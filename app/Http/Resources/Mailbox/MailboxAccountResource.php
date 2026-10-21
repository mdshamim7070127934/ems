<?php

namespace App\Http\Resources\Mailbox;

use App\Models\Mailbox\EmailAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EmailAccount
 */
class MailboxAccountResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'display_name' => $this->display_name,
            'provider' => $this->provider->value,
            'provider_label' => $this->provider->label(),
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'uses_oauth' => $this->usesOAuth(),
        ];
    }
}
