<?php

namespace App\Models\Master;

use App\Enums\Master\EditorDriver;
use App\Enums\Master\Status;
use Database\Factories\Master\EditorSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property EditorDriver $code
 * @property string $name
 * @property string|null $description
 * @property bool $is_default
 * @property Status $status
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable(['code', 'name', 'description', 'is_default', 'status', 'sort_order'])]
class EditorSetting extends Model
{
    /** @use HasFactory<EditorSettingFactory> */
    use HasFactory;

    protected $table = 'mst_editor_setting';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_default' => false,
        'status' => Status::Active->value,
        'sort_order' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'code' => EditorDriver::class,
            'is_default' => 'boolean',
            'status' => Status::class,
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<EditorSetting>  $query
     * @return Builder<EditorSetting>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', Status::Active);
    }

    /**
     * @param  Builder<EditorSetting>  $query
     * @return Builder<EditorSetting>
     */
    #[Scope]
    protected function search(Builder $query, string $term): Builder
    {
        if ($term === '') {
            return $query;
        }

        $escaped = addcslashes($term, '%_\\');

        return $query->where(function (Builder $query) use ($escaped): void {
            $query->whereLike('code', '%'.$escaped.'%')
                ->orWhereLike('name', '%'.$escaped.'%')
                ->orWhereLike('description', '%'.$escaped.'%')
                ->orWhereLike('status', '%'.$escaped.'%');
        });
    }
}
