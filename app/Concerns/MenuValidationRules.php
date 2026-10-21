<?php

namespace App\Concerns;

use App\Enums\Master\Status;
use App\Models\Master\Menu;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

trait MenuValidationRules
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'menu_parent_id' => $this->input('menu_parent_id') === '' || $this->input('menu_parent_id') === null
                ? 0
                : $this->input('menu_parent_id'),
            'column_description' => $this->input('column_description') === '' ? null : $this->input('column_description'),
            'screen_name' => $this->input('screen_name') === '' ? null : $this->input('screen_name'),
            'web_icon' => $this->input('web_icon') === '' ? null : $this->input('web_icon'),
            'mobile_icon' => $this->input('mobile_icon') === '' ? null : $this->input('mobile_icon'),
        ]);
    }

    /**
     * Get the validation rules used to validate menus.
     *
     * @return array<string, mixed>
     */
    protected function menuRules(?int $id = null): array
    {
        return [
            'menu_parent_id' => ['required', 'integer', 'min:0'],
            'menu_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('mst_menu', 'menu_name')
                    ->where('menu_parent_id', $this->input('menu_parent_id'))
                    ->ignore($id),
            ],
            'column_description' => ['nullable', 'string'],
            'menu_route' => ['required', 'string', 'max:100'],
            'menu_order' => ['required', 'integer'],
            'screen_name' => ['nullable', 'string', 'max:255'],
            'web_icon' => ['nullable', 'string', 'max:255'],
            'mobile_icon' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(Status::class)],
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

                $parentId = $this->integer('menu_parent_id');

                if ($parentId === 0) {
                    return;
                }

                /** @var Menu|null $current */
                $current = $this->route('menu');

                if ($current !== null && $parentId === $current->id) {
                    $validator->errors()->add('menu_parent_id', 'The parent cannot be the same record.');

                    return;
                }

                $parentExists = Menu::query()->whereKey($parentId)->exists();

                if (! $parentExists) {
                    $validator->errors()->add('menu_parent_id', 'The selected parent is invalid.');
                }
            },
        ];
    }
}
