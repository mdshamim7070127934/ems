<?php

namespace Database\Factories\Mailbox;

use App\Models\Mailbox\Email;
use App\Models\Mailbox\EmailAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailAttachment>
 */
class EmailAttachmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->word().'.pdf';

        return [
            'email_id' => Email::factory(),
            'filename' => $name,
            'original_filename' => $name,
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'storage_path' => 'email_attachments/'.fake()->uuid().'.pdf',
            'content_id' => null,
        ];
    }
}
