<?php

namespace App\Models\Master;

use App\Enums\Master\Status;
use Database\Factories\Master\ModuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $module_id
 * @property string $module_name
 * @property Status $status
 * @property Carbon $created_on
 * @property Carbon $updated_on
 */
#[Fillable(['module_name', 'status'])]
class Module extends Model
{
    /** @use HasFactory<ModuleFactory> */
    use HasFactory;

    protected $table = 'mst_module';

    protected $primaryKey = 'module_id';

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
     * @return HasMany<Dropdown, $this>
     */
    public function dropdowns(): HasMany
    {
        return $this->hasMany(Dropdown::class, 'module_id', 'module_id');
    }

    /**
     * @return HasMany<DropdownOptionValue, $this>
     */
    public function optionValues(): HasMany
    {
        return $this->hasMany(DropdownOptionValue::class, 'module_id', 'module_id');
    }

    /**
     * @param  Builder<Module>  $query
     * @return Builder<Module>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', Status::Active);
    }

    /**
     * @param  Builder<Module>  $query
     * @return Builder<Module>
     */
    #[Scope]
    protected function search(Builder $query, string $term): Builder
    {
        if ($term === '') {
            return $query;
        }

        $escaped = addcslashes($term, '%_\\');

        return $query->where(function (Builder $query) use ($escaped): void {
            $query->whereLike('module_name', '%'.$escaped.'%')
                ->orWhereLike('status', '%'.$escaped.'%');
        });
    }
}
