<?php

namespace App\Http\Resources\Master;

use App\Models\Master\DropdownOptionValue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DropdownOptionValue
 */
class DropdownOptionValueResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: int,
     *     option_type_id: int,
     *     dropdown_name: string,
     *     module_id: int,
     *     module_name: string,
     *     parent_id: int,
     *     parent_value: string|null,
     *     option_value: string,
     *     position: int|null,
     *     user_access_id: string|null,
     *     master_type: string|null,
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
            'option_type_id' => $this->option_type_id,
            'dropdown_name' => $this->dropdown->name,
            'module_id' => $this->module_id,
            'module_name' => $this->module->module_name,
            'parent_id' => $this->parent_id,
            'parent_value' => $this->parent_id === 0 ? null : $this->parent?->option_value,
            'option_value' => $this->option_value,
            'position' => $this->position,
            'user_access_id' => $this->user_access_id,
            'master_type' => $this->master_type,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_on' => $this->created_on->format('d-m-Y'),
            'updated_on' => $this->updated_on->format('d-m-Y'),
        ];
    }
}
