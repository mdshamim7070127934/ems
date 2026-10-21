<?php

namespace App\Http\Resources\Master;

use App\Models\Master\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Role
 */
class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: int,
     *     role_name: string,
     *     display_name: string,
     *     is_active: bool,
     *     is_super_admin: bool,
     *     is_protected: bool,
     *     status: string,
     *     status_label: string,
     *     menu_ids: list<int>,
     *     created_at: string,
     *     updated_at: string
     * }
     */
    public function toArray(Request $request): array
    {
        $isProtected = $this->isProtected();

        return [
            'id' => $this->id,
            'role_name' => $this->role_name,
            'display_name' => $this->display_name,
            'is_active' => $this->is_active,
            'is_super_admin' => $this->is_super_admin,
            'is_protected' => $isProtected,
            'status' => $this->is_active ? 'active' : 'inactive',
            'status_label' => $isProtected ? 'Administrator' : ($this->is_active ? 'Active' : 'Inactive'),
            'menu_ids' => $this->whenLoaded(
                'menus',
                fn (): array => $this->menus->pluck('id')->map(intval(...))->values()->all(),
                [],
            ),
            'created_at' => $this->created_at->format('d-m-Y'),
            'updated_at' => $this->updated_at->format('d-m-Y'),
        ];
    }
}
