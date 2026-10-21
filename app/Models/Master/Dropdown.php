<?php

namespace App\Models\Master;

use App\Enums\Master\Status;
use Database\Factories\Master\DropdownFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $option_type_id
 * @property int $module_id
 * @property string $name
 * @property Status $status
 * @property Carbon $created_on
 * @property Carbon $updated_on
 * @property-read Module $module
 */
#[Fillable(['module_id', 'name', 'status'])]
class Dropdown extends Model
{
    /** @use HasFactory<DropdownFactory> */
    use HasFactory;

    protected $table = 'mst_dropdown';

    protected $primaryKey = 'option_type_id';

    /**
     * @var array<string, string>
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
     * @return BelongsTo<Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_id', 'module_id');
    }

    /**
     * @return HasMany<DropdownOptionValue, $this>
     */
    public function optionValues(): HasMany
    {
        return $this->hasMany(DropdownOptionValue::class, 'option_type_id', 'option_type_id');
    }

    /**
     * @param  Builder<Dropdown>  $query
     * @return Builder<Dropdown>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', Status::Active);
    }

    /**
     * @param  Builder<Dropdown>  $query
     * @return Builder<Dropdown>
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
                ->orWhereHas('module', function ($query) use ($escaped): void {
                    $query->whereLike('module_name', '%'.$escaped.'%');
                });
        });
    }
}
