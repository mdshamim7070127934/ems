<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreCountryRequest;
use App\Http\Requests\Master\UpdateCountryRequest;
use App\Http\Resources\Master\CountryResource;
use App\Models\Master\Country;
use App\Models\Master\Timezone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CountryController extends Controller
{
    /**
     * @var list<string>
     */
    private const SORTABLE_COLUMNS = ['name', 'sortname', 'currency', 'phonecode', 'timezone_name', 'status'];

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

        $query = Country::query()
            ->with('timezone')
            ->search($filters['search']);

        if ($filters['sortBy'] === 'timezone_name') {
            $query->leftJoin('mst_timezone', 'mst_timezone.id', '=', 'mst_country.timezone_id')
                ->orderBy('mst_timezone.name', $filters['sortOrder'])
                ->select('mst_country.*');
        } else {
            $query->orderBy($filters['sortBy'], $filters['sortOrder']);
        }

        $countries = $query
            ->orderBy('mst_country.id')
            ->paginate($filters['pageSize'])
            ->withQueryString();

        return Inertia::render('master/countries/index', [
            'countries' => [
                'data' => CountryResource::collection($countries->getCollection())->resolve(),
                'meta' => [
                    'current_page' => $countries->currentPage(),
                    'last_page' => $countries->lastPage(),
                    'per_page' => $countries->perPage(),
                    'total' => $countries->total(),
                    'from' => $countries->firstItem(),
                    'to' => $countries->lastItem(),
                ],
            ],
            'filters' => $filters,
            'timezoneOptions' => Timezone::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'display_name', 'utc_offset'])
                ->map(fn (Timezone $timezone): array => [
                    'id' => $timezone->id,
                    'name' => $timezone->name,
                    'display_name' => $timezone->display_name,
                    'utc_offset' => now($timezone->name)->format('P'),
                ])
                ->values()
                ->all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCountryRequest $request): RedirectResponse
    {
        Country::query()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Country created.'),
        ]);

        return back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCountryRequest $request, Country $country): RedirectResponse
    {
        $country->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Country updated.'),
        ]);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Country $country): RedirectResponse
    {
        $country->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Country deleted.'),
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
            'sortBy' => in_array($sortBy, self::SORTABLE_COLUMNS, true) ? $sortBy : 'name',
            'sortOrder' => $sortOrder,
            'page' => $page > 0 ? $page : 1,
            'pageSize' => in_array($pageSize, self::PAGE_SIZES, true) ? $pageSize : 10,
        ];
    }
}
