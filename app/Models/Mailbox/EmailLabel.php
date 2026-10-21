<?php

namespace App\Models\Mailbox;

use App\Models\User;
use App\Policies\Mailbox\EmailLabelPolicy;
use Database\Factories\Mailbox\EmailLabelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int|null $account_id
 * @property string $name
 * @property string $slug
 * @property string|null $color
 * @property Carbon|null $created_at
 */
#[Fillable([
    'user_id',
    'account_id',
    'name',
    'slug',
    'color',
])]
#[UsePolicy(EmailLabelPolicy::class)]
class EmailLabel extends Model
{
    /** @use HasFactory<EmailLabelFactory> */
    use HasFactory;

    protected $table = 'email_labels';

    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<EmailAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class, 'account_id');
    }

    /**
     * @return BelongsToMany<Email, $this>
     */
    public function emails(): BelongsToMany
    {
        return $this->belongsToMany(Email::class, 'email_label_mappings', 'label_id', 'email_id')
            ->withPivot('created_at');
    }
}
