<?php

namespace App\Http\Resources\Mailbox;

use App\Models\Mailbox\EmailThread;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EmailThread
 */
class MailboxThreadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $first = $this->emails->sortBy('id')->first();
        $labels = $this->relationLoaded('emails')
            ? $this->emails
                ->flatMap(fn ($email) => $email->relationLoaded('labels') ? $email->labels : collect())
                ->unique('id')
                ->values()
                ->map(fn ($label): array => [
                    'id' => $label->id,
                    'name' => $label->name,
                    'color' => $label->color,
                    'slug' => $label->slug,
                ])
                ->all()
            : [];

        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'snippet' => $this->snippet,
            'from_email' => $first?->from_email,
            'from_name' => $first?->from_name,
            'is_starred' => $this->is_starred || $this->emails->contains(fn ($email): bool => $email->is_starred),
            'is_unread' => $this->emails->contains(fn ($email): bool => $email->is_unread),
            'has_attachments' => $this->emails->contains(function ($email): bool {
                if (isset($email->attachments_exists)) {
                    return (bool) $email->attachments_exists;
                }

                return $email->relationLoaded('attachments') && $email->attachments->isNotEmpty();
            }),
            'message_count' => $this->message_count,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'labels' => $labels,
            'sender_blocked' => (bool) $this->resource->getAttribute('sender_blocked'),
        ];
    }
}
