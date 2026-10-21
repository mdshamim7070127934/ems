<?php

namespace App\Http\Resources\Master;

use App\Models\Master\Menu;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Menu
 */
class MenuResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: int,
     *     menu_parent_id: int,
     *     parent_name: string|null,
     *     menu_name: string,
     *     column_description: string|null,
     *     menu_route: string,
     *     menu_order: int,
     *     screen_name: string|null,
     *     web_icon: string|null,
     *     mobile_icon: string|null,
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
            'menu_parent_id' => $this->menu_parent_id,
            'parent_name' => $this->menu_parent_id === 0 ? null : $this->parent?->menu_name,
            'menu_name' => $this->menu_name,
            'column_description' => $this->column_description,
            'menu_route' => $this->menu_route,
            'menu_order' => $this->menu_order,
            'screen_name' => $this->screen_name,
            'web_icon' => $this->web_icon,
            'mobile_icon' => $this->mobile_icon,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_at' => $this->created_at->format('d-m-Y'),
            'updated_at' => $this->updated_at->format('d-m-Y'),
        ];
    }
}
