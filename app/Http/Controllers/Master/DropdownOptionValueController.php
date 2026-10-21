<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreDropdownOptionValueRequest;
use App\Http\Requests\Master\UpdateDropdownOptionValueRequest;
use App\Http\Resources\Master\DropdownOptionValueResource;
use App\Models\Master\Dropdown;
use App\Models\Master\DropdownOptionValue;
use App\Models\Master\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DropdownOptionValueController extends Controller
{
    /**
     * @var list<string>
     */
    private const SORTABLE_COLUMNS = [
        'option_value',
        'dropdown_name',
        'module_name',
        'position',
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

        $query = DropdownOptionValue::query()
            ->with(['module', 'dropdown', 'parent'])
            ->search($filters['search']);

        if ($filters['sortBy'] === 'dropdown_name') {
            $query->join('mst_dropdown', 'mst_dropdown.option_type_id', '=', 'mst_dropdown_option_values.option_type_id')
                ->orderBy('mst_dropdown.name', $filters['sortOrder'])
                ->select('mst_dropdown_option_values.*');
        } elseif ($filters['sortBy'] === 'module_name') {
            $query->join('mst_module', 'mst_module.module_id', '=', 'mst_dropdown_option_values.module_id')
                ->orderBy('mst_module.module_name', $filters['sortOrder'])
                ->select('mst_dropdown_option_values.*');
        } else {
            $query->orderBy($filters['sortBy'], $filters['sortOrder']);
        }

        $optionValues = $query
            ->orderBy('mst_dropdown_option_values.id')
            ->paginate($filters['pageSize'])
            ->withQueryString();

        return Inertia::render('master/dropdown-option-values/index', [
            'optionValues' => [
                'data' => DropdownOptionValueResource::collection($optionValues->getCollection())->resolve(),
                'meta' => [
                    'current_page' => $optionValues->currentPage(),
                    'last_page' => $optionValues->lastPage(),
                    'per_page' => $optionValues->perPage(),
                    'total' => $optionValues->total(),
                    'from' => $optionValues->firstItem(),
                    'to' => $optionValues->lastItem(),
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
            'dropdownOptions' => Dropdown::query()
                ->active()
                ->orderBy('name')
                ->get(['option_type_id', 'module_id', 'name'])
                ->map(fn (Dropdown $dropdown): array => [
                    'option_type_id' => $dropdown->option_type_id,
                    'module_id' => $dropdown->module_id,
                    'name' => $dropdown->name,
                ])
                ->values()
                ->all(),
            'parentOptions' => DropdownOptionValue::query()
                ->orderBy('option_value')
                ->get(['id', 'option_type_id', 'option_value'])
                ->map(fn (DropdownOptionValue $optionValue): array => [
                    'id' => $optionValue->id,
                    'option_type_id' => $optionValue->option_type_id,
                    'option_value' => $optionValue->option_value,
                ])
                ->values()
                ->all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDropdownOptionValueRequest $request): RedirectResponse
    {
        DropdownOptionValue::query()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Option value created.'),
        ]);

        return back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDropdownOptionValueRequest $request, DropdownOptionValue $dropdownOptionValue): RedirectResponse
    {
        $dropdownOptionValue->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Option value updated.'),
        ]);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DropdownOptionValue $dropdownOptionValue): RedirectResponse
    {
        $dropdownOptionValue->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Option value deleted.'),
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
