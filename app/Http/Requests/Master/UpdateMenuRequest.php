<?php

namespace App\Http\Requests\Master;

use App\Concerns\MenuValidationRules;
use App\Models\Master\Menu;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuRequest extends FormRequest
{
    use MenuValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Menu $menu */
        $menu = $this->route('menu');

        return $this->menuRules($menu->id);
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'menu_parent_id' => 'parent',
            'menu_name' => 'menu name',
            'column_description' => 'description',
            'menu_route' => 'route',
            'menu_order' => 'order',
            'screen_name' => 'screen name',
            'web_icon' => 'web icon',
            'mobile_icon' => 'mobile icon',
        ];
    }
}
