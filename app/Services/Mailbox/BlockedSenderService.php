<?php

namespace App\Services\Mailbox;

use App\Models\Mailbox\BlockedEmail;
use App\Models\User;
use Illuminate\Support\Str;

class BlockedSenderService
{
    public function block(User $user, string $email): BlockedEmail
    {
        $address = Str::lower(trim($email));

        return BlockedEmail::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'email_id' => $address,
            ],
            [
                'is_block' => true,
                'blocked_by' => $user->id,
            ],
        );
    }

    public function unblock(User $user, string $email): void
    {
        BlockedEmail::query()
            ->where('user_id', $user->id)
            ->where('email_id', Str::lower(trim($email)))
            ->delete();
    }

    /**
     * @return list<string>
     */
    public function blockedAddresses(User $user): array
    {
        $addresses = [];

        foreach (
            BlockedEmail::query()
                ->where('user_id', $user->id)
                ->where('is_block', true)
                ->pluck('email_id') as $email
        ) {
            $addresses[] = Str::lower((string) $email);
        }

        return $addresses;
    }

    /**
     * @return array<string, true>
     */
    public function blockedLookup(User $user): array
    {
        return array_fill_keys($this->blockedAddresses($user), true);
    }

    public function isBlocked(User $user, string $email): bool
    {
        return BlockedEmail::query()
            ->where('user_id', $user->id)
            ->where('email_id', Str::lower(trim($email)))
            ->where('is_block', true)
            ->exists();
    }
}
