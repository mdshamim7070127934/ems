<?php

namespace App\Http\Controllers\Master;

use App\Enums\Master\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreRoleRequest;
use App\Http\Requests\Master\UpdateRoleRequest;
use App\Http\Resources\Master\RoleResource;
use App\Models\Master\Menu;
use App\Models\Master\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    /**
     * @var list<string>
     */
    private const SORTABLE_COLUMNS = ['role_name', 'display_name', 'is_active', 'created_at', 'updated_at'];

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

        $roles = Role::query()
            ->search($filters['search'])
            ->orderBy($filters['sortBy'], $filters['sortOrder'])
            ->orderBy('id')
            ->paginate($filters['pageSize'])
            ->withQueryString();

        return Inertia::render('master/roles/index', [
            'roles' => [
                'data' => RoleResource::collection($roles->getCollection())->resolve(),
                'meta' => [
                    'current_page' => $roles->currentPage(),
                    'last_page' => $roles->lastPage(),
                    'per_page' => $roles->perPage(),
                    'total' => $roles->total(),
                    'from' => $roles->firstItem(),
                    'to' => $roles->lastItem(),
                ],
            ],
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('master/roles/form', [
            'role' => null,
            'menuGroups' => $this->menuGroups(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $data = $request->safe()->only(['role_name', 'display_name', 'is_active']);
        /** @var list<int> $menuIds */
        $menuIds = $request->validated('menu_ids');

        DB::transaction(function () use ($data, $menuIds): void {
            $role = Role::query()->create($data);
            $role->syncMenuPermissions($menuIds);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Role created.'),
        ]);

        return to_route('master.roles.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role): Response
    {
        $role->load('menus');

        return Inertia::render('master/roles/show', [
            'role' => (new RoleResource($role))->resolve(),
            'menuGroups' => $this->menuGroups(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role): Response
    {
        $role->load('menus');

        return Inertia::render('master/roles/form', [
            'role' => (new RoleResource($role))->resolve(),
            'menuGroups' => $this->menuGroups(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        if ($role->isProtected()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Super administrator role cannot be modified.'),
            ]);

            return back();
        }

        $data = $request->safe()->only(['role_name', 'display_name', 'is_active']);
        /** @var list<int> $menuIds */
        $menuIds = $request->validated('menu_ids');

        DB::transaction(function () use ($role, $data, $menuIds): void {
            $role->update($data);
            $role->syncMenuPermissions($menuIds);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Role updated.'),
        ]);

        return to_route('master.roles.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role): RedirectResponse
    {
        if ($role->isProtected()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Super administrator role cannot be deleted.'),
            ]);

            return back();
        }

        if ($role->users()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Role cannot be deleted because users are assigned to it.'),
            ]);

            return back();
        }

        $role->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Role deleted.'),
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
     * @return array<int, array{id: int, label: string, menus: array<int, array{id: int, menu_name: string, menu_route: string}>}>
     */
    private function menuGroups(): array
    {
        $menus = Menu::query()
            ->where('status', Status::Active)
            ->orderBy('menu_order')
            ->orderBy('menu_name')
            ->get(['id', 'menu_parent_id', 'menu_name', 'menu_route']);

        $parents = $menus->where('menu_parent_id', 0)->values();
        $childrenByParent = $menus
            ->filter(fn (Menu $menu): bool => (int) $menu->menu_parent_id !== 0)
            ->groupBy(fn (Menu $menu): string => (string) $menu->menu_parent_id);

        $groups = [];

        foreach ($parents as $parent) {
            $children = $childrenByParent->get((string) $parent->id, collect());

            $groups[] = [
                'id' => $parent->id,
                'label' => $parent->menu_name,
                'menus' => collect([$parent])
                    ->concat($children)
                    ->map(fn (Menu $menu): array => [
                        'id' => $menu->id,
                        'menu_name' => $menu->menu_name,
                        'menu_route' => $menu->menu_route,
                    ])
                    ->values()
                    ->all(),
            ];
        }

        $parentIds = $parents->pluck('id');
        $orphans = $menus
            ->filter(fn (Menu $menu): bool => (int) $menu->menu_parent_id !== 0 && ! $parentIds->contains($menu->menu_parent_id))
            ->values();

        if ($orphans->isNotEmpty()) {
            $groups[] = [
                'id' => 0,
                'label' => 'Other',
                'menus' => $orphans
                    ->map(fn (Menu $menu): array => [
                        'id' => $menu->id,
                        'menu_name' => $menu->menu_name,
                        'menu_route' => $menu->menu_route,
                    ])
                    ->all(),
            ];
        }

        return $groups;
    }
}
