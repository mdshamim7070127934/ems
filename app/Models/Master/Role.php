<?php

namespace App\Models\Master;

use App\Models\User;
use Database\Factories\Master\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $role_name
 * @property string $display_name
 * @property bool $is_active
 * @property bool $is_super_admin
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable(['role_name', 'display_name', 'is_active'])]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    protected $table = 'roles';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'is_super_admin' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_super_admin' => 'boolean',
        ];
    }

    /**
     * Super administrator roles cannot be edited or deleted.
     */
    public function isProtected(): bool
    {
        return $this->is_super_admin || strtolower($this->role_name) === 'admin';
    }

    /**
     * @return BelongsToMany<Menu, $this>
     */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'role_menu_permissions', 'role_id', 'menu_id')
            ->withPivot('can_access');
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Replace the menus this role can access.
     *
     * @param  list<int>  $menuIds
     */
    public function syncMenuPermissions(array $menuIds): void
    {
        $permissions = [];

        foreach (array_unique($menuIds) as $menuId) {
            if ($menuId <= 0) {
                continue;
            }

            $permissions[$menuId] = ['can_access' => true];
        }

        $this->menus()->sync($permissions);
    }

    /**
     * @param  Builder<Role>  $query
     * @return Builder<Role>
     */
    #[Scope]
    protected function search(Builder $query, string $term): Builder
    {
        if ($term === '') {
            return $query;
        }

        $escaped = addcslashes($term, '%_\\');

        return $query->where(function (Builder $query) use ($escaped): void {
            $query->whereLike('role_name', '%'.$escaped.'%')
                ->orWhereLike('display_name', '%'.$escaped.'%');
        });
    }
}
