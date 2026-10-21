<?php

namespace App\Services\Mailbox;

use App\Models\Mailbox\Email;
use App\Models\Mailbox\EmailAccount;
use App\Models\Mailbox\EmailDraft;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class MailboxDraftService
{
    public function __construct(
        private MailboxAttachmentService $attachments,
        private UniqueEmailService $uniqueEmails,
    ) {}

    /**
     * @param  array{
     *     to_emails?: list<string>,
     *     cc_emails?: list<string>,
     *     bcc_emails?: list<string>,
     *     subject?: string|null,
     *     body_html?: string|null,
     *     body_plain?: string|null,
     *     thread_id?: int|null,
     *     in_reply_to?: string|null
     * }  $data
     * @param  list<UploadedFile>  $files
     * @param  list<int>|null  $keepAttachmentIds
     */
    public function save(EmailAccount $account, ?EmailDraft $draft, array $data, array $files = [], ?array $keepAttachmentIds = null): EmailDraft
    {
        return DB::transaction(function () use ($account, $draft, $data, $files, $keepAttachmentIds): EmailDraft {
            $payload = [
                'account_id' => $account->id,
                'thread_id' => $data['thread_id'] ?? $draft?->thread_id,
                'in_reply_to' => $data['in_reply_to'] ?? $draft?->in_reply_to,
                'to_emails' => $data['to_emails'] ?? ($draft !== null ? ($draft->to_emails ?? []) : []),
                'cc_emails' => $data['cc_emails'] ?? ($draft !== null ? ($draft->cc_emails ?? []) : []),
                'bcc_emails' => $data['bcc_emails'] ?? ($draft !== null ? ($draft->bcc_emails ?? []) : []),
                'subject' => $data['subject'] ?? $draft?->subject,
                'body_html' => $data['body_html'] ?? $draft?->body_html,
                'body_plain' => $data['body_plain'] ?? strip_tags((string) ($data['body_html'] ?? $draft?->body_html)),
            ];

            if ($draft === null) {
                $draft = EmailDraft::query()->create($payload);
            } else {
                $draft->update($payload);
            }

            if ($keepAttachmentIds !== null) {
                if ($keepAttachmentIds === []) {
                    $draft->attachments()->delete();
                } else {
                    $draft->attachments()
                        ->whereNotIn('id', $keepAttachmentIds)
                        ->delete();
                }
            }

            foreach ($files as $file) {
                $this->attachments->storeUploadForDraft($draft, $file);
            }

            $this->uniqueEmails->remember([
                ...$payload['to_emails'],
                ...$payload['cc_emails'],
                ...$payload['bcc_emails'],
            ]);

            return $draft->refresh()->load('attachments');
        });
    }

    public function delete(EmailDraft $draft): void
    {
        $draft->delete();
    }

    /**
     * @return array{to_emails: list<string>, cc_emails: list<string>, bcc_emails: list<string>, subject: string, body_html: string, thread_id: int|null, in_reply_to: string|null}
     */
    public function replyPrefill(Email $email, bool $replyAll, EmailAccount $account): array
    {
        $subject = $email->subject;
        $prefix = str_starts_with(strtolower($subject), 're:') ? '' : 'Re: ';
        $to = [$email->from_email];
        $cc = [];

        if ($replyAll) {
            foreach ([...($email->to_emails ?? []), ...($email->cc_emails ?? [])] as $address) {
                if ($address !== $account->email && $address !== $email->from_email && ! in_array($address, $cc, true)) {
                    $cc[] = $address;
                }
            }
        }

        return [
            'to_emails' => $to,
            'cc_emails' => $cc,
            'bcc_emails' => [],
            'subject' => $prefix.$subject,
            'body_html' => $this->quotedBody($email),
            'thread_id' => $email->thread_id,
            'in_reply_to' => $email->message_id,
        ];
    }

    /**
     * @return array{to_emails: list<string>, cc_emails: list<string>, bcc_emails: list<string>, subject: string, body_html: string, thread_id: int|null, in_reply_to: string|null}
     */
    public function forwardPrefill(Email $email): array
    {
        $subject = $email->subject;
        $prefix = str_starts_with(strtolower($subject), 'fwd:') ? '' : 'Fwd: ';

        return [
            'to_emails' => [],
            'cc_emails' => [],
            'bcc_emails' => [],
            'subject' => $prefix.$subject,
            'body_html' => $this->quotedBody($email),
            'thread_id' => null,
            'in_reply_to' => null,
        ];
    }

    private function quotedBody(Email $email): string
    {
        $when = $email->internal_date?->format('D, M j, Y, g:i A') ?? '';
        $who = htmlspecialchars((string) ($email->from_name ?: $email->from_email), ENT_QUOTES, 'UTF-8');
        $intro = trim($when) === '' ? $who.' wrote:' : 'On '.$when.', '.$who.' wrote:';

        return '<br><br><div class="email-quoted-reply"><p>'.$intro.'</p><blockquote>'.$email->body_html.'</blockquote></div>';
    }
}
