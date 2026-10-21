<?php

namespace App\Concerns;

use App\Enums\Master\Status;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

trait UserValidationRules
{
    /**
     * Normalize user fields before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => trim((string) $this->input('first_name')),
            'last_name' => trim((string) $this->input('last_name')),
            'email' => strtolower(trim((string) $this->input('email'))),
            'reporting_person_id' => ! $this->filled('reporting_person_id') || $this->input('reporting_person_id') === 'none'
                ? null
                : $this->input('reporting_person_id'),
            'extra_branch_ids' => $this->uniquePositiveIds($this->input('extra_branch_ids', [])),
            'exclude_branch_ids' => $this->uniquePositiveIds($this->input('exclude_branch_ids', [])),
        ]);

        if ($this->has('password') && $this->input('password') === '') {
            $this->merge(['password' => null]);
        }
    }

    /**
     * Get the validation rules used to validate users.
     *
     * @return array<string, mixed>
     */
    protected function userRules(?int $id = null, bool $passwordRequired = true): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'password' => $passwordRequired
                ? ['required', 'string', Password::defaults()]
                : ['nullable', 'string', Password::defaults()],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'branch_id' => ['required', 'integer', Rule::exists('mst_branch', 'id')],
            'reporting_person_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
                Rule::when($id !== null, Rule::notIn([$id])),
            ],
            'extra_branch_ids' => ['present', 'array'],
            'extra_branch_ids.*' => ['integer', Rule::exists('mst_branch', 'id')],
            'exclude_branch_ids' => ['present', 'array'],
            'exclude_branch_ids.*' => ['integer', Rule::exists('mst_branch', 'id')],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reporting_person_id.not_in' => 'The user cannot report to themselves.',
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

                $user = $this->route('user');
                $userId = $user instanceof User ? $user->id : null;
                $reportingPersonId = $this->filled('reporting_person_id')
                    ? $this->integer('reporting_person_id')
                    : null;

                if ($userId !== null && $reportingPersonId === $userId) {
                    $validator->errors()->add(
                        'reporting_person_id',
                        'The user cannot report to themselves.',
                    );
                }

                $branchId = $this->integer('branch_id');
                $extraIds = $this->uniquePositiveIds($this->input('extra_branch_ids', []));
                $excludeIds = $this->uniquePositiveIds($this->input('exclude_branch_ids', []));

                if (in_array($branchId, $extraIds, true)) {
                    $validator->errors()->add(
                        'extra_branch_ids',
                        'The primary branch cannot also be in extra branches.',
                    );
                }

                if (in_array($branchId, $excludeIds, true)) {
                    $validator->errors()->add(
                        'exclude_branch_ids',
                        'The primary branch cannot also be in excluded branches.',
                    );
                }

                if (array_intersect($extraIds, $excludeIds) !== []) {
                    $validator->errors()->add(
                        'exclude_branch_ids',
                        'A branch cannot be in both extra and excluded branches.',
                    );
                }
            },
        ];
    }

    /**
     * @return list<int>
     */
    private function uniquePositiveIds(mixed $value): array
    {
        return array_values(array_unique(array_filter(
            array_map(intval(...), (array) $value),
            fn (int $id): bool => $id > 0,
        )));
    }
}
