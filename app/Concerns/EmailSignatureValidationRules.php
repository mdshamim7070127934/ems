<?php

namespace App\Concerns;

use App\Enums\Master\Status;
use Illuminate\Validation\Rule;

trait EmailSignatureValidationRules
{
    /**
     * Normalize signature fields before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('signature_name')) {
            $this->merge([
                'signature_name' => trim((string) $this->input('signature_name')),
            ]);
        }

        $this->merge([
            'signature_html' => $this->filled('signature_html')
                ? (string) $this->input('signature_html')
                : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function emailSignatureRules(?int $ignoreId = null): array
    {
        $unique = Rule::unique('email_signatures', 'signature_name')
            ->where('user_id', $this->user()?->id);

        if ($ignoreId !== null) {
            $unique->ignore($ignoreId);
        }

        return [
            'signature_name' => ['required', 'string', 'max:255', $unique],
            'signature_html' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'signature_name' => 'signature name',
            'signature_html' => 'signature',
        ];
    }
}
