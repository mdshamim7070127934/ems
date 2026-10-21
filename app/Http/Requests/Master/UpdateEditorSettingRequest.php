<?php

namespace App\Http\Requests\Master;

use App\Concerns\EditorSettingValidationRules;
use App\Enums\Master\Status;
use App\Models\Master\EditorSetting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEditorSettingRequest extends FormRequest
{
    use EditorSettingValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->editorSettingRules();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sort_order' => 'sort order',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $setting = $this->route('editor_setting');

                if (! $setting instanceof EditorSetting) {
                    return;
                }

                if ($setting->is_default && $this->input('status') === Status::Inactive->value) {
                    $validator->errors()->add(
                        'status',
                        __('The default editor cannot be inactive. Set another editor as default first.'),
                    );
                }
            },
        ];
    }
}
