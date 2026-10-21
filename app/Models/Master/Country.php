<?php

namespace App\Models\Master;

use App\Enums\Master\CountryStatus;
use Database\Factories\Master\CountryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $sortname
 * @property string $name
 * @property string $currency
 * @property int $phonecode
 * @property int|null $timezone_id
 * @property CountryStatus $status
 * @property-read Timezone|null $timezone
 */
#[Fillable([
    'sortname',
    'name',
    'currency',
    'phonecode',
    'timezone_id',
    'status',
])]
class Country extends Model
{
    /** @use HasFactory<CountryFactory> */
    use HasFactory;

    protected $table = 'mst_country';

    public $timestamps = false;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => CountryStatus::Inactive->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phonecode' => 'integer',
            'timezone_id' => 'integer',
            'status' => CountryStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Timezone, $this>
     */
    public function timezone(): BelongsTo
    {
        return $this->belongsTo(Timezone::class, 'timezone_id');
    }

    /**
     * @return HasMany<State, $this>
     */
    public function states(): HasMany
    {
        return $this->hasMany(State::class, 'country_id');
    }

    /**
     * @return HasMany<City, $this>
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'country_id');
    }

    /**
     * @return HasMany<Region, $this>
     */
    public function regions(): HasMany
    {
        return $this->hasMany(Region::class, 'country_id');
    }

    /**
     * @return HasMany<Branch, $this>
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class, 'country_id');
    }

    /**
     * @param  Builder<Country>  $query
     * @return Builder<Country>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', CountryStatus::Active);
    }

    /**
     * @param  Builder<Country>  $query
     * @return Builder<Country>
     */
    #[Scope]
    protected function search(Builder $query, string $term): Builder
    {
        if ($term === '') {
            return $query;
        }

        $escaped = addcslashes($term, '%_\\');

        return $query->where(function (Builder $query) use ($escaped): void {
            $query->whereLike('name', '%'.$escaped.'%')
                ->orWhereLike('sortname', '%'.$escaped.'%')
                ->orWhereLike('currency', '%'.$escaped.'%')
                ->orWhereLike('status', '%'.$escaped.'%')
                ->orWhereHas('timezone', function ($query) use ($escaped): void {
                    $query->whereLike('name', '%'.$escaped.'%')
                        ->orWhereLike('display_name', '%'.$escaped.'%');
                });
        });
    }
}
