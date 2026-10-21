<?php

namespace App\Models\Master;

use App\Enums\Master\Status;
use App\Models\User;
use Database\Factories\Master\RegionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $region_name
 * @property int $country_id
 * @property int $owner_id
 * @property Status $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Country $country
 * @property-read User $owner
 */
#[Fillable(['region_name', 'country_id', 'owner_id', 'status'])]
class Region extends Model
{
    /** @use HasFactory<RegionFactory> */
    use HasFactory;

    protected $table = 'mst_region';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => Status::Active->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'country_id' => 'integer',
            'owner_id' => 'integer',
            'status' => Status::class,
        ];
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<Branch, $this>
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class, 'region_id');
    }

    /**
     * @param  Builder<Region>  $query
     * @return Builder<Region>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', Status::Active);
    }

    /**
     * @param  Builder<Region>  $query
     * @return Builder<Region>
     */
    #[Scope]
    protected function search(Builder $query, string $term): Builder
    {
        if ($term === '') {
            return $query;
        }

        $escaped = addcslashes($term, '%_\\');

        return $query->where(function (Builder $query) use ($escaped): void {
            $query->whereLike('region_name', '%'.$escaped.'%')
                ->orWhereLike('status', '%'.$escaped.'%')
                ->orWhereHas('country', function ($query) use ($escaped): void {
                    $query->whereLike('name', '%'.$escaped.'%');
                })
                ->orWhereHas('owner', function ($query) use ($escaped): void {
                    $query->whereLike('name', '%'.$escaped.'%');
                });
        });
    }
}
