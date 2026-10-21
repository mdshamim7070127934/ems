<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Master\Status;
use App\Models\Mailbox\EmailAccount;
use App\Models\Mailbox\EmailSignature;
use App\Models\Master\Branch;
use App\Models\Master\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $username
 * @property int|null $role_id
 * @property int|null $reporting_person_id
 * @property int|null $branch_id
 * @property list<int>|null $extra_branch_ids
 * @property list<int>|null $exclude_branch_ids
 * @property Status $status
 * @property bool $is_available
 * @property int|null $created_by
 * @property int|null $last_modified_by
 * @property Carbon|null $last_login
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Role|null $role
 * @property-read User|null $reportingPerson
 * @property-read Branch|null $branch
 * @property-read User|null $creator
 * @property-read User|null $modifier
 */
#[Fillable([
    'name',
    'email',
    'password',
    'first_name',
    'last_name',
    'username',
    'role_id',
    'reporting_person_id',
    'branch_id',
    'extra_branch_ids',
    'exclude_branch_ids',
    'status',
    'is_available',
    'created_by',
    'last_modified_by',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => Status::Active->value,
        'is_available' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role_id' => 'integer',
            'reporting_person_id' => 'integer',
            'branch_id' => 'integer',
            'extra_branch_ids' => 'array',
            'exclude_branch_ids' => 'array',
            'status' => Status::class,
            'is_available' => 'boolean',
            'created_by' => 'integer',
            'last_modified_by' => 'integer',
            'last_login' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reportingPerson(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reporting_person_id');
    }

    /**
     * @return HasMany<User, $this>
     */
    public function reportees(): HasMany
    {
        return $this->hasMany(self::class, 'reporting_person_id');
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function modifier(): BelongsTo
    {
        return $this->belongsTo(self::class, 'last_modified_by');
    }

    /**
     * @return HasMany<EmailAccount, $this>
     */
    public function mailboxAccounts(): HasMany
    {
        return $this->hasMany(EmailAccount::class);
    }

    /**
     * @return HasMany<EmailSignature, $this>
     */
    public function emailSignatures(): HasMany
    {
        return $this->hasMany(EmailSignature::class);
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    #[Scope]
    protected function search(Builder $query, string $term): Builder
    {
        if ($term === '') {
            return $query;
        }

        $escaped = addcslashes($term, '%_\\');

        return $query->where(function (Builder $query) use ($escaped): void {
            $query->whereLike('first_name', '%'.$escaped.'%')
                ->orWhereLike('last_name', '%'.$escaped.'%')
                ->orWhereLike('name', '%'.$escaped.'%')
                ->orWhereLike('email', '%'.$escaped.'%')
                ->orWhereLike('username', '%'.$escaped.'%')
                ->orWhereLike('status', '%'.$escaped.'%')
                ->orWhereHas('role', function (Builder $query) use ($escaped): void {
                    $query->whereLike('display_name', '%'.$escaped.'%')
                        ->orWhereLike('role_name', '%'.$escaped.'%');
                })
                ->orWhereHas('branch', function (Builder $query) use ($escaped): void {
                    $query->whereLike('branch_name', '%'.$escaped.'%');
                })
                ->orWhereHas('reportingPerson', function (Builder $query) use ($escaped): void {
                    $query->whereLike('name', '%'.$escaped.'%');
                });
        });
    }
}
