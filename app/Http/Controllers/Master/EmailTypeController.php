<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreEmailTypeRequest;
use App\Http\Requests\Master\UpdateEmailTypeRequest;
use App\Http\Resources\Master\EmailTypeResource;
use App\Models\Master\EmailType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailTypeController extends Controller
{
    /**
     * @var list<string>
     */
    private const SORTABLE_COLUMNS = ['code', 'name', 'sort_order', 'status', 'created_at'];

    /**
     * @var list<int>
     */
    private const PAGE_SIZES = [10, 25, 50, 100];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $filters = $this->filters($request);

        $emailTypes = EmailType::query()
            ->search($filters['search'])
            ->orderBy($filters['sortBy'], $filters['sortOrder'])
            ->orderBy('id')
            ->paginate($filters['pageSize'])
            ->withQueryString();

        return Inertia::render('master/email-types/index', [
            'emailTypes' => [
                'data' => EmailTypeResource::collection($emailTypes->getCollection())->resolve(),
                'meta' => [
                    'current_page' => $emailTypes->currentPage(),
                    'last_page' => $emailTypes->lastPage(),
                    'per_page' => $emailTypes->perPage(),
                    'total' => $emailTypes->total(),
                    'from' => $emailTypes->firstItem(),
                    'to' => $emailTypes->lastItem(),
                ],
            ],
            'filters' => $filters,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmailTypeRequest $request): RedirectResponse
    {
        EmailType::query()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Email type created.'),
        ]);

        return back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmailTypeRequest $request, EmailType $emailType): RedirectResponse
    {
        $emailType->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Email type updated.'),
        ]);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmailType $emailType): RedirectResponse
    {
        if ($emailType->emailAccounts()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This email type cannot be deleted because it is assigned to an email account.'),
            ]);

            return back();
        }

        $emailType->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Email type deleted.'),
        ]);

        return back();
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
            'sortBy' => in_array($sortBy, self::SORTABLE_COLUMNS, true) ? $sortBy : 'sort_order',
            'sortOrder' => $sortOrder,
            'page' => $page > 0 ? $page : 1,
            'pageSize' => in_array($pageSize, self::PAGE_SIZES, true) ? $pageSize : 10,
        ];
    }
}
