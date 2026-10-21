<?php

namespace App\Http\Resources\Mailbox;

use App\Models\Mailbox\EmailAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EmailAttachment
 */
class MailboxAttachmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'original_filename' => $this->original_filename ?: $this->filename,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
        ];
    }
}
