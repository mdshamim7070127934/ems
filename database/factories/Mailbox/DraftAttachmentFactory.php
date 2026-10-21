<?php

namespace Database\Factories\Mailbox;

use App\Models\Mailbox\DraftAttachment;
use App\Models\Mailbox\EmailDraft;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DraftAttachment>
 */
class DraftAttachmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->word().'.pdf';

        return [
            'draft_id' => EmailDraft::factory(),
            'filename' => $name,
            'original_filename' => $name,
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'storage_path' => 'email_attachments/drafts/'.fake()->uuid().'.pdf',
        ];
    }
}
