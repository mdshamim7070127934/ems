<?php

namespace App\Http\Resources\Mailbox;

use App\Models\Mailbox\Email;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Email
 */
class MailboxEmailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_email' => $this->from_email,
            'from_name' => $this->from_name,
            'to_emails' => $this->to_emails ?? [],
            'cc_emails' => $this->cc_emails ?? [],
            'subject' => $this->subject,
            'snippet' => $this->snippet,
            'body_html' => $this->body_html,
            'body_plain' => $this->body_plain,
            'is_unread' => $this->is_unread,
            'is_starred' => $this->is_starred,
            'internal_date' => $this->internal_date?->toIso8601String(),
            'attachments' => $this->whenLoaded(
                'attachments',
                fn () => $this->attachments
                    ->map(fn ($attachment): array => (new MailboxAttachmentResource($attachment))->resolve())
                    ->values()
                    ->all(),
                [],
            ),
            'label_ids' => $this->whenLoaded('labels', fn () => $this->labels->pluck('id')->all(), []),
            'sender_blocked' => (bool) $this->resource->getAttribute('sender_blocked'),
        ];
    }
}
