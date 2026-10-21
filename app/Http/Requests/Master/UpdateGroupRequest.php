<?php

namespace App\Http\Requests\Master;

use App\Concerns\GroupValidationRules;
use App\Models\Master\Group;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupRequest extends FormRequest
{
    use GroupValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Group $group */
        $group = $this->route('group');

        return $this->groupRules($group->id);
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'group_name' => 'group name',
            'owner_id' => 'owner',
        ];
    }
}
