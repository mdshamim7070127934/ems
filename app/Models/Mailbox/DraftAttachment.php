<?php

namespace App\Models\Mailbox;

use Database\Factories\Mailbox\DraftAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $draft_id
 * @property string $filename
 * @property string|null $original_filename
 * @property string|null $mime_type
 * @property int $size
 * @property string $storage_path
 * @property Carbon|null $created_at
 */
#[Fillable([
    'draft_id',
    'filename',
    'original_filename',
    'mime_type',
    'size',
    'storage_path',
])]
class DraftAttachment extends Model
{
    /** @use HasFactory<DraftAttachmentFactory> */
    use HasFactory;

    protected $table = 'draft_attachments';

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
     * @return BelongsTo<EmailDraft, $this>
     */
    public function draft(): BelongsTo
    {
        return $this->belongsTo(EmailDraft::class, 'draft_id');
    }
}
