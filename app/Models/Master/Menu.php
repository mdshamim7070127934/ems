<?php

namespace App\Models\Master;

use App\Enums\Master\Status;
use Database\Factories\Master\MenuFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $menu_parent_id
 * @property string $menu_name
 * @property string|null $column_description
 * @property string $menu_route
 * @property int $menu_order
 * @property string|null $screen_name
 * @property string|null $web_icon
 * @property string|null $mobile_icon
 * @property Status $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Menu|null $parent
 */
#[Fillable([
    'menu_parent_id',
    'menu_name',
    'column_description',
    'menu_route',
    'menu_order',
    'screen_name',
    'web_icon',
    'mobile_icon',
    'status',
])]
class Menu extends Model
{
    /** @use HasFactory<MenuFactory> */
    use HasFactory;

    protected $table = 'mst_menu';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'menu_parent_id' => 0,
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
            'status' => Status::class,
        ];
    }

    /**
     * @return BelongsTo<Menu, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'menu_parent_id');
    }

    /**
     * @return HasMany<Menu, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'menu_parent_id');
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_menu_permissions', 'menu_id', 'role_id')
            ->withPivot('can_access');
    }

    /**
     * @param  Builder<Menu>  $query
     * @return Builder<Menu>
     */
    #[Scope]
    protected function search(Builder $query, string $term): Builder
    {
        if ($term === '') {
            return $query;
        }

        $escaped = addcslashes($term, '%_\\');

        return $query->where(function (Builder $query) use ($escaped): void {
            $query->whereLike('menu_name', '%'.$escaped.'%')
                ->orWhereLike('menu_route', '%'.$escaped.'%')
                ->orWhereLike('screen_name', '%'.$escaped.'%')
                ->orWhereLike('status', '%'.$escaped.'%')
                ->orWhereHas('parent', function ($query) use ($escaped): void {
                    $query->whereLike('menu_name', '%'.$escaped.'%');
                });
        });
    }
}
