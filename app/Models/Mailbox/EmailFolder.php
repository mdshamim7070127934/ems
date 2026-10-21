<?php

namespace App\Models\Mailbox;

use App\Enums\Mailbox\FolderType;
use Database\Factories\Mailbox\EmailFolderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $account_id
 * @property string|null $provider_folder_id
 * @property string $name
 * @property string $slug
 * @property string|null $icon
 * @property FolderType $type
 * @property int $message_count
 * @property int $unread_count
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable([
    'account_id',
    'provider_folder_id',
    'name',
    'slug',
    'icon',
    'type',
    'message_count',
    'unread_count',
])]
class EmailFolder extends Model
{
    /** @use HasFactory<EmailFolderFactory> */
    use HasFactory;

    protected $table = 'email_folders';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => FolderType::Custom->value,
        'message_count' => 0,
        'unread_count' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => FolderType::class,
            'message_count' => 'integer',
            'unread_count' => 'integer',
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
        return $this->hasMany(Email::class, 'folder_id');
    }
}
