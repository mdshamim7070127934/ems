<?php

namespace App\Http\Resources\Master;

use App\Models\Master\Branch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Branch
 */
class BranchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: int,
     *     branch_name: string,
     *     geofencing: bool,
     *     geofencing_range: string|null,
     *     branch_country_code: string|null,
     *     branch_fbcountry_code: string,
     *     branch_manager: int,
     *     manager_name: string,
     *     country_id: int,
     *     country_name: string,
     *     region_id: int,
     *     region_name: string,
     *     group_id: int,
     *     group_name: string,
     *     branch_code: string,
     *     owner_id: int|null,
     *     owner_name: string|null,
     *     man_power: int|null,
     *     retail_man_power: int|null,
     *     created_by: int,
     *     created_by_name: string,
     *     last_modified_by: int,
     *     last_modified_by_name: string,
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
            'branch_name' => $this->branch_name,
            'geofencing' => $this->geofencing,
            'geofencing_range' => $this->geofencing_range,
            'branch_country_code' => $this->branch_country_code,
            'branch_fbcountry_code' => $this->branch_fbcountry_code,
            'branch_manager' => $this->branch_manager,
            'manager_name' => $this->manager->name,
            'country_id' => $this->country_id,
            'country_name' => $this->country->name,
            'region_id' => $this->region_id,
            'region_name' => $this->region->region_name,
            'group_id' => $this->group_id,
            'group_name' => $this->group->group_name,
            'branch_code' => $this->branch_code,
            'owner_id' => $this->owner_id,
            'owner_name' => $this->owner?->name,
            'man_power' => $this->man_power,
            'retail_man_power' => $this->retail_man_power,
            'created_by' => $this->created_by,
            'created_by_name' => $this->creator->name,
            'last_modified_by' => $this->last_modified_by,
            'last_modified_by_name' => $this->modifier->name,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_on' => $this->created_on->format('d-m-Y'),
            'updated_on' => $this->updated_on->format('d-m-Y'),
        ];
    }
}
