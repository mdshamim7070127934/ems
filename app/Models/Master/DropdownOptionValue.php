<?php

namespace App\Models\Master;

use App\Enums\Master\Status;
use Database\Factories\Master\DropdownOptionValueFactory;
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
 * @property int $option_type_id
 * @property int $module_id
 * @property int $parent_id
 * @property string $option_value
 * @property int|null $position
 * @property string|null $user_access_id
 * @property string|null $master_type
 * @property Status $status
 * @property Carbon $created_on
 * @property Carbon $updated_on
 * @property-read Module $module
 * @property-read Dropdown $dropdown
 * @property-read DropdownOptionValue|null $parent
 */
#[Fillable([
    'option_type_id',
    'module_id',
    'parent_id',
    'option_value',
    'position',
    'user_access_id',
    'master_type',
    'status',
])]
class DropdownOptionValue extends Model
{
    /** @use HasFactory<DropdownOptionValueFactory> */
    use HasFactory;

    protected $table = 'mst_dropdown_option_values';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'parent_id' => 0,
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
     * @return BelongsTo<Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_id', 'module_id');
    }

    /**
     * @return BelongsTo<Dropdown, $this>
     */
    public function dropdown(): BelongsTo
    {
        return $this->belongsTo(Dropdown::class, 'option_type_id', 'option_type_id');
    }

    /**
     * @return BelongsTo<DropdownOptionValue, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<DropdownOptionValue, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @param  Builder<DropdownOptionValue>  $query
     * @return Builder<DropdownOptionValue>
     */
    #[Scope]
    protected function search(Builder $query, string $term): Builder
    {
        if ($term === '') {
            return $query;
        }

        $escaped = addcslashes($term, '%_\\');

        return $query->where(function (Builder $query) use ($escaped): void {
            $query->whereLike('option_value', '%'.$escaped.'%')
                ->orWhereLike('status', '%'.$escaped.'%')
                ->orWhereLike('master_type', '%'.$escaped.'%')
                ->orWhereHas('dropdown', function ($query) use ($escaped): void {
                    $query->whereLike('name', '%'.$escaped.'%');
                })
                ->orWhereHas('module', function ($query) use ($escaped): void {
                    $query->whereLike('module_name', '%'.$escaped.'%');
                });
        });
    }
}
