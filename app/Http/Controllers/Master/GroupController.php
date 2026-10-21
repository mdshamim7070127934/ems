<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreGroupRequest;
use App\Http\Requests\Master\UpdateGroupRequest;
use App\Http\Resources\Master\GroupResource;
use App\Models\Master\Group;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    /**
     * @var list<string>
     */
    private const SORTABLE_COLUMNS = ['group_name', 'owner_name', 'status', 'created_at', 'updated_at'];

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

        $query = Group::query()
            ->with('owner')
            ->search($filters['search']);

        if ($filters['sortBy'] === 'owner_name') {
            $query->join('users', 'users.id', '=', 'mst_group.owner_id')
                ->orderBy('users.name', $filters['sortOrder'])
                ->select('mst_group.*');
        } else {
            $query->orderBy($filters['sortBy'], $filters['sortOrder']);
        }

        $groups = $query
            ->orderBy('mst_group.id')
            ->paginate($filters['pageSize'])
            ->withQueryString();

        return Inertia::render('master/groups/index', [
            'groups' => [
                'data' => GroupResource::collection($groups->getCollection())->resolve(),
                'meta' => [
                    'current_page' => $groups->currentPage(),
                    'last_page' => $groups->lastPage(),
                    'per_page' => $groups->perPage(),
                    'total' => $groups->total(),
                    'from' => $groups->firstItem(),
                    'to' => $groups->lastItem(),
                ],
            ],
            'filters' => $filters,
            'userOptions' => $this->userOptions(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGroupRequest $request): RedirectResponse
    {
        Group::query()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Group created.'),
        ]);

        return back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGroupRequest $request, Group $group): RedirectResponse
    {
        $group->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Group updated.'),
        ]);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Group $group): RedirectResponse
    {
        if ($group->branches()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This group cannot be deleted because it is assigned to a branch.'),
            ]);

            return back();
        }

        $group->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Group deleted.'),
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
            'sortBy' => in_array($sortBy, self::SORTABLE_COLUMNS, true) ? $sortBy : 'created_at',
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
