<?php

namespace App\Http\Resources\Master;

use App\Models\Master\State;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin State
 */
class StateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: int,
     *     country_id: int,
     *     country_name: string,
     *     name: string,
     *     status: string,
     *     status_label: string,
     *     created_on: string,
     *     updated_on: string
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'country_id' => $this->country_id,
            'country_name' => $this->country->name,
            'name' => $this->name,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_on' => $this->created_on->format('d-m-Y'),
            'updated_on' => $this->updated_on->format('d-m-Y'),
        ];
    }
}
