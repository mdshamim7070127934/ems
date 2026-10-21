<?php

namespace App\Http\Controllers\Mailbox;

use App\Http\Requests\Mailbox\StoreMailboxDraftRequest;
use App\Http\Resources\Mailbox\MailboxAccountResource;
use App\Http\Resources\Mailbox\MailboxDraftResource;
use App\Jobs\SendMailboxEmailJob;
use App\Models\Mailbox\Email;
use App\Models\Mailbox\EmailDraft;
use App\Services\Mailbox\MailboxAccountService;
use App\Services\Mailbox\MailboxDraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MailboxDraftController extends MailboxController
{
    public function index(Request $request, MailboxAccountService $accounts): Response
    {
        $account = $this->mailboxAccount($request, $accounts);
        $this->authorize('viewAny', EmailDraft::class);

        $filters = $this->listFilters($request, $account->id);
        $drafts = $account->drafts()
            ->with('attachments')
            ->when($filters['search'] !== '', fn ($query) => $query->search($filters['search']))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate($filters['pageSize'], ['*'], 'page', $filters['page'])
            ->withQueryString();

        return Inertia::render('mailbox/drafts', [
            ...$this->mailboxChrome($request, $account),
            'account' => (new MailboxAccountResource($account))->resolve(),
            'drafts' => [
                'data' => MailboxDraftResource::collection($drafts->getCollection())->resolve(),
                'meta' => [
                    'current_page' => $drafts->currentPage(),
                    'last_page' => $drafts->lastPage(),
                    'per_page' => $drafts->perPage(),
                    'total' => $drafts->total(),
                    'from' => $drafts->firstItem(),
                    'to' => $drafts->lastItem(),
                ],
            ],
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, EmailDraft $mailboxDraft): JsonResponse|RedirectResponse
    {
        $this->authorize('view', $mailboxDraft);

        $mailboxDraft->load('attachments');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Draft fetched.',
                'data' => (new MailboxDraftResource($mailboxDraft))->resolve(),
            ]);
        }

        return to_route('mailbox.compose', [
            'draft' => $mailboxDraft->id,
            'account_id' => $mailboxDraft->account_id,
        ]);
    }

    public function create(Request $request, MailboxAccountService $accounts, MailboxDraftService $drafts): Response
    {
        $account = $this->mailboxAccount($request, $accounts);
        $this->authorize('create', EmailDraft::class);

        $prefill = [
            'to_emails' => [],
            'cc_emails' => [],
            'bcc_emails' => [],
            'subject' => '',
            'body_html' => '',
            'thread_id' => null,
            'in_reply_to' => null,
        ];

        if ($request->filled('draft')) {
            $draft = EmailDraft::query()->with('attachments')->findOrFail($request->integer('draft'));
            $this->authorize('view', $draft);
            $prefill = (new MailboxDraftResource($draft))->resolve();
        } elseif ($request->filled('reply') || $request->filled('reply_all') || $request->filled('forward')) {
            $emailId = $request->integer('reply') ?: $request->integer('reply_all') ?: $request->integer('forward');
            $email = Email::query()->findOrFail($emailId);
            $this->authorize('view', $email);

            $prefill = $request->filled('forward')
                ? $drafts->forwardPrefill($email)
                : $drafts->replyPrefill($email, $request->filled('reply_all'), $account);
        }

        return Inertia::render('mailbox/compose', [
            ...$this->mailboxChrome($request, $account),
            'account' => (new MailboxAccountResource($account))->resolve(),
            'draft' => $prefill,
        ]);
    }

    public function prefill(Request $request, MailboxAccountService $accounts, MailboxDraftService $drafts): JsonResponse
    {
        $account = $this->mailboxAccount($request, $accounts);

        if (! $request->filled('reply') && ! $request->filled('reply_all') && ! $request->filled('forward')) {
            return response()->json([
                'success' => false,
                'message' => 'A reply, reply all, or forward target is required.',
                'errors' => [
                    'email' => ['A reply, reply all, or forward target is required.'],
                ],
            ], 422);
        }

        $emailId = $request->integer('reply') ?: $request->integer('reply_all') ?: $request->integer('forward');
        $email = Email::query()->findOrFail($emailId);
        $this->authorize('view', $email);

        $data = $request->filled('forward')
            ? $drafts->forwardPrefill($email)
            : $drafts->replyPrefill($email, $request->filled('reply_all'), $account);

        return response()->json([
            'success' => true,
            'message' => 'Compose prefill fetched.',
            'data' => $data,
        ]);
    }

    public function store(
        StoreMailboxDraftRequest $request,
        MailboxAccountService $accounts,
        MailboxDraftService $drafts,
    ): RedirectResponse|JsonResponse {
        $account = $this->mailboxAccount($request, $accounts);
        $this->authorize('create', EmailDraft::class);

        $existing = $request->filled('draft_id')
            ? EmailDraft::query()->findOrFail($request->integer('draft_id'))
            : null;

        if ($existing !== null) {
            $this->authorize('update', $existing);
        }

        $uploads = [];
        $attachments = $request->file('attachments');

        if (is_array($attachments)) {
            foreach ($attachments as $file) {
                $uploads[] = $file;
            }
        }

        $keepAttachmentIds = $request->exists('keep_attachment_ids')
            ? array_values(array_filter(array_map(intval(...), $request->array('keep_attachment_ids'))))
            : null;

        $draft = $drafts->save(
            $account,
            $existing,
            $request->safe()->except(['attachments', 'keep_attachment_ids', 'draft_id', 'send', 'account_id', 'autosave']),
            $uploads,
            $keepAttachmentIds,
        );

        if ($request->boolean('send')) {
            $this->authorize('send', $draft);
            SendMailboxEmailJob::dispatch($draft);

            if ($request->boolean('autosave') || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Email queued for sending.'),
                    'data' => (new MailboxDraftResource($draft))->resolve(),
                ]);
            }

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('Email queued for sending.'),
            ]);

            return to_route('mailbox.index', ['account_id' => $account->id]);
        }

        if ($request->boolean('autosave') || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Draft saved.',
                'data' => (new MailboxDraftResource($draft))->resolve(),
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Draft saved.'),
        ]);

        return to_route('mailbox.compose', ['draft' => $draft->id, 'account_id' => $account->id]);
    }

    public function send(Request $request, EmailDraft $mailboxDraft): RedirectResponse
    {
        $this->authorize('send', $mailboxDraft);

        SendMailboxEmailJob::dispatch($mailboxDraft);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Email queued for sending.'),
        ]);

        return to_route('mailbox.index');
    }

    public function destroy(EmailDraft $mailboxDraft): RedirectResponse
    {
        $this->authorize('delete', $mailboxDraft);

        $accountId = $mailboxDraft->account_id;
        $mailboxDraft->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Draft deleted.'),
        ]);

        return to_route('mailbox.drafts', ['account_id' => $accountId]);
    }

    /**
     * @return array{account_id: int, search: string, page: int, pageSize: int}
     */
    private function listFilters(Request $request, int $accountId): array
    {
        $pageSize = $request->integer('pageSize', 25);

        if (! in_array($pageSize, [10, 25, 50, 100], true)) {
            $pageSize = 25;
        }

        return [
            'account_id' => $accountId,
            'search' => $request->string('search')->toString(),
            'page' => max(1, $request->integer('page', 1)),
            'pageSize' => $pageSize,
        ];
    }
}
