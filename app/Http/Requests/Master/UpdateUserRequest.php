<?php

namespace App\Http\Requests\Master;

use App\Concerns\UserValidationRules;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    use UserValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return $this->userRules($user->id, passwordRequired: false);
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'role_id' => 'role',
            'branch_id' => 'branch',
            'reporting_person_id' => 'reporting person',
            'extra_branch_ids' => 'extra branches',
            'extra_branch_ids.*' => 'extra branch',
            'exclude_branch_ids' => 'excluded branches',
            'exclude_branch_ids.*' => 'excluded branch',
        ];
    }
}
