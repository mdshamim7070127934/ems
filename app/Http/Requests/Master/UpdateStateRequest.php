<?php

namespace App\Http\Requests\Master;

use App\Concerns\StateValidationRules;
use App\Models\Master\State;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStateRequest extends FormRequest
{
    use StateValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var State $state */
        $state = $this->route('state');

        return $this->stateRules($state->id);
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'country_id' => 'country',
        ];
    }
}
