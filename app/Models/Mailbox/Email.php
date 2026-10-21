<?php

namespace App\Models\Mailbox;

use App\Policies\Mailbox\EmailPolicy;
use Database\Factories\Mailbox\EmailFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $account_id
 * @property int|null $thread_id
 * @property string $message_id
 * @property string|null $provider_message_id
 * @property int|null $folder_id
 * @property string $from_email
 * @property string|null $from_name
 * @property list<string>|null $to_emails
 * @property list<string>|null $cc_emails
 * @property list<string>|null $bcc_emails
 * @property string|null $reply_to
 * @property string $subject
 * @property string|null $snippet
 * @property string|null $body_html
 * @property string|null $body_plain
 * @property Carbon|null $internal_date
 * @property bool $is_unread
 * @property bool $is_starred
 * @property bool $is_draft
 * @property string|null $in_reply_to
 * @property string|null $references
 * @property Carbon $created_at
 * @property bool $attachments_exists
 * @property Carbon $updated_at
 */
#[Fillable([
    'account_id',
    'thread_id',
    'message_id',
    'provider_message_id',
    'folder_id',
    'from_email',
    'from_name',
    'to_emails',
    'cc_emails',
    'bcc_emails',
    'reply_to',
    'subject',
    'snippet',
    'body_html',
    'body_plain',
    'internal_date',
    'is_unread',
    'is_starred',
    'is_draft',
    'in_reply_to',
    'references',
])]
#[UsePolicy(EmailPolicy::class)]
class Email extends Model
{
    /** @use HasFactory<EmailFactory> */
    use HasFactory;

    protected $table = 'emails';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'subject' => '',
        'is_unread' => true,
        'is_starred' => false,
        'is_draft' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'to_emails' => 'array',
            'cc_emails' => 'array',
            'bcc_emails' => 'array',
            'internal_date' => 'datetime',
            'is_unread' => 'boolean',
            'is_starred' => 'boolean',
            'is_draft' => 'boolean',
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
     * @return BelongsTo<EmailThread, $this>
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(EmailThread::class, 'thread_id');
    }

    /**
     * @return BelongsTo<EmailFolder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(EmailFolder::class, 'folder_id');
    }

    /**
     * @return HasMany<EmailAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(EmailAttachment::class, 'email_id');
    }

    /**
     * @return BelongsToMany<EmailLabel, $this>
     */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(EmailLabel::class, 'email_label_mappings', 'email_id', 'label_id')
            ->withPivot('created_at');
    }

    /**
     * @param  Builder<Email>  $query
     * @return Builder<Email>
     */
    public function scopeNotDraft(Builder $query): Builder
    {
        return $query->where('is_draft', false);
    }
}
