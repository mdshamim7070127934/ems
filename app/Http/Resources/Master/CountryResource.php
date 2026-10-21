<?php

namespace App\Http\Resources\Master;

use App\Models\Master\Country;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Country
 */
class CountryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: int,
     *     sortname: string,
     *     name: string,
     *     currency: string,
     *     phonecode: int,
     *     timezone_id: int|null,
     *     timezone_name: string|null,
     *     timezone_display_name: string|null,
     *     status: string,
     *     status_label: string
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sortname' => $this->sortname,
            'name' => $this->name,
            'currency' => $this->currency,
            'phonecode' => $this->phonecode,
            'timezone_id' => $this->timezone_id,
            'timezone_name' => $this->timezone?->name,
            'timezone_display_name' => $this->timezone?->display_name,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
        ];
    }
}
