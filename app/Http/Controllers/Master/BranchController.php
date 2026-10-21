<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreBranchRequest;
use App\Http\Requests\Master\UpdateBranchRequest;
use App\Http\Resources\Master\BranchResource;
use App\Models\Master\Branch;
use App\Models\Master\Country;
use App\Models\Master\Group;
use App\Models\Master\Region;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchController extends Controller
{
    /**
     * @var list<string>
     */
    private const SORTABLE_COLUMNS = [
        'branch_name',
        'branch_code',
        'country_name',
        'region_name',
        'group_name',
        'manager_name',
        'status',
        'created_on',
        'updated_on',
    ];

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

        $query = Branch::query()
            ->with(['country', 'region', 'group', 'manager', 'owner', 'creator', 'modifier'])
            ->search($filters['search']);

        if ($filters['sortBy'] === 'country_name') {
            $query->join('mst_country', 'mst_country.id', '=', 'mst_branch.country_id')
                ->orderBy('mst_country.name', $filters['sortOrder'])
                ->select('mst_branch.*');
        } elseif ($filters['sortBy'] === 'region_name') {
            $query->join('mst_region', 'mst_region.id', '=', 'mst_branch.region_id')
                ->orderBy('mst_region.region_name', $filters['sortOrder'])
                ->select('mst_branch.*');
        } elseif ($filters['sortBy'] === 'group_name') {
            $query->join('mst_group', 'mst_group.id', '=', 'mst_branch.group_id')
                ->orderBy('mst_group.group_name', $filters['sortOrder'])
                ->select('mst_branch.*');
        } elseif ($filters['sortBy'] === 'manager_name') {
            $query->join('users', 'users.id', '=', 'mst_branch.branch_manager')
                ->orderBy('users.name', $filters['sortOrder'])
                ->select('mst_branch.*');
        } else {
            $query->orderBy($filters['sortBy'], $filters['sortOrder']);
        }

        $branches = $query
            ->orderBy('mst_branch.id')
            ->paginate($filters['pageSize'])
            ->withQueryString();

        return Inertia::render('master/branches/index', [
            'branches' => [
                'data' => BranchResource::collection($branches->getCollection())->resolve(),
                'meta' => [
                    'current_page' => $branches->currentPage(),
                    'last_page' => $branches->lastPage(),
                    'per_page' => $branches->perPage(),
                    'total' => $branches->total(),
                    'from' => $branches->firstItem(),
                    'to' => $branches->lastItem(),
                ],
            ],
            'filters' => $filters,
            'countryOptions' => Country::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Country $country): array => [
                    'id' => $country->id,
                    'name' => $country->name,
                ])
                ->values()
                ->all(),
            'regionOptions' => Region::query()
                ->active()
                ->orderBy('region_name')
                ->get(['id', 'country_id', 'region_name'])
                ->map(fn (Region $region): array => [
                    'id' => $region->id,
                    'country_id' => $region->country_id,
                    'name' => $region->region_name,
                ])
                ->values()
                ->all(),
            'groupOptions' => Group::query()
                ->active()
                ->orderBy('group_name')
                ->get(['id', 'group_name'])
                ->map(fn (Group $group): array => [
                    'id' => $group->id,
                    'name' => $group->group_name,
                ])
                ->values()
                ->all(),
            'userOptions' => $this->userOptions(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBranchRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $attributes = $request->validated();
        $attributes['created_by'] = $user->id;
        $attributes['last_modified_by'] = $user->id;

        Branch::query()->create($attributes);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Branch created.'),
        ]);

        return back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBranchRequest $request, Branch $branch): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $attributes = $request->validated();
        $attributes['last_modified_by'] = $user->id;

        $branch->update($attributes);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Branch updated.'),
        ]);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Branch $branch): RedirectResponse
    {
        $branch->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Branch deleted.'),
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
            'sortBy' => in_array($sortBy, self::SORTABLE_COLUMNS, true) ? $sortBy : 'created_on',
            'sortOrder' => $sortOrder,
            'page' => $page > 0 ? $page : 1,
            'pageSize' => in_array($pageSize, self::PAGE_SIZES, true) ? $pageSize : 10,
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function userOptions(): array
    {
        return User::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->values()
            ->all();
    }
}
