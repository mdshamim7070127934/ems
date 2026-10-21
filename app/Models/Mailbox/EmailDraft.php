<?php

namespace App\Models\Mailbox;

use App\Policies\Mailbox\EmailDraftPolicy;
use Database\Factories\Mailbox\EmailDraftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $account_id
 * @property int|null $thread_id
 * @property string|null $in_reply_to
 * @property list<string>|null $to_emails
 * @property list<string>|null $cc_emails
 * @property list<string>|null $bcc_emails
 * @property string|null $subject
 * @property string|null $body_html
 * @property string|null $body_plain
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable([
    'account_id',
    'thread_id',
    'in_reply_to',
    'to_emails',
    'cc_emails',
    'bcc_emails',
    'subject',
    'body_html',
    'body_plain',
])]
#[UsePolicy(EmailDraftPolicy::class)]
class EmailDraft extends Model
{
    /** @use HasFactory<EmailDraftFactory> */
    use HasFactory;

    protected $table = 'email_drafts';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'to_emails' => 'array',
            'cc_emails' => 'array',
            'bcc_emails' => 'array',
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
     * @return HasMany<DraftAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(DraftAttachment::class, 'draft_id');
    }

    /**
     * @param  Builder<EmailDraft>  $query
     * @return Builder<EmailDraft>
     */
    #[Scope]
    protected function search(Builder $query, string $term): Builder
    {
        if ($term === '') {
            return $query;
        }

        $escaped = addcslashes($term, '%_\\');
        $like = '%'.$escaped.'%';

        return $query->where(function (Builder $drafts) use ($like): void {
            $drafts->whereLike('subject', $like)
                ->orWhereLike('body_plain', $like)
                ->orWhereLike('to_emails', $like)
                ->orWhereLike('cc_emails', $like)
                ->orWhereLike('bcc_emails', $like);
        });
    }
}
