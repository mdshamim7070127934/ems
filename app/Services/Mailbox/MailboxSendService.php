<?php

namespace App\Services\Mailbox;

use App\Enums\Mailbox\MailboxProvider;
use App\Enums\Master\SmtpEncryption;
use App\Events\MailboxEmailSent;
use App\Exceptions\EmailProviderException;
use App\Jobs\SyncMailboxAccountJob;
use App\Models\Mailbox\Email;
use App\Models\Mailbox\EmailAccount;
use App\Models\Mailbox\EmailDraft;
use App\Models\Mailbox\EmailThread;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email as MimeEmail;
use Throwable;

class MailboxSendService
{
    public function __construct(
        private GmailMailboxClient $gmail,
        private OutlookMailboxClient $outlook,
        private MailboxFolderService $folders,
        private MailboxAttachmentService $attachments,
        private UniqueEmailService $uniqueEmails,
        private MailboxOAuthConnectionService $oauth,
    ) {}

    /**
     * @return array{success: bool, error?: string}
     */
    public function sendDraft(EmailDraft $draft): array
    {
        $account = $draft->account;

        if ($account === null || ! $account->is_active) {
            return ['success' => false, 'error' => 'Mailbox account is inactive.'];
        }

        $to = $draft->to_emails ?? [];

        if ($to === []) {
            return ['success' => false, 'error' => 'Add at least one recipient.'];
        }

        $draft->loadMissing('attachments');

        try {
            $sent = match ($account->provider) {
                MailboxProvider::Gmail => $this->sendGmail($account, $draft),
                MailboxProvider::Outlook => $this->sendOutlook($account, $draft),
                MailboxProvider::SmtpImap => $this->sendSmtp($account, $draft),
            };

            if ($sent === null) {
                return ['success' => false, 'error' => 'Could not send the email.'];
            }

            $email = $this->recordSent($account, $draft, $sent);
            $draft->delete();
            MailboxEmailSent::dispatch($email);
            SyncMailboxAccountJob::dispatch($account);

            return ['success' => true];
        } catch (Throwable $exception) {
            Log::error('Mailbox send failed.', [
                'draft_id' => $draft->id,
                'email_account_id' => $account->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return ['success' => false, 'error' => 'Could not send the email.'];
        }
    }

    /**
     * @return array{rfc_message_id: string, provider_message_id: string|null, provider_thread_id: string|null}|null
     */
    private function sendGmail(EmailAccount $account, EmailDraft $draft): ?array
    {
        $this->oauth->ensureValidAccessToken($account);

        $mime = $this->mimeMessage($account, $draft);
        $identity = [
            'rfc_message_id' => $this->rfcMessageId($mime),
            'provider_message_id' => null,
            'provider_thread_id' => $this->providerThreadIdForSend($account, $draft),
        ];

        $result = $this->gmail->sendRaw($account, $mime->toString(), $identity['provider_thread_id']);

        if ($result === null) {
            return null;
        }

        $providerMessageId = isset($result['id']) && is_string($result['id']) ? $result['id'] : null;
        $providerThreadId = isset($result['threadId']) && is_string($result['threadId']) ? $result['threadId'] : null;

        if ($providerMessageId !== null) {
            $full = $this->gmail->getMessage($account, $providerMessageId);
            $headerMessageId = $this->gmailHeader($full, 'message-id');

            if ($headerMessageId !== null) {
                $identity['rfc_message_id'] = $headerMessageId;
            }

            if (is_array($full) && isset($full['threadId']) && is_string($full['threadId'])) {
                $providerThreadId = $full['threadId'];
            }
        }

        $identity['provider_message_id'] = $providerMessageId;
        $identity['provider_thread_id'] = $providerThreadId;

        return $identity;
    }

    /**
     * @return array{rfc_message_id: string, provider_message_id: string|null, provider_thread_id: string|null}|null
     */
    private function sendOutlook(EmailAccount $account, EmailDraft $draft): ?array
    {
        $this->oauth->ensureValidAccessToken($account);

        $mime = $this->mimeMessage($account, $draft);

        if (! $this->outlook->sendMailMime($account, $mime->toString())) {
            return null;
        }

        return [
            'rfc_message_id' => $this->rfcMessageId($mime),
            'provider_message_id' => null,
            'provider_thread_id' => null,
        ];
    }

    /**
     * @return array{rfc_message_id: string, provider_message_id: string|null, provider_thread_id: string|null}
     */
    private function sendSmtp(EmailAccount $account, EmailDraft $draft): array
    {
        if (blank($account->smtp_host) || $account->smtp_port === null) {
            throw EmailProviderException::sendFailed();
        }

        $mailer = 'mailbox_account_'.$account->id;

        config([
            "mail.mailers.{$mailer}" => [
                'transport' => 'smtp',
                'host' => $account->smtp_host,
                'port' => $account->smtp_port,
                'encryption' => $account->smtp_encryption === SmtpEncryption::None
                    ? null
                    : $account->smtp_encryption?->value,
                'username' => $account->smtp_username ?: $account->email,
                'password' => $account->smtp_password,
                'timeout' => 15,
            ],
        ]);

        $mime = $this->mimeMessage($account, $draft);
        $rfcMessageId = $this->rfcMessageId($mime);

        Mail::mailer($mailer)->html((string) $draft->body_html, function (Message $outgoing) use ($account, $draft, $rfcMessageId): void {
            $outgoing->from($account->email, (string) $account->display_name);
            $outgoing->subject((string) $draft->subject);

            foreach ($draft->to_emails ?? [] as $address) {
                $outgoing->to($address);
            }

            foreach ($draft->cc_emails ?? [] as $address) {
                $outgoing->cc($address);
            }

            foreach ($draft->bcc_emails ?? [] as $address) {
                $outgoing->bcc($address);
            }

            $text = (string) ($draft->body_plain ?: strip_tags((string) $draft->body_html));

            if ($text !== '') {
                $outgoing->text($text);
            }

            $outgoing->getHeaders()->addIdHeader('Message-ID', [$rfcMessageId]);

            if (filled($draft->in_reply_to)) {
                $replyId = $this->normalizeMessageId((string) $draft->in_reply_to);
                $outgoing->getHeaders()->addIdHeader('In-Reply-To', [$replyId]);
                $outgoing->getHeaders()->addTextHeader('References', '<'.$replyId.'>');
            }

            foreach ($draft->attachments as $attachment) {
                $outgoing->attachData(
                    $this->attachments->contents($attachment),
                    $attachment->original_filename ?: $attachment->filename,
                    ['mime' => (string) $attachment->mime_type],
                );
            }
        });

        return [
            'rfc_message_id' => $rfcMessageId,
            'provider_message_id' => null,
            'provider_thread_id' => null,
        ];
    }

    /**
     * @param  array{rfc_message_id: string, provider_message_id: string|null, provider_thread_id: string|null}  $identity
     */
    private function recordSent(EmailAccount $account, EmailDraft $draft, array $identity): Email
    {
        $rfcMessageId = $this->normalizeMessageId($identity['rfc_message_id']);

        if ($rfcMessageId === '') {
            $rfcMessageId = Str::uuid().'@'.Str::after($account->email, '@');
        }

        $existing = Email::query()
            ->where('account_id', $account->id)
            ->where(function ($query) use ($rfcMessageId, $identity): void {
                $query->where('message_id', $rfcMessageId);

                if (is_string($identity['provider_message_id']) && $identity['provider_message_id'] !== '') {
                    $query->orWhere('provider_message_id', $identity['provider_message_id'])
                        ->orWhere('message_id', $identity['provider_message_id']);
                }
            })
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $folder = $this->folders->ensureSent($account);
        $snippet = Str::limit(strip_tags((string) $draft->body_html), 500, '');
        $thread = $this->threadForSent($account, $draft, $identity['provider_thread_id'], $snippet);

        $email = Email::query()->create([
            'account_id' => $account->id,
            'thread_id' => $thread->id,
            'message_id' => $rfcMessageId,
            'provider_message_id' => $identity['provider_message_id'],
            'folder_id' => $folder->id,
            'from_email' => $account->email,
            'from_name' => $account->display_name,
            'to_emails' => $draft->to_emails ?? [],
            'cc_emails' => $draft->cc_emails ?? [],
            'bcc_emails' => $draft->bcc_emails ?? [],
            'subject' => (string) $draft->subject,
            'snippet' => $snippet,
            'body_html' => $draft->body_html,
            'body_plain' => $draft->body_plain ?: strip_tags((string) $draft->body_html),
            'internal_date' => now(),
            'is_unread' => false,
            'is_starred' => false,
            'is_draft' => false,
            'in_reply_to' => filled($draft->in_reply_to) ? $this->normalizeMessageId((string) $draft->in_reply_to) : null,
        ]);

        foreach ($draft->attachments as $attachment) {
            $this->attachments->copyDraftAttachmentToEmail($email, $attachment);
        }

        $this->uniqueEmails->remember([
            ...($draft->to_emails ?? []),
            ...($draft->cc_emails ?? []),
            ...($draft->bcc_emails ?? []),
        ]);

        $thread->update([
            'last_message_at' => now(),
            'message_count' => $thread->emails()->where('is_draft', false)->count(),
            'snippet' => $email->snippet,
        ]);

        return $email;
    }

    private function threadForSent(
        EmailAccount $account,
        EmailDraft $draft,
        ?string $providerThreadId,
        string $snippet,
    ): EmailThread {
        $replyThread = $draft->thread_id !== null
            ? EmailThread::query()->where('account_id', $account->id)->find($draft->thread_id)
            : null;

        if (is_string($providerThreadId) && $providerThreadId !== '') {
            $providerThread = EmailThread::query()
                ->where('account_id', $account->id)
                ->where('thread_id', $providerThreadId)
                ->first();

            if ($providerThread !== null) {
                return $providerThread;
            }

            if ($replyThread !== null) {
                if ($this->isLocalThreadId($replyThread->thread_id)) {
                    $replyThread->update(['thread_id' => $providerThreadId]);
                }

                return $replyThread->refresh();
            }

            return EmailThread::query()->create([
                'account_id' => $account->id,
                'thread_id' => $providerThreadId,
                'subject' => $draft->subject,
                'snippet' => $snippet,
                'last_message_at' => now(),
                'message_count' => 0,
            ]);
        }

        if ($replyThread !== null) {
            return $replyThread;
        }

        return EmailThread::query()->create([
            'account_id' => $account->id,
            'thread_id' => 'local-'.Str::uuid(),
            'subject' => $draft->subject,
            'snippet' => $snippet,
            'last_message_at' => now(),
            'message_count' => 0,
        ]);
    }

    private function mimeMessage(EmailAccount $account, EmailDraft $draft): MimeEmail
    {
        $message = (new MimeEmail)
            ->from(new Address($account->email, (string) $account->display_name))
            ->subject((string) $draft->subject)
            ->html((string) $draft->body_html)
            ->text((string) ($draft->body_plain ?: strip_tags((string) $draft->body_html)));

        foreach ($draft->to_emails ?? [] as $address) {
            $message->addTo($address);
        }

        foreach ($draft->cc_emails ?? [] as $address) {
            $message->addCc($address);
        }

        foreach ($draft->bcc_emails ?? [] as $address) {
            $message->addBcc($address);
        }

        if (filled($draft->in_reply_to)) {
            $replyId = $this->normalizeMessageId((string) $draft->in_reply_to);
            $message->getHeaders()->addIdHeader('In-Reply-To', [$replyId]);
            $message->getHeaders()->addTextHeader('References', '<'.$replyId.'>');
        }

        foreach ($draft->attachments as $attachment) {
            $message->attach(
                $this->attachments->contents($attachment),
                $attachment->original_filename ?: $attachment->filename,
                $attachment->mime_type,
            );
        }

        $this->ensureRfcMessageId($message, $account);

        return $message;
    }

    private function ensureRfcMessageId(MimeEmail $message, EmailAccount $account): string
    {
        $existing = $this->rfcMessageId($message);

        if ($existing !== '') {
            return $existing;
        }

        $domain = Str::after(Str::lower($account->email), '@');
        $messageId = Str::uuid().'@'.($domain !== '' ? $domain : 'localhost');
        $message->getHeaders()->addIdHeader('Message-ID', $messageId);

        return $messageId;
    }

    private function providerThreadIdForSend(EmailAccount $account, EmailDraft $draft): ?string
    {
        if ($draft->thread_id === null) {
            return null;
        }

        $thread = $draft->relationLoaded('thread')
            ? $draft->thread
            : EmailThread::query()->where('account_id', $account->id)->find($draft->thread_id);

        if ($thread === null || $this->isLocalThreadId($thread->thread_id)) {
            return null;
        }

        return $thread->thread_id;
    }

    private function rfcMessageId(MimeEmail $message): string
    {
        $header = $message->getHeaders()->get('Message-ID');

        if ($header === null) {
            return '';
        }

        return $this->normalizeMessageId($header->getBodyAsString());
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function gmailHeader(?array $payload, string $name): ?string
    {
        if ($payload === null) {
            return null;
        }

        $headers = is_array($payload['payload']['headers'] ?? null) ? $payload['payload']['headers'] : [];

        foreach ($headers as $header) {
            if (! is_array($header) || ! isset($header['name'], $header['value'])) {
                continue;
            }

            if (strtolower((string) $header['name']) !== $name) {
                continue;
            }

            $value = $this->normalizeMessageId((string) $header['value']);

            return $value !== '' ? $value : null;
        }

        return null;
    }

    private function normalizeMessageId(string $messageId): string
    {
        return trim($messageId, " \t<>");
    }

    private function isLocalThreadId(string $threadId): bool
    {
        return str_starts_with($threadId, 'local-') || str_starts_with($threadId, 'sent-');
    }
}
