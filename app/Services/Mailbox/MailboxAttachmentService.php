<?php

namespace App\Services\Mailbox;

use App\Models\Mailbox\DraftAttachment;
use App\Models\Mailbox\Email;
use App\Models\Mailbox\EmailAttachment;
use App\Models\Mailbox\EmailDraft;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MailboxAttachmentService
{
    public function storeForEmail(Email $email, string $originalFilename, string $contents, ?string $mimeType = null, ?string $contentId = null): EmailAttachment
    {
        $path = $this->write($originalFilename, $contents);

        return EmailAttachment::query()->create([
            'email_id' => $email->id,
            'filename' => basename($path),
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'size' => strlen($contents),
            'storage_path' => $path,
            'content_id' => $contentId,
        ]);
    }

    public function storeUploadForDraft(EmailDraft $draft, UploadedFile $file): DraftAttachment
    {
        $path = $this->write(
            $file->getClientOriginalName(),
            (string) file_get_contents($file->getRealPath()),
        );

        return DraftAttachment::query()->create([
            'draft_id' => $draft->id,
            'filename' => basename($path),
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize() ?: 0,
            'storage_path' => $path,
        ]);
    }

    public function copyDraftAttachmentToEmail(Email $email, DraftAttachment $attachment): EmailAttachment
    {
        return EmailAttachment::query()->create([
            'email_id' => $email->id,
            'filename' => $attachment->filename,
            'original_filename' => $attachment->original_filename,
            'mime_type' => $attachment->mime_type,
            'size' => $attachment->size,
            'storage_path' => $attachment->storage_path,
            'content_id' => null,
        ]);
    }

    public function download(EmailAttachment $attachment): StreamedResponse
    {
        return Storage::disk($this->disk())->download(
            $attachment->storage_path,
            $attachment->original_filename ?: $attachment->filename,
        );
    }

    public function preview(EmailAttachment $attachment): StreamedResponse
    {
        return Storage::disk($this->disk())->response(
            $attachment->storage_path,
            $attachment->original_filename ?: $attachment->filename,
            [
                'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            ],
        );
    }

    public function contents(DraftAttachment|EmailAttachment $attachment): string
    {
        return (string) Storage::disk($this->disk())->get($attachment->storage_path);
    }

    private function write(string $originalFilename, string $contents): string
    {
        $directory = trim((string) config('mailbox.attachments_directory'), '/').'/'.now()->format('Y/m/d');
        $safe = Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME)) ?: 'file';
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $filename = $safe.'_'.Str::lower(Str::random(8)).($extension !== '' ? '.'.$extension : '');
        $path = $directory.'/'.$filename;

        Storage::disk($this->disk())->put($path, $contents);

        return $path;
    }

    private function disk(): string
    {
        return (string) config('mailbox.attachments_disk', 'local');
    }
}
