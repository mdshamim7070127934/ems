<?php

namespace App\Models\Mailbox;

use App\Models\User;
use Database\Factories\Mailbox\BlockedEmailFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $email_id
 * @property bool $is_block
 * @property int|null $blocked_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable([
    'user_id',
    'email_id',
    'is_block',
    'blocked_by',
])]
class BlockedEmail extends Model
{
    /** @use HasFactory<BlockedEmailFactory> */
    use HasFactory;

    protected $table = 'ems_block_email';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_block' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_block' => 'boolean',
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
     * @return BelongsTo<User, $this>
     */
    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }
}
