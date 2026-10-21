<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreStateRequest;
use App\Http\Requests\Master\UpdateStateRequest;
use App\Http\Resources\Master\StateResource;
use App\Models\Master\Country;
use App\Models\Master\State;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StateController extends Controller
{
    /**
     * @var list<string>
     */
    private const SORTABLE_COLUMNS = ['name', 'country_name', 'status', 'created_on', 'updated_on'];

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

        $query = State::query()
            ->with('country')
            ->search($filters['search']);

        if ($filters['sortBy'] === 'country_name') {
            $query->join('mst_country', 'mst_country.id', '=', 'mst_state.country_id')
                ->orderBy('mst_country.name', $filters['sortOrder'])
                ->select('mst_state.*');
        } else {
            $query->orderBy($filters['sortBy'], $filters['sortOrder']);
        }

        $states = $query
            ->orderBy('mst_state.id')
            ->paginate($filters['pageSize'])
            ->withQueryString();

        return Inertia::render('master/states/index', [
            'states' => [
                'data' => StateResource::collection($states->getCollection())->resolve(),
                'meta' => [
                    'current_page' => $states->currentPage(),
                    'last_page' => $states->lastPage(),
                    'per_page' => $states->perPage(),
                    'total' => $states->total(),
                    'from' => $states->firstItem(),
                    'to' => $states->lastItem(),
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
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStateRequest $request): RedirectResponse
    {
        State::query()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('State created.'),
        ]);

        return back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStateRequest $request, State $state): RedirectResponse
    {
        $state->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('State updated.'),
        ]);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(State $state): RedirectResponse
    {
        $state->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('State deleted.'),
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
