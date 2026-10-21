<?php

namespace App\Http\Resources\Master;

use App\Models\Master\EditorSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EditorSetting
 */
class EditorSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: int,
     *     code: string,
     *     code_label: string,
     *     name: string,
     *     description: string|null,
     *     is_default: bool,
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
            'code' => $this->code->value,
            'code_label' => $this->code->label(),
            'name' => $this->name,
            'description' => $this->description,
            'is_default' => $this->is_default,
            'sort_order' => $this->sort_order,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_at' => $this->created_at->format('d-m-Y'),
            'updated_at' => $this->updated_at->format('d-m-Y'),
        ];
    }
}
