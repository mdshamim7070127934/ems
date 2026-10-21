<?php

namespace App\Http\Resources\Mailbox;

use App\Models\Mailbox\EmailFolder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EmailFolder
 */
class MailboxFolderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'type' => $this->type->value,
            'unread_count' => $this->unread_count,
            'message_count' => $this->message_count,
        ];
    }
}
