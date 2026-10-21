<?php

namespace App\Http\Requests\Master;

use App\Concerns\ModuleValidationRules;
use App\Models\Master\Module;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateModuleRequest extends FormRequest
{
    use ModuleValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Module $module */
        $module = $this->route('module');

        return $this->moduleRules($module->module_id);
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'module_name' => 'name',
        ];
    }
}
