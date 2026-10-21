<?php

namespace App\Models\Mailbox;

use App\Enums\Master\Status;
use App\Models\User;
use App\Policies\Mailbox\EmailSignaturePolicy;
use Database\Factories\Mailbox\EmailSignatureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $signature_name
 * @property string|null $signature_html
 * @property bool $is_default
 * @property Status $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 */
#[Fillable([
    'user_id',
    'signature_name',
    'signature_html',
    'is_default',
    'status',
])]
#[UsePolicy(EmailSignaturePolicy::class)]
class EmailSignature extends Model
{
    /** @use HasFactory<EmailSignatureFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_default' => false,
        'status' => Status::Active->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'is_default' => 'boolean',
            'status' => Status::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<EmailSignature>  $query
     * @return Builder<EmailSignature>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', Status::Active);
    }

    /**
     * @param  Builder<EmailSignature>  $query
     * @return Builder<EmailSignature>
     */
    #[Scope]
    protected function search(Builder $query, string $term): Builder
    {
        if ($term === '') {
            return $query;
        }

        $escaped = addcslashes($term, '%_\\');

        return $query->where(function (Builder $query) use ($escaped): void {
            $query->whereLike('signature_name', '%'.$escaped.'%')
                ->orWhereLike('status', '%'.$escaped.'%');
        });
    }
}
