<?php

namespace App\Models\Master;

use App\Enums\Master\Status;
use App\Models\User;
use Database\Factories\Master\BranchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $branch_name
 * @property bool $geofencing
 * @property string|null $geofencing_range
 * @property string|null $branch_country_code
 * @property string $branch_fbcountry_code
 * @property int $branch_manager
 * @property int $country_id
 * @property int $region_id
 * @property int $group_id
 * @property string $branch_code
 * @property int|null $owner_id
 * @property int|null $man_power
 * @property int|null $retail_man_power
 * @property int $created_by
 * @property int $last_modified_by
 * @property Status $status
 * @property Carbon $created_on
 * @property Carbon $updated_on
 * @property-read User $manager
 * @property-read Country $country
 * @property-read Region $region
 * @property-read Group $group
 * @property-read User|null $owner
 * @property-read User $creator
 * @property-read User $modifier
 */
#[Fillable([
    'branch_name',
    'geofencing',
    'geofencing_range',
    'branch_country_code',
    'branch_fbcountry_code',
    'branch_manager',
    'country_id',
    'region_id',
    'group_id',
    'branch_code',
    'owner_id',
    'man_power',
    'retail_man_power',
    'created_by',
    'last_modified_by',
    'status',
])]
class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use HasFactory;

    protected $table = 'mst_branch';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => Status::Active->value,
        'geofencing' => false,
    ];

    public const CREATED_AT = 'created_on';

    public const UPDATED_AT = 'updated_on';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'geofencing' => 'boolean',
            'branch_manager' => 'integer',
            'country_id' => 'integer',
            'region_id' => 'integer',
            'group_id' => 'integer',
            'owner_id' => 'integer',
            'man_power' => 'integer',
            'retail_man_power' => 'integer',
            'created_by' => 'integer',
            'last_modified_by' => 'integer',
            'status' => Status::class,
            'created_on' => 'datetime',
            'updated_on' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'branch_manager');
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * @return BelongsTo<Region, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function modifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_modified_by');
    }

    /**
     * @param  Builder<Branch>  $query
     * @return Builder<Branch>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', Status::Active);
    }

    /**
     * @param  Builder<Branch>  $query
     * @return Builder<Branch>
     */
    #[Scope]
    protected function search(Builder $query, string $term): Builder
    {
        if ($term === '') {
            return $query;
        }

        $escaped = addcslashes($term, '%_\\');

        return $query->where(function (Builder $query) use ($escaped): void {
            $query->whereLike('branch_name', '%'.$escaped.'%')
                ->orWhereLike('branch_code', '%'.$escaped.'%')
                ->orWhereLike('status', '%'.$escaped.'%')
                ->orWhereHas('country', function ($query) use ($escaped): void {
                    $query->whereLike('name', '%'.$escaped.'%');
                })
                ->orWhereHas('region', function ($query) use ($escaped): void {
                    $query->whereLike('region_name', '%'.$escaped.'%');
                })
                ->orWhereHas('group', function ($query) use ($escaped): void {
                    $query->whereLike('group_name', '%'.$escaped.'%');
                })
                ->orWhereHas('manager', function ($query) use ($escaped): void {
                    $query->whereLike('name', '%'.$escaped.'%');
                });
        });
    }
}
