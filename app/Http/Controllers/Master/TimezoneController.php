<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreTimezoneRequest;
use App\Http\Requests\Master\UpdateTimezoneRequest;
use App\Http\Resources\Master\TimezoneResource;
use App\Models\Master\Timezone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TimezoneController extends Controller
{
    /**
     * @var list<string>
     */
    private const SORTABLE_COLUMNS = ['name', 'display_name', 'utc_offset', 'status', 'created_on'];

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

        $timezones = Timezone::query()
            ->search($filters['search'])
            ->orderBy($filters['sortBy'], $filters['sortOrder'])
            ->orderBy('id')
            ->paginate($filters['pageSize'])
            ->withQueryString();

        return Inertia::render('master/timezones/index', [
            'timezones' => [
                'data' => TimezoneResource::collection($timezones->getCollection())->resolve(),
                'meta' => [
                    'current_page' => $timezones->currentPage(),
                    'last_page' => $timezones->lastPage(),
                    'per_page' => $timezones->perPage(),
                    'total' => $timezones->total(),
                    'from' => $timezones->firstItem(),
                    'to' => $timezones->lastItem(),
                ],
            ],
            'filters' => $filters,
            'identifierOptions' => collect(Timezone::identifiers())
                ->map(fn (string $name): array => Timezone::attributesFor($name))
                ->values()
                ->all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTimezoneRequest $request): RedirectResponse
    {
        Timezone::query()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Timezone created.'),
        ]);

        return back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTimezoneRequest $request, Timezone $timezone): RedirectResponse
    {
        $timezone->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Timezone updated.'),
        ]);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Timezone $timezone): RedirectResponse
    {
        if ($timezone->countries()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This timezone cannot be deleted because it is assigned to a country.'),
            ]);

            return back();
        }

        $timezone->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Timezone deleted.'),
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
