<?php

namespace App\Http\Controllers\Settings;

use App\Enums\Master\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreEmailSignatureRequest;
use App\Http\Requests\Settings\UpdateEmailSignatureRequest;
use App\Http\Resources\Mailbox\EmailSignatureResource;
use App\Models\Mailbox\EmailSignature;
use App\Models\User;
use App\Services\Mailbox\EmailSignatureService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailSignatureController extends Controller
{
    use AuthorizesRequests;

    /**
     * @var list<string>
     */
    private const SORTABLE_COLUMNS = ['signature_name', 'status', 'is_default', 'created_at'];

    /**
     * @var list<int>
     */
    private const PAGE_SIZES = [10, 25, 50, 100];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', EmailSignature::class);

        $user = $this->user($request);
        $filters = $this->filters($request);

        $signatures = EmailSignature::query()
            ->whereBelongsTo($user)
            ->search($filters['search'])
            ->orderBy($filters['sortBy'], $filters['sortOrder'])
            ->orderBy('id')
            ->paginate($filters['pageSize'])
            ->withQueryString();

        return Inertia::render('settings/email-signatures/index', [
            'signatures' => [
                'data' => EmailSignatureResource::collection($signatures->getCollection())->resolve(),
                'meta' => [
                    'current_page' => $signatures->currentPage(),
                    'last_page' => $signatures->lastPage(),
                    'per_page' => $signatures->perPage(),
                    'total' => $signatures->total(),
                    'from' => $signatures->firstItem(),
                    'to' => $signatures->lastItem(),
                ],
            ],
            'filters' => $filters,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmailSignatureRequest $request, EmailSignatureService $signatures): RedirectResponse
    {
        $validated = $request->validated();

        $signatures->create($this->user($request), [
            'signature_name' => $validated['signature_name'],
            'signature_html' => $validated['signature_html'] ?? null,
            'status' => Status::from($validated['status']),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Signature created.'),
        ]);

        return back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateEmailSignatureRequest $request,
        EmailSignature $emailSignature,
        EmailSignatureService $signatures,
    ): RedirectResponse {
        $validated = $request->validated();

        $signatures->update($emailSignature, [
            'signature_name' => $validated['signature_name'],
            'signature_html' => $validated['signature_html'] ?? null,
            'status' => Status::from($validated['status']),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Signature updated.'),
        ]);

        return back();
    }

    /**
     * Mark or unset the signature as the user's default.
     */
    public function makeDefault(
        Request $request,
        EmailSignature $emailSignature,
        EmailSignatureService $signatures,
    ): RedirectResponse {
        $this->authorize('update', $emailSignature);

        $updated = $signatures->makeDefault($emailSignature, $request->boolean('unset'));

        Inertia::flash('toast', [
            'type' => $updated ? 'success' : 'error',
            'message' => $updated
                ? ($request->boolean('unset')
                    ? __('Default signature removed.')
                    : __('Default signature updated.'))
                : __('Only an active signature can be set as the default.'),
        ]);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmailSignature $emailSignature, EmailSignatureService $signatures): RedirectResponse
    {
        $this->authorize('delete', $emailSignature);

        $signatures->delete($emailSignature);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Signature deleted.'),
        ]);

        return back();
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }

    /**
     * @return array{search: string, sortBy: string, sortOrder: 'asc'|'desc', page: int, pageSize: int}
     */
    private function filters(Request $request): array
    {
        $search = trim($request->string('search')->toString());
        $sortBy = $request->string('sortBy')->toString();
        $sortOrder = strtolower($request->string('sortOrder')->toString()) === 'desc' ? 'desc' : 'asc';
        $pageSize = $request->integer('pageSize', 10);
        $page = $request->integer('page', 1);

        return [
            'search' => $search,
            'sortBy' => in_array($sortBy, self::SORTABLE_COLUMNS, true) ? $sortBy : 'signature_name',
            'sortOrder' => $sortOrder,
            'page' => $page > 0 ? $page : 1,
            'pageSize' => in_array($pageSize, self::PAGE_SIZES, true) ? $pageSize : 10,
        ];
    }
}
