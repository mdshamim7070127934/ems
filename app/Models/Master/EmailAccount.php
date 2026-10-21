<?php

namespace App\Models\Master;

use App\Enums\Master\EmailMailer;
use App\Enums\Master\EmailProvider;
use App\Enums\Master\SmtpEncryption;
use App\Enums\Master\Status;
use Database\Factories\Master\EmailAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property EmailProvider $provider
 * @property EmailMailer $mailer
 * @property string $from_name
 * @property string $from_email
 * @property string|null $oauth_provider_account_id
 * @property string|null $username
 * @property string|null $password
 * @property string|null $smtp_host
 * @property int|null $smtp_port
 * @property SmtpEncryption|null $smtp_encryption
 * @property string|null $oauth_client_id
 * @property string|null $oauth_client_secret
 * @property string|null $oauth_access_token
 * @property string|null $oauth_refresh_token
 * @property Carbon|null $oauth_token_expires_at
 * @property Carbon|null $last_token_refresh_at
 * @property bool $reauthorization_required
 * @property string|null $oauth_error
 * @property Status $status
 * @property bool $is_default
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable([
    'name',
    'provider',
    'mailer',
    'from_name',
    'from_email',
    'oauth_provider_account_id',
    'username',
    'password',
    'smtp_host',
    'smtp_port',
    'smtp_encryption',
    'oauth_client_id',
    'oauth_client_secret',
    'oauth_access_token',
    'oauth_refresh_token',
    'oauth_token_expires_at',
    'last_token_refresh_at',
    'reauthorization_required',
    'oauth_error',
    'status',
    'is_default',
])]
#[Hidden([
    'password',
    'oauth_client_secret',
    'oauth_access_token',
    'oauth_refresh_token',
])]
class EmailAccount extends Model
{
    /** @use HasFactory<EmailAccountFactory> */
    use HasFactory;

    protected $table = 'mst_email_account';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => Status::Active->value,
        'is_default' => false,
        'mailer' => EmailMailer::Smtp->value,
        'reauthorization_required' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => EmailProvider::class,
            'mailer' => EmailMailer::class,
            'smtp_port' => 'integer',
            'smtp_encryption' => SmtpEncryption::class,
            'password' => 'encrypted',
            'oauth_client_secret' => 'encrypted',
            'oauth_access_token' => 'encrypted',
            'oauth_refresh_token' => 'encrypted',
            'oauth_token_expires_at' => 'datetime',
            'last_token_refresh_at' => 'datetime',
            'reauthorization_required' => 'boolean',
            'status' => Status::class,
            'is_default' => 'boolean',
        ];
    }

    public function usesOAuth(): bool
    {
        return $this->mailer === EmailMailer::GmailApi
            || $this->mailer === EmailMailer::GraphApi;
    }

    public function accessTokenIsUsable(int $bufferSeconds = 300): bool
    {
        if ($this->reauthorization_required || blank($this->oauth_access_token)) {
            return false;
        }

        if ($this->oauth_token_expires_at === null) {
            return true;
        }

        return $this->oauth_token_expires_at->gt(now()->addSeconds($bufferSeconds));
    }

    /**
     * @return 'connected'|'reconnect_required'|'disconnected'|'smtp'
     */
    public function connectionStatus(): string
    {
        if (! $this->usesOAuth()) {
            return 'smtp';
        }

        if ($this->reauthorization_required || blank($this->oauth_refresh_token)) {
            return 'reconnect_required';
        }

        return 'connected';
    }

    public function connectionStatusLabel(): string
    {
        return match ($this->connectionStatus()) {
            'smtp' => 'SMTP',
            'connected' => 'Connected',
            'reconnect_required' => 'Reconnect required',
            'disconnected' => 'Disconnected',
        };
    }

    /**
     * @return BelongsToMany<EmailType, $this>
     */
    public function emailTypes(): BelongsToMany
    {
        return $this->belongsToMany(EmailType::class, 'mst_email_account_type', 'email_account_id', 'email_type_id')
            ->withPivot('is_enabled')
            ->withTimestamps();
    }

    /**
     * Replace the email types this account handles.
     *
     * @param  list<int>  $emailTypeIds
     */
    public function syncEmailTypes(array $emailTypeIds): void
    {
        $permissions = [];

        foreach (array_unique($emailTypeIds) as $emailTypeId) {
            if ($emailTypeId <= 0) {
                continue;
            }

            $permissions[$emailTypeId] = ['is_enabled' => true];
        }

        $this->emailTypes()->sync($permissions);
    }

    /**
     * @param  Builder<EmailAccount>  $query
     * @return Builder<EmailAccount>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', Status::Active);
    }

    /**
     * Active accounts assigned to an enabled email type code.
     *
     * @param  Builder<EmailAccount>  $query
     * @return Builder<EmailAccount>
     */
    #[Scope]
    protected function forType(Builder $query, string $code): Builder
    {
        return $query->active()->whereHas('emailTypes', function (Builder $query) use ($code): void {
            $query->where('mst_email_type.code', $code)
                ->where('mst_email_type.status', Status::Active)
                ->where('mst_email_account_type.is_enabled', true);
        });
    }

    /**
     * @param  Builder<EmailAccount>  $query
     * @return Builder<EmailAccount>
     */
    #[Scope]
    protected function search(Builder $query, string $term): Builder
    {
        if ($term === '') {
            return $query;
        }

        $escaped = addcslashes($term, '%_\\');

        return $query->where(function (Builder $query) use ($escaped): void {
            $query->whereLike('name', '%'.$escaped.'%')
                ->orWhereLike('from_name', '%'.$escaped.'%')
                ->orWhereLike('from_email', '%'.$escaped.'%')
                ->orWhereLike('username', '%'.$escaped.'%')
                ->orWhereLike('provider', '%'.$escaped.'%')
                ->orWhereLike('mailer', '%'.$escaped.'%')
                ->orWhereLike('status', '%'.$escaped.'%')
                ->orWhereHas('emailTypes', function (Builder $query) use ($escaped): void {
                    $query->whereLike('name', '%'.$escaped.'%')
                        ->orWhereLike('code', '%'.$escaped.'%');
                });
        });
    }
}
