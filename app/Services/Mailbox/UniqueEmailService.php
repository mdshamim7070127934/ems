<?php

namespace App\Services\Mailbox;

use App\Models\Mailbox\Email;
use App\Models\Mailbox\UniqueEmail;
use Illuminate\Support\Str;

class UniqueEmailService
{
    /**
     * @param  list<string|null>  $addresses
     */
    public function remember(array $addresses): void
    {
        foreach ($addresses as $address) {
            if (! is_string($address) || $address === '') {
                continue;
            }

            $email = Str::lower(trim($address));

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            UniqueEmail::query()->firstOrCreate(['email_id' => $email]);
        }
    }

    /**
     * @return list<array{email: string, display_name: string}>
     */
    public function suggest(string $query, int $limit = 10): array
    {
        $query = Str::lower(trim($query));

        if (Str::length($query) < 2) {
            return [];
        }

        $escaped = addcslashes($query, '%_\\');

        $emails = array_values(array_filter(
            UniqueEmail::query()
                ->whereLike('email_id', '%'.$escaped.'%')
                ->orderBy('email_id')
                ->limit($limit)
                ->pluck('email_id')
                ->all(),
            fn (mixed $email): bool => is_string($email),
        ));

        if ($emails === []) {
            return [];
        }

        $named = Email::query()
            ->whereIn('from_email', $emails)
            ->whereNotNull('from_name')
            ->orderByDesc('id')
            ->get(['from_email', 'from_name']);

        $names = [];

        foreach ($named as $row) {
            $key = Str::lower($row->from_email);

            if (! isset($names[$key]) && is_string($row->from_name) && $row->from_name !== '') {
                $names[$key] = $row->from_name;
            }
        }

        return array_map(
            function (string $email) use ($names): array {
                $displayName = $names[Str::lower($email)] ?? Str::before($email, '@');

                return [
                    'email' => $email,
                    'display_name' => $displayName !== '' ? $displayName : $email,
                ];
            },
            $emails,
        );
    }
}
