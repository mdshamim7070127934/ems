<?php

namespace App\Http\Resources\Master;

use App\Models\Master\Module;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Module
 */
class ModuleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     module_id: int,
     *     module_name: string,
     *     status: string,
     *     status_label: string,
     *     created_on: string,
     *     updated_on: string
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'module_id' => $this->module_id,
            'module_name' => $this->module_name,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_on' => $this->created_on->format('d-m-Y'),
            'updated_on' => $this->updated_on->format('d-m-Y'),
        ];
    }
}
