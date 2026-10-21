<?php

namespace App\Models\Master;

use App\Enums\Master\Status;
use Database\Factories\Master\CityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $country_id
 * @property int $state_id
 * @property string $name
 * @property Status $status
 * @property Carbon $created_on
 * @property Carbon $updated_on
 * @property-read Country $country
 * @property-read State $state
 */
#[Fillable(['country_id', 'state_id', 'name', 'status'])]
class City extends Model
{
    /** @use HasFactory<CityFactory> */
    use HasFactory;

    protected $table = 'mst_city';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => Status::Active->value,
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
            'status' => Status::class,
            'created_on' => 'datetime',
            'updated_on' => 'datetime',
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
     * @return BelongsTo<State, $this>
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    /**
     * @param  Builder<City>  $query
     * @return Builder<City>
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
                ->orWhereLike('status', '%'.$escaped.'%')
                ->orWhereHas('country', function ($query) use ($escaped): void {
                    $query->whereLike('name', '%'.$escaped.'%');
                })
                ->orWhereHas('state', function ($query) use ($escaped): void {
                    $query->whereLike('name', '%'.$escaped.'%');
                });
        });
    }
}
