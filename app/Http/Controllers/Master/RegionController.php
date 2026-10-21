<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreRegionRequest;
use App\Http\Requests\Master\UpdateRegionRequest;
use App\Http\Resources\Master\RegionResource;
use App\Models\Master\Country;
use App\Models\Master\Region;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegionController extends Controller
{
    /**
     * @var list<string>
     */
    private const SORTABLE_COLUMNS = ['region_name', 'country_name', 'owner_name', 'status', 'created_at', 'updated_at'];

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

        $query = Region::query()
            ->with(['country', 'owner'])
            ->search($filters['search']);

        if ($filters['sortBy'] === 'country_name') {
            $query->join('mst_country', 'mst_country.id', '=', 'mst_region.country_id')
                ->orderBy('mst_country.name', $filters['sortOrder'])
                ->select('mst_region.*');
        } elseif ($filters['sortBy'] === 'owner_name') {
            $query->join('users', 'users.id', '=', 'mst_region.owner_id')
                ->orderBy('users.name', $filters['sortOrder'])
                ->select('mst_region.*');
        } else {
            $query->orderBy($filters['sortBy'], $filters['sortOrder']);
        }

        $regions = $query
            ->orderBy('mst_region.id')
            ->paginate($filters['pageSize'])
            ->withQueryString();

        return Inertia::render('master/regions/index', [
            'regions' => [
                'data' => RegionResource::collection($regions->getCollection())->resolve(),
                'meta' => [
                    'current_page' => $regions->currentPage(),
                    'last_page' => $regions->lastPage(),
                    'per_page' => $regions->perPage(),
                    'total' => $regions->total(),
                    'from' => $regions->firstItem(),
                    'to' => $regions->lastItem(),
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
            'userOptions' => $this->userOptions(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRegionRequest $request): RedirectResponse
    {
        Region::query()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Region created.'),
        ]);

        return back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRegionRequest $request, Region $region): RedirectResponse
    {
        $region->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Region updated.'),
        ]);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Region $region): RedirectResponse
    {
        if ($region->branches()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This region cannot be deleted because it is assigned to a branch.'),
            ]);

            return back();
        }

        $region->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Region deleted.'),
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
