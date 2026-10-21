<?php

namespace App\Models\Master;

use App\Enums\Master\Status;
use Database\Factories\Master\EmailTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property Status $status
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable(['code', 'name', 'description', 'status', 'sort_order'])]
class EmailType extends Model
{
    /** @use HasFactory<EmailTypeFactory> */
    use HasFactory;

    public const CODE_REGISTRATION = 'REGISTRATION';

    public const CODE_FORGOT_PASSWORD = 'FORGOT_PASSWORD';

    public const CODE_RESET_PASSWORD = 'RESET_PASSWORD';

    public const CODE_NOTIFICATION = 'NOTIFICATION';

    public const CODE_SLA_ESCALATION = 'SLA_ESCALATION';

    protected $table = 'mst_email_type';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => Status::Active->value,
        'sort_order' => 0,
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
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsToMany<EmailAccount, $this>
     */
    public function emailAccounts(): BelongsToMany
    {
        return $this->belongsToMany(EmailAccount::class, 'mst_email_account_type', 'email_type_id', 'email_account_id')
            ->withPivot('is_enabled')
            ->withTimestamps();
    }

    /**
     * @param  Builder<EmailType>  $query
     * @return Builder<EmailType>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', Status::Active);
    }

    /**
     * @param  Builder<EmailType>  $query
     * @return Builder<EmailType>
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
