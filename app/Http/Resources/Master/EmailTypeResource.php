<?php

namespace App\Http\Resources\Master;

use App\Models\Master\EmailType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EmailType
 */
class EmailTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: int,
     *     code: string,
     *     name: string,
     *     description: string|null,
     *     sort_order: int,
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
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_at' => $this->created_at->format('d-m-Y'),
            'updated_at' => $this->updated_at->format('d-m-Y'),
        ];
    }
}
