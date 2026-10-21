<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreCityRequest;
use App\Http\Requests\Master\UpdateCityRequest;
use App\Http\Resources\Master\CityResource;
use App\Models\Master\City;
use App\Models\Master\Country;
use App\Models\Master\State;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CityController extends Controller
{
    /**
     * @var list<string>
     */
    private const SORTABLE_COLUMNS = ['name', 'country_name', 'state_name', 'status', 'created_on', 'updated_on'];

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

        $query = City::query()
            ->with(['country', 'state'])
            ->search($filters['search']);

        if ($filters['sortBy'] === 'country_name') {
            $query->join('mst_country', 'mst_country.id', '=', 'mst_city.country_id')
                ->orderBy('mst_country.name', $filters['sortOrder'])
                ->select('mst_city.*');
        } elseif ($filters['sortBy'] === 'state_name') {
            $query->join('mst_state', 'mst_state.id', '=', 'mst_city.state_id')
                ->orderBy('mst_state.name', $filters['sortOrder'])
                ->select('mst_city.*');
        } else {
            $query->orderBy($filters['sortBy'], $filters['sortOrder']);
        }

        $cities = $query
            ->orderBy('mst_city.id')
            ->paginate($filters['pageSize'])
            ->withQueryString();

        return Inertia::render('master/cities/index', [
            'cities' => [
                'data' => CityResource::collection($cities->getCollection())->resolve(),
                'meta' => [
                    'current_page' => $cities->currentPage(),
                    'last_page' => $cities->lastPage(),
                    'per_page' => $cities->perPage(),
                    'total' => $cities->total(),
                    'from' => $cities->firstItem(),
                    'to' => $cities->lastItem(),
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
            'stateOptions' => State::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'country_id', 'name'])
                ->map(fn (State $state): array => [
                    'id' => $state->id,
                    'country_id' => $state->country_id,
                    'name' => $state->name,
                ])
                ->values()
                ->all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCityRequest $request): RedirectResponse
    {
        City::query()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('City created.'),
        ]);

        return back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCityRequest $request, City $city): RedirectResponse
    {
        $city->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('City updated.'),
        ]);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(City $city): RedirectResponse
    {
        $city->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('City deleted.'),
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
