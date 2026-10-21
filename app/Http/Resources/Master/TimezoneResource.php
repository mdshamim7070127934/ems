<?php

namespace App\Http\Resources\Master;

use App\Models\Master\Timezone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Timezone
 */
class TimezoneResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: int,
     *     name: string,
     *     code: string,
     *     utc_offset: string,
     *     display_name: string,
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
            'name' => $this->name,
            'code' => $this->code,
            'utc_offset' => now($this->name)->format('P'),
            'display_name' => $this->display_name,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_on' => $this->created_on->format('d-m-Y'),
            'updated_on' => $this->updated_on->format('d-m-Y'),
        ];
    }
}
