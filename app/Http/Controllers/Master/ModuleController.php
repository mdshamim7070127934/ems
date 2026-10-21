<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreModuleRequest;
use App\Http\Requests\Master\UpdateModuleRequest;
use App\Http\Resources\Master\ModuleResource;
use App\Models\Master\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ModuleController extends Controller
{
    /**
     * @var list<string>
     */
    private const SORTABLE_COLUMNS = ['module_name', 'status', 'created_on', 'updated_on'];

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

        $modules = Module::query()
            ->search($filters['search'])
            ->orderBy($filters['sortBy'], $filters['sortOrder'])
            ->orderBy('module_id')
            ->paginate($filters['pageSize'])
            ->withQueryString();

        return Inertia::render('master/modules/index', [
            'modules' => [
                'data' => ModuleResource::collection($modules->getCollection())->resolve(),
                'meta' => [
                    'current_page' => $modules->currentPage(),
                    'last_page' => $modules->lastPage(),
                    'per_page' => $modules->perPage(),
                    'total' => $modules->total(),
                    'from' => $modules->firstItem(),
                    'to' => $modules->lastItem(),
                ],
            ],
            'filters' => $filters,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreModuleRequest $request): RedirectResponse
    {
        Module::query()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Module created.'),
        ]);

        return back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateModuleRequest $request, Module $module): RedirectResponse
    {
        $module->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Module updated.'),
        ]);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Module $module): RedirectResponse
    {
        $module->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Module deleted.'),
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
