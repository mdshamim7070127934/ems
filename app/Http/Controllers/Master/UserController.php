<?php

namespace App\Http\Controllers\Master;

use App\Enums\Master\Status;
use App\Events\UserRegistered;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreUserRequest;
use App\Http\Requests\Master\UpdateUserRequest;
use App\Http\Resources\Master\UserResource;
use App\Models\Master\Branch;
use App\Models\Master\Group;
use App\Models\Master\Region;
use App\Models\Master\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * @var list<string>
     */
    private const SORTABLE_COLUMNS = [
        'first_name',
        'last_name',
        'email',
        'role_name',
        'branch_name',
        'reporting_person_name',
        'status',
        'created_at',
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

        $query = User::query()
            ->with(['role', 'branch', 'reportingPerson'])
            ->search($filters['search']);

        $query = $this->applySort($query, $filters['sortBy'], $filters['sortOrder']);

        $users = $query
            ->orderBy('users.id')
            ->paginate($filters['pageSize'])
            ->withQueryString();

        $this->decorateBranchNames($users->getCollection());

        return Inertia::render('master/users/index', [
            'users' => [
                'data' => UserResource::collection($users->getCollection())->resolve(),
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                ],
            ],
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        return Inertia::render('master/users/form', [
            'user' => null,
            'roleOptions' => $this->roleOptions(),
            'branchOptions' => $this->branchOptions(),
            'reportingPersonOptions' => $this->reportingPersonOptions(),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $this->userPayload($request->safe()->only([
            'first_name',
            'last_name',
            'email',
            'password',
            'role_id',
            'branch_id',
            'reporting_person_id',
            'extra_branch_ids',
            'exclude_branch_ids',
            'status',
        ]));

        $data['username'] = $this->uniqueUsername($data['email']);
        $data['created_by'] = $request->user()?->id;
        $data['last_modified_by'] = $request->user()?->id;
        $data['is_available'] = true;

        $user = User::query()->create($data);

        UserRegistered::dispatch($user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('User created.'),
        ]);

        return to_route('master.users.index');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): Response
    {
        $user->load(['role', 'branch', 'reportingPerson']);
        $this->decorateBranchNames(collect([$user]));

        return Inertia::render('master/users/show', [
            'user' => (new UserResource($user))->resolve(),
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): Response
    {
        $user->load(['role', 'branch', 'reportingPerson']);
        $this->decorateBranchNames(collect([$user]));

        return Inertia::render('master/users/form', [
            'user' => (new UserResource($user))->resolve(),
            'roleOptions' => $this->roleOptions(),
            'branchOptions' => $this->branchOptions(),
            'reportingPersonOptions' => $this->reportingPersonOptions($user->id),
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $this->userPayload($request->safe()->only([
            'first_name',
            'last_name',
            'email',
            'role_id',
            'branch_id',
            'reporting_person_id',
            'extra_branch_ids',
            'exclude_branch_ids',
            'status',
        ]));

        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
        }

        if ($user->username === null || $user->username === '') {
            $data['username'] = $this->uniqueUsername($data['email'], $user->id);
        }

        $data['last_modified_by'] = $request->user()?->id;

        $user->update($data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('User updated.'),
        ]);

        return to_route('master.users.index');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->is($user)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('You cannot delete your own account.'),
            ]);

            return back();
        }

        if ($user->reportees()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Cannot delete user: other users report to this person.'),
            ]);

            return back();
        }

        if ($this->userIsReferenced($user)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Cannot delete user: they are assigned to a branch, group, or region.'),
            ]);

            return back();
        }

        $user->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('User deleted.'),
        ]);

        return back();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function userPayload(array $data): array
    {
        $data['name'] = trim($data['first_name'].' '.$data['last_name']);
        $data['status'] = Status::from($data['status']);
        $data['extra_branch_ids'] = array_values(array_map(intval(...), $data['extra_branch_ids'] ?? []));
        $data['exclude_branch_ids'] = array_values(array_map(intval(...), $data['exclude_branch_ids'] ?? []));

        return $data;
    }

    /**
     * @param  Builder<User>  $query
     * @param  'asc'|'desc'  $sortOrder
     * @return Builder<User>
     */
    private function applySort(Builder $query, string $sortBy, string $sortOrder): Builder
    {
        $direction = $sortOrder === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'role_name') {
            return $query->leftJoin('roles', 'roles.id', '=', 'users.role_id')
                ->orderBy('roles.display_name', $direction)
                ->select('users.*');
        }

        if ($sortBy === 'branch_name') {
            return $query->leftJoin('mst_branch', 'mst_branch.id', '=', 'users.branch_id')
                ->orderBy('mst_branch.branch_name', $direction)
                ->select('users.*');
        }

        if ($sortBy === 'reporting_person_name') {
            return $query->leftJoin('users as reporting_users', 'reporting_users.id', '=', 'users.reporting_person_id')
                ->orderBy('reporting_users.name', $direction)
                ->select('users.*');
        }

        return $query->orderBy($sortBy, $direction);
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function decorateBranchNames(Collection $users): void
    {
        $ids = $users
            ->flatMap(fn (User $user): array => array_merge(
                $this->intIds($user->extra_branch_ids),
                $this->intIds($user->exclude_branch_ids),
            ))
            ->unique()
            ->values()
            ->all();

        $names = $ids === []
            ? collect()
            : Branch::query()->whereIn('id', $ids)->pluck('branch_name', 'id');

        $users->each(function (User $user) use ($names): void {
            $user->setAttribute('extra_branch_names', $this->joinNames($user->extra_branch_ids, $names));
            $user->setAttribute('exclude_branch_names', $this->joinNames($user->exclude_branch_ids, $names));
        });
    }

    /**
     * @param  list<int>|null  $ids
     * @param  Collection<int|string, string>  $names
     */
    private function joinNames(?array $ids, Collection $names): string
    {
        return collect($this->intIds($ids))
            ->map(fn (int $id): ?string => $names->get($id))
            ->filter()
            ->implode(', ');
    }

    /**
     * @param  list<int>|null  $ids
     * @return list<int>
     */
    private function intIds(?array $ids): array
    {
        return array_values(array_unique(array_filter(
            array_map(intval(...), $ids ?? []),
            fn (int $id): bool => $id > 0,
        )));
    }

    private function uniqueUsername(string $email, ?int $ignoreId = null): string
    {
        $local = Str::lower(Str::before($email, '@'));
        $base = preg_replace('/[^a-z0-9._-]/', '', $local) ?: 'user';
        $username = $base;
        $suffix = 1;

        while (User::query()
            ->where('username', $username)
            ->when($ignoreId !== null, fn (Builder $query): Builder => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $username = $base.$suffix;
            $suffix++;
        }

        return $username;
    }

    private function userIsReferenced(User $user): bool
    {
        $usedByBranch = Branch::query()
            ->where(function (Builder $query) use ($user): void {
                $query->where('branch_manager', $user->id)
                    ->orWhere('owner_id', $user->id)
                    ->orWhere('created_by', $user->id)
                    ->orWhere('last_modified_by', $user->id);
            })
            ->exists();

        return $usedByBranch
            || Group::query()->where('owner_id', $user->id)->exists()
            || Region::query()->where('owner_id', $user->id)->exists();
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
    private function roleOptions(): array
    {
        return Role::query()
            ->orderBy('display_name')
            ->get(['id', 'display_name'])
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->display_name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function branchOptions(): array
    {
        return Branch::query()
            ->orderBy('branch_name')
            ->get(['id', 'branch_name'])
            ->map(fn (Branch $branch): array => [
                'id' => $branch->id,
                'name' => $branch->branch_name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function reportingPersonOptions(?int $excludeId = null): array
    {
        return User::query()
            ->when($excludeId !== null, fn (Builder $query): Builder => $query->whereKeyNot($excludeId))
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name.' ('.$user->email.')',
            ])
            ->values()
            ->all();
    }
}
