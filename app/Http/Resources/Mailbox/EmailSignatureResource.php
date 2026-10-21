<?php

namespace App\Http\Resources\Mailbox;

use App\Models\Mailbox\EmailSignature;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EmailSignature
 */
class EmailSignatureResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: int,
     *     signature_name: string,
     *     signature_html: string|null,
     *     is_default: bool,
     *     status: string,
     *     status_label: string,
     *     created_at: string,
     *     updated_at: string
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'signature_name' => $this->signature_name,
            'signature_html' => $this->signature_html,
            'is_default' => $this->is_default,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_at' => $this->created_at->format('d-m-Y'),
            'updated_at' => $this->updated_at->format('d-m-Y'),
        ];
    }
}
