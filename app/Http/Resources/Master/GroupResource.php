<?php

namespace App\Http\Resources\Master;

use App\Models\Master\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Group
 */
class GroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: int,
     *     group_name: string,
     *     description: string,
     *     owner_id: int,
     *     owner_name: string,
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
            'group_name' => $this->group_name,
            'description' => $this->description,
            'owner_id' => $this->owner_id,
            'owner_name' => $this->owner->name,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_at' => $this->created_at->format('d-m-Y'),
            'updated_at' => $this->updated_at->format('d-m-Y'),
        ];
    }
}
