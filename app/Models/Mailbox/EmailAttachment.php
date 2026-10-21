<?php

namespace App\Models\Mailbox;

use Database\Factories\Mailbox\EmailAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $email_id
 * @property string $filename
 * @property string|null $original_filename
 * @property string|null $mime_type
 * @property int $size
 * @property string $storage_path
 * @property string|null $content_id
 * @property Carbon|null $created_at
 */
#[Fillable([
    'email_id',
    'filename',
    'original_filename',
    'mime_type',
    'size',
    'storage_path',
    'content_id',
])]
class EmailAttachment extends Model
{
    /** @use HasFactory<EmailAttachmentFactory> */
    use HasFactory;

    protected $table = 'email_attachments';

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Email, $this>
     */
    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class, 'email_id');
    }
}
