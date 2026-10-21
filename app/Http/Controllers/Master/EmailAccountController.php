<?php

namespace App\Http\Controllers\Master;

use App\Enums\Master\EmailMailer;
use App\Enums\Master\EmailProvider;
use App\Enums\Master\SmtpEncryption;
use App\Enums\Master\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreEmailAccountRequest;
use App\Http\Requests\Master\UpdateEmailAccountRequest;
use App\Http\Resources\Master\EmailAccountResource;
use App\Models\Master\EmailAccount;
use App\Models\Master\EmailType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EmailAccountController extends Controller
{
    /**
     * @var list<string>
     */
    private const SORTABLE_COLUMNS = ['name', 'provider', 'from_email', 'is_default', 'status', 'created_at'];

    /**
     * @var list<int>
     */
    private const PAGE_SIZES = [10, 25, 50, 100];

    /**
     * @var list<string>
     */
    private const SMTP_FIELDS = [
        'name',
        'provider',
        'mailer',
        'from_name',
        'from_email',
        'username',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'status',
        'is_default',
    ];

    /**
     * @var list<string>
     */
    private const OAUTH_FIELDS = [
        'name',
        'from_name',
        'status',
        'is_default',
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $filters = $this->filters($request);

        $accounts = EmailAccount::query()
            ->with('emailTypes')
            ->search($filters['search'])
            ->orderBy($filters['sortBy'], $filters['sortOrder'])
            ->orderBy('id')
            ->paginate($filters['pageSize'])
            ->withQueryString();

        return Inertia::render('master/email-accounts/index', [
            'emailAccounts' => [
                'data' => EmailAccountResource::collection($accounts->getCollection())->resolve(),
                'meta' => [
                    'current_page' => $accounts->currentPage(),
                    'last_page' => $accounts->lastPage(),
                    'per_page' => $accounts->perPage(),
                    'total' => $accounts->total(),
                    'from' => $accounts->firstItem(),
                    'to' => $accounts->lastItem(),
                ],
            ],
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new email account.
     */
    public function create(): Response
    {
        return Inertia::render('master/email-accounts/form', [
            'emailAccount' => null,
            'emailTypeOptions' => $this->emailTypeOptions(),
            'providerOptions' => $this->enumOptions([EmailProvider::Smtp, EmailProvider::Other]),
            'mailerOptions' => $this->enumOptions([EmailMailer::Smtp]),
            'encryptionOptions' => $this->enumOptions(SmtpEncryption::cases()),
        ]);
    }

    /**
     * Store a newly created email account.
     */
    public function store(StoreEmailAccountRequest $request): RedirectResponse
    {
        $data = $this->accountPayload($request->safe()->only(self::SMTP_FIELDS));
        $data = $this->mergePassword($data, $request->validated(), required: true);
        /** @var list<int> $emailTypeIds */
        $emailTypeIds = $request->validated('email_type_ids');

        DB::transaction(function () use ($data, $emailTypeIds): void {
            $account = EmailAccount::query()->create($data);
            $account->syncEmailTypes($emailTypeIds);
            $this->ensureSingleDefault($account);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Email account created.'),
        ]);

        return to_route('master.email-accounts.index');
    }

    /**
     * Display the specified email account.
     */
    public function show(EmailAccount $emailAccount): Response
    {
        $emailAccount->load('emailTypes');

        return Inertia::render('master/email-accounts/show', [
            'emailAccount' => (new EmailAccountResource($emailAccount))->resolve(),
            'emailTypeOptions' => $this->emailTypeOptions($emailAccount),
        ]);
    }

    /**
     * Show the form for editing the specified email account.
     */
    public function edit(EmailAccount $emailAccount): Response
    {
        $emailAccount->load('emailTypes');

        $oauth = $emailAccount->usesOAuth();

        return Inertia::render('master/email-accounts/form', [
            'emailAccount' => (new EmailAccountResource($emailAccount))->resolve(),
            'emailTypeOptions' => $this->emailTypeOptions($emailAccount),
            'providerOptions' => $this->enumOptions(
                $oauth ? [$emailAccount->provider] : [EmailProvider::Smtp, EmailProvider::Other],
            ),
            'mailerOptions' => $this->enumOptions(
                $oauth ? [$emailAccount->mailer] : [EmailMailer::Smtp],
            ),
            'encryptionOptions' => $this->enumOptions(SmtpEncryption::cases()),
        ]);
    }

    /**
     * Update the specified email account.
     */
    public function update(UpdateEmailAccountRequest $request, EmailAccount $emailAccount): RedirectResponse
    {
        $fields = $emailAccount->usesOAuth() ? self::OAUTH_FIELDS : self::SMTP_FIELDS;
        $data = $this->accountPayload($request->safe()->only($fields));

        if (! $emailAccount->usesOAuth()) {
            $data = $this->mergePassword($data, $request->validated(), required: false);
        }
        /** @var list<int> $emailTypeIds */
        $emailTypeIds = $request->validated('email_type_ids');

        DB::transaction(function () use ($emailAccount, $data, $emailTypeIds): void {
            $emailAccount->update($data);
            $emailAccount->syncEmailTypes($emailTypeIds);
            $this->ensureSingleDefault($emailAccount->refresh());
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Email account updated.'),
        ]);

        return to_route('master.email-accounts.index');
    }

    /**
     * Remove the specified email account.
     */
    public function destroy(EmailAccount $emailAccount): RedirectResponse
    {
        $emailAccount->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Email account deleted.'),
        ]);

        return back();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function accountPayload(array $data): array
    {
        if (($data['status'] ?? null) === Status::Inactive->value) {
            $data['is_default'] = false;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function mergePassword(array $data, array $validated, bool $required): array
    {
        if ($required || filled($validated['password'] ?? null)) {
            $data['password'] = $validated['password'] ?? null;
        }

        return $data;
    }

    private function ensureSingleDefault(EmailAccount $account): void
    {
        if (! $account->is_default) {
            return;
        }

        EmailAccount::query()
            ->whereKeyNot($account->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    /**
     * @return array{search: string, sortBy: string, sortOrder: 'asc'|'desc', page: int, pageSize: int}
     */
    private function filters(Request $request): array
    {
        $search = trim($request->string('search')->toString());
        $sortBy = $request->string('sortBy')->toString();
        $sortOrder = strtolower($request->string('sortOrder')->toString()) === 'asc' ? 'asc' : 'desc';
        $pageSize = $request->integer('pageSize', 10);
        $page = $request->integer('page', 1);

        return [
            'search' => $search,
            'sortBy' => in_array($sortBy, self::SORTABLE_COLUMNS, true) ? $sortBy : 'created_at',
            'sortOrder' => $sortOrder,
            'page' => $page > 0 ? $page : 1,
            'pageSize' => in_array($pageSize, self::PAGE_SIZES, true) ? $pageSize : 10,
        ];
    }

    /**
     * @return array<int, array{id: int, code: string, name: string, status: string, disabled: bool}>
     */
    private function emailTypeOptions(?EmailAccount $account = null): array
    {
        $assignedIds = $account?->emailTypes->pluck('id')->map(intval(...))->all() ?? [];

        return EmailType::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'status'])
            ->filter(function (EmailType $type) use ($assignedIds): bool {
                return $type->status === Status::Active || in_array($type->id, $assignedIds, true);
            })
            ->map(fn (EmailType $type): array => [
                'id' => $type->id,
                'code' => $type->code,
                'name' => $type->name,
                'status' => $type->status->value,
                'disabled' => $type->status !== Status::Active && ! in_array($type->id, $assignedIds, true),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<EmailProvider|EmailMailer|SmtpEncryption>  $cases
     * @return array<int, array{value: string, label: string}>
     */
    private function enumOptions(array $cases): array
    {
        return array_map(
            fn (EmailProvider|EmailMailer|SmtpEncryption $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            $cases,
        );
    }
}
