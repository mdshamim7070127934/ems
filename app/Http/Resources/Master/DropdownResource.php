<?php

namespace App\Http\Resources\Master;

use App\Models\Master\Dropdown;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Dropdown
 */
class DropdownResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     option_type_id: int,
     *     module_id: int,
     *     module_name: string,
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
            'option_type_id' => $this->option_type_id,
            'module_id' => $this->module_id,
            'module_name' => $this->module->module_name,
            'name' => $this->name,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_on' => $this->created_on->format('d-m-Y'),
            'updated_on' => $this->updated_on->format('d-m-Y'),
        ];
    }
}
