<?php

namespace App\Services\Mailbox;

use App\Enums\Master\Status;
use App\Models\Mailbox\EmailSignature;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EmailSignatureService
{
    /**
     * @param  array{signature_name: string, signature_html?: string|null, status: Status|string}  $data
     */
    public function create(User $user, array $data): EmailSignature
    {
        return EmailSignature::query()->create([
            'user_id' => $user->id,
            'signature_name' => $data['signature_name'],
            'signature_html' => $data['signature_html'] ?? null,
            'status' => $this->status($data['status']),
            'is_default' => false,
        ]);
    }

    /**
     * @param  array{signature_name: string, signature_html?: string|null, status: Status|string}  $data
     */
    public function update(EmailSignature $signature, array $data): EmailSignature
    {
        $status = $this->status($data['status']);
        $isDefault = $signature->is_default && $status === Status::Active;

        $signature->update([
            'signature_name' => $data['signature_name'],
            'signature_html' => $data['signature_html'] ?? null,
            'status' => $status,
            'is_default' => $isDefault,
        ]);

        return $signature;
    }

    public function delete(EmailSignature $signature): void
    {
        $signature->delete();
    }

    public function makeDefault(EmailSignature $signature, bool $unset = false): bool
    {
        if ($unset) {
            $signature->update(['is_default' => false]);

            return true;
        }

        if ($signature->status !== Status::Active) {
            return false;
        }

        DB::transaction(function () use ($signature): void {
            EmailSignature::query()
                ->where('user_id', $signature->user_id)
                ->where('id', '!=', $signature->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);

            $signature->update([
                'is_default' => true,
                'status' => Status::Active,
            ]);
        });

        return true;
    }

    /**
     * @return array{items: list<array{id: int, name: string, html: string}>, default_html: string|null}
     */
    public function sharedForUser(User $user): array
    {
        $signatures = EmailSignature::query()
            ->whereBelongsTo($user)
            ->active()
            ->orderBy('signature_name')
            ->orderBy('id')
            ->get(['id', 'signature_name', 'signature_html', 'is_default']);

        $default = $signatures->firstWhere('is_default', true);

        return [
            'items' => $signatures
                ->map(fn (EmailSignature $signature): array => [
                    'id' => $signature->id,
                    'name' => $signature->signature_name,
                    'html' => $signature->signature_html ?? '',
                ])
                ->values()
                ->all(),
            'default_html' => $default?->signature_html,
        ];
    }

    private function status(Status|string $status): Status
    {
        return $status instanceof Status ? $status : Status::from($status);
    }
}
