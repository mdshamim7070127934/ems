<?php

namespace App\Http\Controllers\Mailbox;

use App\Http\Resources\Mailbox\MailboxAccountResource;
use App\Http\Resources\Mailbox\MailboxThreadResource;
use App\Models\Mailbox\EmailAccount;
use App\Services\Mailbox\BlockedSenderService;
use App\Services\Mailbox\MailboxAccountService;
use App\Services\Mailbox\MailboxThreadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MailboxInboxController extends MailboxController
{
    public function index(
        Request $request,
        MailboxAccountService $accounts,
        MailboxThreadService $threads,
    ): Response|RedirectResponse {
        $user = $this->user($request);
        $this->authorize('viewAny', EmailAccount::class);

        $mailboxAccounts = EmailAccount::query()
            ->ownedBy($user)
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        if ($mailboxAccounts->isEmpty()) {
            return to_route('mailbox.connect');
        }

        $account = $this->mailboxAccount($request, $accounts);
        $filters = $this->filters($request);
        $this->authorize('viewFolder', [$account, $filters['folder']]);

        $paginator = $threads->paginate(
            $account,
            $user,
            $filters['folder'],
            $filters['label'],
            $filters['search'] !== '' ? $filters['search'] : null,
            $filters['page'],
            $filters['pageSize'],
        );

        return Inertia::render('mailbox/index', [
            ...$this->mailboxChrome($request, $account),
            'account' => (new MailboxAccountResource($account))->resolve(),
            'threads' => [
                'data' => MailboxThreadResource::collection($paginator->getCollection())->resolve(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ],
            'filters' => $filters,
        ]);
    }

    public function block(
        Request $request,
        MailboxAccountService $accounts,
        BlockedSenderService $blocked,
    ): RedirectResponse {
        $request->validate(['email' => ['required', 'email', 'max:100']]);
        $account = $this->mailboxAccount($request, $accounts);
        $this->authorize('update', $account);

        $blocked->block($this->user($request), $request->string('email')->toString());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Sender blocked.'),
        ]);

        return back();
    }

    public function unblock(
        Request $request,
        MailboxAccountService $accounts,
        BlockedSenderService $blocked,
    ): RedirectResponse {
        $request->validate(['email' => ['required', 'email', 'max:100']]);
        $account = $this->mailboxAccount($request, $accounts);
        $this->authorize('update', $account);

        $blocked->unblock($this->user($request), $request->string('email')->toString());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Sender unblocked.'),
        ]);

        return back();
    }

    /**
     * @return array{account_id: int|null, folder: string, label: int|null, search: string, page: int, pageSize: int}
     */
    private function filters(Request $request): array
    {
        $pageSize = $request->integer('pageSize', 25);

        if (! in_array($pageSize, [10, 25, 50, 100], true)) {
            $pageSize = 25;
        }

        return [
            'account_id' => $request->integer('account_id') ?: null,
            'folder' => $request->string('folder', 'inbox')->toString() ?: 'inbox',
            'label' => $request->integer('label') ?: null,
            'search' => $request->string('search')->toString(),
            'page' => max(1, $request->integer('page', 1)),
            'pageSize' => $pageSize,
        ];
    }
}
