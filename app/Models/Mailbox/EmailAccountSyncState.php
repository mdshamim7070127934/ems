<?php

namespace App\Models\Mailbox;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $account_id
 * @property string|null $gmail_history_id
 * @property string|null $outlook_delta_inbox
 * @property string|null $outlook_delta_sent
 * @property Carbon|null $imap_last_sync_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'account_id',
    'gmail_history_id',
    'outlook_delta_inbox',
    'outlook_delta_sent',
    'imap_last_sync_at',
])]
class EmailAccountSyncState extends Model
{
    protected $table = 'email_account_sync_state';

    protected $primaryKey = 'account_id';

    public $incrementing = false;

    public const CREATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'account_id' => 'integer',
            'imap_last_sync_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<EmailAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class, 'account_id');
    }
}
