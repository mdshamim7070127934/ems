<?php

namespace App\Http\Resources\Master;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: int,
     *     first_name: string|null,
     *     last_name: string|null,
     *     name: string,
     *     email: string,
     *     username: string|null,
     *     role_id: int|null,
     *     role_name: string|null,
     *     branch_id: int|null,
     *     branch_name: string|null,
     *     reporting_person_id: int|null,
     *     reporting_person_name: string|null,
     *     extra_branch_ids: list<int>,
     *     extra_branch_names: string,
     *     exclude_branch_ids: list<int>,
     *     exclude_branch_names: string,
     *     status: string,
     *     status_label: string,
     *     is_available: bool,
     *     last_login: string|null,
     *     created_at: string,
     *     updated_at: string
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => $this->displayName(),
            'email' => $this->email,
            'username' => $this->username,
            'role_id' => $this->role_id,
            'role_name' => $this->role?->display_name,
            'branch_id' => $this->branch_id,
            'branch_name' => $this->branch?->branch_name,
            'reporting_person_id' => $this->reporting_person_id,
            'reporting_person_name' => $this->reportingPerson?->name,
            'extra_branch_ids' => $this->intIds($this->extra_branch_ids),
            'extra_branch_names' => (string) $this->resource->getAttribute('extra_branch_names'),
            'exclude_branch_ids' => $this->intIds($this->exclude_branch_ids),
            'exclude_branch_names' => (string) $this->resource->getAttribute('exclude_branch_names'),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_available' => $this->is_available,
            'last_login' => $this->last_login?->format('d-m-Y H:i'),
            'created_at' => $this->created_at?->format('d-m-Y') ?? '',
            'updated_at' => $this->updated_at?->format('d-m-Y') ?? '',
        ];
    }

    private function displayName(): string
    {
        $fullName = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return $fullName !== '' ? $fullName : $this->name;
    }

    /**
     * @param  list<int>|null  $ids
     * @return list<int>
     */
    private function intIds(?array $ids): array
    {
        return array_values(array_unique(array_filter(
            array_map(intval(...), $ids ?? []),
            fn (int $id): bool => $id > 0,
        )));
    }
}
