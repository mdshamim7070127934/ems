<?php

namespace App\Http\Resources\Mailbox;

use App\Models\Mailbox\EmailDraft;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EmailDraft
 */
class MailboxDraftResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'to_emails' => $this->to_emails ?? [],
            'cc_emails' => $this->cc_emails ?? [],
            'bcc_emails' => $this->bcc_emails ?? [],
            'subject' => $this->subject,
            'body_html' => $this->body_html,
            'thread_id' => $this->thread_id,
            'in_reply_to' => $this->in_reply_to,
            'updated_at' => $this->updated_at->toIso8601String(),
            'attachments' => $this->whenLoaded(
                'attachments',
                fn () => $this->attachments->map(fn ($attachment): array => [
                    'id' => $attachment->id,
                    'original_filename' => $attachment->original_filename ?: $attachment->filename,
                    'size' => $attachment->size,
                ])->all(),
                [],
            ),
        ];
    }
}
