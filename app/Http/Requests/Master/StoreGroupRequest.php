<?php

namespace App\Http\Requests\Master;

use App\Concerns\GroupValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreGroupRequest extends FormRequest
{
    use GroupValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->groupRules();
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
