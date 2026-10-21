<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreMenuRequest;
use App\Http\Requests\Master\UpdateMenuRequest;
use App\Http\Resources\Master\MenuResource;
use App\Models\Master\Menu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    /**
     * @var list<string>
     */
    private const SORTABLE_COLUMNS = [
        'menu_name',
        'parent_name',
        'menu_route',
        'menu_order',
        'status',
        'created_at',
        'updated_at',
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

        $query = Menu::query()
            ->with('parent')
            ->search($filters['search']);

        if ($filters['sortBy'] === 'parent_name') {
            $query->leftJoin('mst_menu as parent_menus', 'parent_menus.id', '=', 'mst_menu.menu_parent_id')
                ->orderBy('parent_menus.menu_name', $filters['sortOrder'])
                ->select('mst_menu.*');
        } else {
            $query->orderBy($filters['sortBy'], $filters['sortOrder']);
        }

        $menus = $query
            ->orderBy('mst_menu.id')
            ->paginate($filters['pageSize'])
            ->withQueryString();

        return Inertia::render('master/menus/index', [
            'menus' => [
                'data' => MenuResource::collection($menus->getCollection())->resolve(),
                'meta' => [
                    'current_page' => $menus->currentPage(),
                    'last_page' => $menus->lastPage(),
                    'per_page' => $menus->perPage(),
                    'total' => $menus->total(),
                    'from' => $menus->firstItem(),
                    'to' => $menus->lastItem(),
                ],
            ],
            'filters' => $filters,
            'parentOptions' => Menu::query()
                ->orderBy('menu_name')
                ->get(['id', 'menu_name'])
                ->map(fn (Menu $menu): array => [
                    'id' => $menu->id,
                    'menu_name' => $menu->menu_name,
                ])
                ->values()
                ->all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMenuRequest $request): RedirectResponse
    {
        Menu::query()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Menu created.'),
        ]);

        return back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMenuRequest $request, Menu $menu): RedirectResponse
    {
        $menu->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Menu updated.'),
        ]);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Menu $menu): RedirectResponse
    {
        $menu->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Menu deleted.'),
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
}
