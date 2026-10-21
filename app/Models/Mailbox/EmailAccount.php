<?php

namespace App\Models\Mailbox;

use App\Enums\Mailbox\MailboxProvider;
use App\Enums\Master\SmtpEncryption;
use App\Models\User;
use App\Policies\Mailbox\EmailAccountPolicy;
use Database\Factories\Mailbox\EmailAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property MailboxProvider $provider
 * @property string $email
 * @property string|null $display_name
 * @property bool $is_default
 * @property bool $is_active
 * @property string|null $access_token
 * @property string|null $refresh_token
 * @property Carbon|null $token_expiry
 * @property string|null $smtp_host
 * @property int|null $smtp_port
 * @property string|null $smtp_username
 * @property string|null $smtp_password
 * @property SmtpEncryption|null $smtp_encryption
 * @property string|null $imap_host
 * @property int|null $imap_port
 * @property string|null $imap_username
 * @property string|null $imap_password
 * @property SmtpEncryption|null $imap_encryption
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable([
    'user_id',
    'provider',
    'email',
    'display_name',
    'is_default',
    'is_active',
    'access_token',
    'refresh_token',
    'token_expiry',
    'smtp_host',
    'smtp_port',
    'smtp_username',
    'smtp_password',
    'smtp_encryption',
    'imap_host',
    'imap_port',
    'imap_username',
    'imap_password',
    'imap_encryption',
])]
#[Hidden([
    'access_token',
    'refresh_token',
    'smtp_password',
    'imap_password',
])]
#[UsePolicy(EmailAccountPolicy::class)]
class EmailAccount extends Model
{
    /** @use HasFactory<EmailAccountFactory> */
    use HasFactory;

    protected $table = 'email_accounts';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_default' => false,
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => MailboxProvider::class,
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expiry' => 'datetime',
            'smtp_port' => 'integer',
            'smtp_password' => 'encrypted',
            'smtp_encryption' => SmtpEncryption::class,
            'imap_port' => 'integer',
            'imap_password' => 'encrypted',
            'imap_encryption' => SmtpEncryption::class,
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
     * @return HasOne<EmailAccountSyncState, $this>
     */
    public function syncState(): HasOne
    {
        return $this->hasOne(EmailAccountSyncState::class, 'account_id');
    }

    /**
     * @return HasMany<EmailFolder, $this>
     */
    public function folders(): HasMany
    {
        return $this->hasMany(EmailFolder::class, 'account_id');
    }

    /**
     * @return HasMany<EmailThread, $this>
     */
    public function threads(): HasMany
    {
        return $this->hasMany(EmailThread::class, 'account_id');
    }

    /**
     * @return HasMany<Email, $this>
     */
    public function emails(): HasMany
    {
        return $this->hasMany(Email::class, 'account_id');
    }

    /**
     * @return HasMany<EmailDraft, $this>
     */
    public function drafts(): HasMany
    {
        return $this->hasMany(EmailDraft::class, 'account_id');
    }

    /**
     * @return HasMany<EmailLabel, $this>
     */
    public function labels(): HasMany
    {
        return $this->hasMany(EmailLabel::class, 'account_id');
    }

    public function usesOAuth(): bool
    {
        return $this->provider->usesOAuth();
    }

    public function accessTokenIsUsable(int $bufferSeconds = 300): bool
    {
        if (blank($this->access_token) || blank($this->refresh_token)) {
            return false;
        }

        if ($this->token_expiry === null) {
            return true;
        }

        return $this->token_expiry->gt(now()->addSeconds($bufferSeconds));
    }

    /**
     * @param  Builder<EmailAccount>  $query
     * @return Builder<EmailAccount>
     */
    #[Scope]
    protected function ownedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * @param  Builder<EmailAccount>  $query
     * @return Builder<EmailAccount>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
