<?php

namespace App\Models\Mailbox;

use App\Policies\Mailbox\EmailThreadPolicy;
use Database\Factories\Mailbox\EmailThreadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $account_id
 * @property string $thread_id
 * @property string|null $subject
 * @property string|null $snippet
 * @property Carbon|null $last_message_at
 * @property int $message_count
 * @property bool $is_starred
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable([
    'account_id',
    'thread_id',
    'subject',
    'snippet',
    'last_message_at',
    'message_count',
    'is_starred',
])]
#[UsePolicy(EmailThreadPolicy::class)]
class EmailThread extends Model
{
    /** @use HasFactory<EmailThreadFactory> */
    use HasFactory;

    protected $table = 'email_threads';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'message_count' => 0,
        'is_starred' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'message_count' => 'integer',
            'is_starred' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<EmailAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class, 'account_id');
    }

    /**
     * @return HasMany<Email, $this>
     */
    public function emails(): HasMany
    {
        return $this->hasMany(Email::class, 'thread_id');
    }
}
