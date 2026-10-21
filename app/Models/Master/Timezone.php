<?php

namespace App\Models\Master;

use App\Enums\Master\Status;
use Database\Factories\Master\TimezoneFactory;
use DateTimeZone;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $utc_offset
 * @property string $display_name
 * @property Status $status
 * @property Carbon $created_on
 * @property Carbon $updated_on
 */
#[Fillable(['name', 'code', 'utc_offset', 'display_name', 'status'])]
class Timezone extends Model
{
    /** @use HasFactory<TimezoneFactory> */
    use HasFactory;

    protected $table = 'mst_timezone';

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
     * @return list<string>
     */
    public static function identifiers(): array
    {
        return DateTimeZone::listIdentifiers();
    }

    /**
     * Build persisted attributes for a canonical IANA timezone identifier.
     *
     * utc_offset is the current offset at seed/save time for display and
     * search only. Date/time conversion must use the IANA name.
     *
     * @return array{name: string, code: string, utc_offset: string, display_name: string}
     */
    public static function attributesFor(string $name): array
    {
        return [
            'name' => $name,
            'code' => strtoupper(str_replace(['/', '-'], '_', $name)),
            'utc_offset' => now($name)->format('P'),
            'display_name' => str_replace('_', ' ', $name),
        ];
    }

    /**
     * @return HasMany<Country, $this>
     */
    public function countries(): HasMany
    {
        return $this->hasMany(Country::class, 'timezone_id');
    }

    /**
     * @param  Builder<Timezone>  $query
     * @return Builder<Timezone>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', Status::Active);
    }

    /**
     * @param  Builder<Timezone>  $query
     * @return Builder<Timezone>
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
                ->orWhereLike('code', '%'.$escaped.'%')
                ->orWhereLike('display_name', '%'.$escaped.'%')
                ->orWhereLike('utc_offset', '%'.$escaped.'%')
                ->orWhereLike('status', '%'.$escaped.'%');
        });
    }
}
