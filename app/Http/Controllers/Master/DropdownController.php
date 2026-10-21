<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreDropdownRequest;
use App\Http\Requests\Master\UpdateDropdownRequest;
use App\Http\Resources\Master\DropdownResource;
use App\Models\Master\Dropdown;
use App\Models\Master\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DropdownController extends Controller
{
    /**
     * @var list<string>
     */
    private const SORTABLE_COLUMNS = ['name', 'module_name', 'status', 'created_on', 'updated_on'];

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

        $query = Dropdown::query()
            ->with('module')
            ->search($filters['search']);

        if ($filters['sortBy'] === 'module_name') {
            $query->join('mst_module', 'mst_module.module_id', '=', 'mst_dropdown.module_id')
                ->orderBy('mst_module.module_name', $filters['sortOrder'])
                ->select('mst_dropdown.*');
        } else {
            $query->orderBy($filters['sortBy'], $filters['sortOrder']);
        }

        $dropdowns = $query
            ->orderBy('mst_dropdown.option_type_id')
            ->paginate($filters['pageSize'])
            ->withQueryString();

        return Inertia::render('master/dropdowns/index', [
            'dropdowns' => [
                'data' => DropdownResource::collection($dropdowns->getCollection())->resolve(),
                'meta' => [
                    'current_page' => $dropdowns->currentPage(),
                    'last_page' => $dropdowns->lastPage(),
                    'per_page' => $dropdowns->perPage(),
                    'total' => $dropdowns->total(),
                    'from' => $dropdowns->firstItem(),
                    'to' => $dropdowns->lastItem(),
                ],
            ],
            'filters' => $filters,
            'moduleOptions' => Module::query()
                ->active()
                ->orderBy('module_name')
                ->get(['module_id', 'module_name'])
                ->map(fn (Module $module): array => [
                    'module_id' => $module->module_id,
                    'module_name' => $module->module_name,
                ])
                ->values()
                ->all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDropdownRequest $request): RedirectResponse
    {
        Dropdown::query()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Dropdown created.'),
        ]);

        return back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDropdownRequest $request, Dropdown $dropdown): RedirectResponse
    {
        $dropdown->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Dropdown updated.'),
        ]);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Dropdown $dropdown): RedirectResponse
    {
        $dropdown->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Dropdown deleted.'),
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
}
