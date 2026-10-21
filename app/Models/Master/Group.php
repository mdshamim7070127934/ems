<?php

namespace App\Models\Master;

use App\Enums\Master\Status;
use App\Models\User;
use Database\Factories\Master\GroupFactory;
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
 * @property string $group_name
 * @property string $description
 * @property int $owner_id
 * @property Status $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $owner
 */
#[Fillable(['group_name', 'description', 'owner_id', 'status'])]
class Group extends Model
{
    /** @use HasFactory<GroupFactory> */
    use HasFactory;

    protected $table = 'mst_group';

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
            'owner_id' => 'integer',
            'status' => Status::class,
        ];
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
        return $this->hasMany(Branch::class, 'group_id');
    }

    /**
     * @param  Builder<Group>  $query
     * @return Builder<Group>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', Status::Active);
    }

    /**
     * @param  Builder<Group>  $query
     * @return Builder<Group>
     */
    #[Scope]
    protected function search(Builder $query, string $term): Builder
    {
        if ($term === '') {
            return $query;
        }

        $escaped = addcslashes($term, '%_\\');

        return $query->where(function (Builder $query) use ($escaped): void {
            $query->whereLike('group_name', '%'.$escaped.'%')
                ->orWhereLike('description', '%'.$escaped.'%')
                ->orWhereLike('status', '%'.$escaped.'%')
                ->orWhereHas('owner', function ($query) use ($escaped): void {
                    $query->whereLike('name', '%'.$escaped.'%');
                });
        });
    }
}
