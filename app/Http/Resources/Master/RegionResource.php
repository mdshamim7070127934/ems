<?php

namespace App\Http\Resources\Master;

use App\Models\Master\Region;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Region
 */
class RegionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: int,
     *     region_name: string,
     *     country_id: int,
     *     country_name: string,
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
            'region_name' => $this->region_name,
            'country_id' => $this->country_id,
            'country_name' => $this->country->name,
            'owner_id' => $this->owner_id,
            'owner_name' => $this->owner->name,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_at' => $this->created_at->format('d-m-Y'),
            'updated_at' => $this->updated_at->format('d-m-Y'),
        ];
    }
}
