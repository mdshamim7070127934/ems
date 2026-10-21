<?php

namespace Database\Factories\Mailbox;

use App\Enums\Mailbox\FolderType;
use App\Models\Mailbox\EmailAccount;
use App\Models\Mailbox\EmailFolder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailFolder>
 */
class EmailFolderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'account_id' => EmailAccount::factory(),
            'provider_folder_id' => 'INBOX',
            'name' => 'Inbox',
            'slug' => $slug,
            'icon' => 'inbox',
            'type' => FolderType::System,
            'message_count' => 0,
            'unread_count' => 0,
        ];
    }

    public function inbox(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Inbox',
            'slug' => 'inbox',
            'icon' => 'inbox',
            'type' => FolderType::System,
            'provider_folder_id' => 'INBOX',
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Sent',
            'slug' => 'sent',
            'icon' => 'sent',
            'type' => FolderType::System,
            'provider_folder_id' => 'SENT',
        ]);
    }

    public function trash(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Trash',
            'slug' => 'trash',
            'icon' => 'trash',
            'type' => FolderType::System,
            'provider_folder_id' => 'TRASH',
        ]);
    }

    public function spam(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Spam',
            'slug' => 'spam',
            'icon' => 'spam',
            'type' => FolderType::System,
            'provider_folder_id' => 'SPAM',
        ]);
    }
}
