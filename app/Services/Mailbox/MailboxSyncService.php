<?php

namespace App\Services\Mailbox;

use App\Enums\Mailbox\MailboxProvider;
use App\Events\MailboxEmailReceived;
use App\Events\MailboxSynced;
use App\Models\Mailbox\Email;
use App\Models\Mailbox\EmailAccount;
use App\Models\Mailbox\EmailAccountSyncState;
use App\Models\Mailbox\EmailFolder;
use App\Models\Mailbox\EmailThread;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class MailboxSyncService
{
    public function __construct(
        private GmailMailboxClient $gmail,
        private OutlookMailboxClient $outlook,
        private ImapMailboxClient $imap,
        private MailboxFolderService $folders,
        private MailboxAttachmentService $attachments,
        private UniqueEmailService $uniqueEmails,
    ) {}

    /**
     * @return array{success: bool, synced: int, error?: string}
     */
    public function syncAccount(EmailAccount $account): array
    {
        if (! $account->is_active) {
            return ['success' => false, 'synced' => 0, 'error' => 'Account is inactive.'];
        }

        try {
            $synced = match ($account->provider) {
                MailboxProvider::Gmail => $this->syncGmail($account),
                MailboxProvider::Outlook => $this->syncOutlook($account),
                MailboxProvider::SmtpImap => $this->syncImap($account),
            };

            $this->collapseLocalSentCopies($account);
            $this->refreshThreadCounts($account);

            MailboxSynced::dispatch($account, $synced);

            return ['success' => true, 'synced' => $synced];
        } catch (Throwable $exception) {
            Log::error('Mailbox sync failed.', [
                'email_account_id' => $account->id,
                'provider' => $account->provider->value,
                'exception' => $exception::class,
            ]);

            return ['success' => false, 'synced' => 0, 'error' => 'Mailbox sync failed.'];
        }
    }

    private function syncGmail(EmailAccount $account): int
    {
        $this->syncGmailFolders($account);
        $state = $this->state($account);
        $max = $this->maxMessages();
        $threadIds = $this->gmailChangedThreadIds($account, $state, $max);

        if ($threadIds === []) {
            $threadIds = $this->gmailRecentThreadIds($account, $max);
        }

        $synced = 0;

        foreach ($threadIds as $providerThreadId) {
            $threadPayload = $this->gmail->getThread($account, $providerThreadId);

            if ($threadPayload === null) {
                continue;
            }

            $messages = is_array($threadPayload['messages'] ?? null) ? array_values($threadPayload['messages']) : [];
            $localThread = $this->upsertThread(
                $account,
                $providerThreadId,
                $this->gmailThreadSubject($messages),
                is_string($threadPayload['snippet'] ?? null) ? $threadPayload['snippet'] : null,
            );

            foreach ($messages as $message) {
                if (! is_array($message)) {
                    continue;
                }

                if ($this->storeGmailMessage($account, $localThread, $message)) {
                    $synced++;
                }

                if ($synced >= $max) {
                    break 2;
                }
            }
        }

        $profile = $this->gmail->getProfile($account);

        if (is_string($profile['historyId'] ?? null)) {
            $state->update(['gmail_history_id' => $profile['historyId']]);
        }

        return $synced;
    }

    /**
     * @return list<string>
     */
    private function gmailChangedThreadIds(EmailAccount $account, EmailAccountSyncState $state, int $max): array
    {
        $historyId = $state->gmail_history_id;

        if ($historyId === null || $historyId === '') {
            return [];
        }

        $threadIds = [];
        $pageToken = null;
        $latestHistoryId = $historyId;

        do {
            $page = $this->gmail->listHistory($account, $historyId, $pageToken);

            if ($page === null) {
                $state->update(['gmail_history_id' => null]);

                return [];
            }

            foreach ($page['history'] as $entry) {
                foreach (['messagesAdded', 'messages'] as $key) {
                    foreach (is_array($entry[$key] ?? null) ? $entry[$key] : [] as $item) {
                        $threadId = is_array($item) ? ($item['threadId'] ?? ($item['message']['threadId'] ?? null)) : null;

                        if (is_array($item) && isset($item['message']) && is_array($item['message'])) {
                            $threadId = $item['message']['threadId'] ?? $threadId;
                        }

                        if (is_string($threadId) && $threadId !== '') {
                            $threadIds[$threadId] = $threadId;
                        }
                    }
                }
            }

            if (is_string($page['historyId'])) {
                $latestHistoryId = $page['historyId'];
            }

            $pageToken = $page['nextPageToken'];
        } while ($pageToken !== null && count($threadIds) < $max);

        $state->update(['gmail_history_id' => $latestHistoryId]);

        return array_values($threadIds);
    }

    /**
     * @return list<string>
     */
    private function gmailRecentThreadIds(EmailAccount $account, int $max): array
    {
        $ids = [];

        foreach (['INBOX', 'SENT'] as $label) {
            $page = $this->gmail->listThreadIds($account, $label, $max);

            foreach ($page['threads'] as $thread) {
                $ids[$thread['id']] = $thread['id'];
            }
        }

        return array_slice(array_values($ids), 0, $max);
    }

    private function syncGmailFolders(EmailAccount $account): void
    {
        foreach ($this->gmail->listLabels($account) as $label) {
            $id = $label['id'] ?? null;
            $name = $label['name'] ?? null;

            if (! is_string($id) || ! is_string($name) || $name === '') {
                continue;
            }

            $meta = $this->folders->fromGmailLabel($id, $name);
            $this->folders->upsert($account, $meta['slug'], $meta['name'], $meta['icon'], $meta['type'], $id);
        }
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function storeGmailMessage(EmailAccount $account, EmailThread $thread, array $message): bool
    {
        $parsed = $this->parseGmailMessage($message);
        $providerMessageId = isset($message['id']) && is_string($message['id']) ? $message['id'] : null;
        $providerThreadId = isset($message['threadId']) && is_string($message['threadId'])
            ? $message['threadId']
            : $thread->thread_id;

        $folder = $this->gmailFolderForMessage($account, $message);
        $replyThread = $this->threadFromReply($account, $parsed['in_reply_to']);
        $targetThread = $this->bindProviderThread($account, $replyThread ?? $thread, $providerThreadId);
        $existing = $this->matchStoredMessage($account, $parsed, $providerMessageId);

        if ($existing !== null) {
            $previousThreadId = $existing->thread_id;

            $existing->update([
                'message_id' => $parsed['message_id'],
                'provider_message_id' => $providerMessageId ?? $existing->provider_message_id,
                'thread_id' => $targetThread->id,
                'in_reply_to' => $parsed['in_reply_to'] ?? $existing->in_reply_to,
                'references' => $parsed['references'] ?? $existing->references,
            ]);

            $this->deleteOrphanThread($previousThreadId, $targetThread->id);

            return false;
        }

        $email = $this->insertEmail($account, $targetThread->id, $folder?->id, $parsed, $providerMessageId);

        $this->storeGmailAttachments($account, $email, $message);
        $this->rememberAddresses($parsed);
        MailboxEmailReceived::dispatch($email);

        return true;
    }

    /**
     * @param  array<string, mixed>  $message
     * @return array{
     *     message_id: string,
     *     from_email: string,
     *     from_name: string|null,
     *     to_emails: list<string>,
     *     cc_emails: list<string>,
     *     bcc_emails: list<string>,
     *     subject: string,
     *     snippet: string|null,
     *     body_html: string,
     *     body_plain: string,
     *     internal_date: string|null,
     *     is_unread: bool,
     *     is_starred: bool,
     *     is_draft: bool,
     *     in_reply_to: string|null,
     *     references: string|null
     * }
     */
    private function parseGmailMessage(array $message): array
    {
        $payload = is_array($message['payload'] ?? null) ? $message['payload'] : [];
        $headers = [];

        foreach (is_array($payload['headers'] ?? null) ? $payload['headers'] : [] as $header) {
            if (is_array($header) && isset($header['name'], $header['value']) && is_string($header['name']) && is_string($header['value'])) {
                $headers[strtolower($header['name'])] = $header['value'];
            }
        }

        $from = $this->parseAddress($headers['from'] ?? '');
        $bodies = $this->extractGmailBodies($payload);
        $labelIds = is_array($message['labelIds'] ?? null) ? $message['labelIds'] : [];
        $internalDate = isset($message['internalDate']) && is_numeric($message['internalDate'])
            ? date('Y-m-d H:i:s', (int) ((int) $message['internalDate'] / 1000))
            : null;

        return [
            'message_id' => isset($headers['message-id'])
                ? trim($headers['message-id'], '<>')
                : (string) ($message['id'] ?? Str::uuid()),
            'from_email' => $from['email'],
            'from_name' => $from['name'],
            'to_emails' => $this->splitAddresses($headers['to'] ?? ''),
            'cc_emails' => $this->splitAddresses($headers['cc'] ?? ''),
            'bcc_emails' => $this->splitAddresses($headers['bcc'] ?? ''),
            'subject' => $headers['subject'] ?? '',
            'snippet' => is_string($message['snippet'] ?? null) ? $message['snippet'] : null,
            'body_html' => $bodies['html'],
            'body_plain' => $bodies['plain'],
            'internal_date' => $internalDate,
            'is_unread' => in_array('UNREAD', $labelIds, true),
            'is_starred' => in_array('STARRED', $labelIds, true),
            'is_draft' => in_array('DRAFT', $labelIds, true),
            'in_reply_to' => isset($headers['in-reply-to']) ? trim($headers['in-reply-to'], '<>') : null,
            'references' => $headers['references'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{html: string, plain: string}
     */
    private function extractGmailBodies(array $payload): array
    {
        $html = '';
        $plain = '';
        $this->walkGmailParts($payload, $html, $plain);

        if ($plain === '' && $html !== '') {
            $plain = strip_tags($html);
        }

        if ($html === '' && $plain !== '') {
            $html = nl2br(e($plain));
        }

        return ['html' => $html, 'plain' => $plain];
    }

    /**
     * @param  array<string, mixed>  $part
     */
    private function walkGmailParts(array $part, string &$html, string &$plain): void
    {
        $mime = is_string($part['mimeType'] ?? null) ? $part['mimeType'] : '';
        $data = is_array($part['body'] ?? null) ? ($part['body']['data'] ?? null) : null;

        if (is_string($data) && $data !== '') {
            $decoded = (string) base64_decode(strtr($data, '-_', '+/'), true);

            if (str_contains($mime, 'html')) {
                $html = $decoded;
            } elseif (str_contains($mime, 'plain')) {
                $plain = $decoded;
            }
        }

        foreach (is_array($part['parts'] ?? null) ? $part['parts'] : [] as $child) {
            if (is_array($child)) {
                $this->walkGmailParts($child, $html, $plain);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function gmailFolderForMessage(EmailAccount $account, array $message): ?EmailFolder
    {
        $labelIds = is_array($message['labelIds'] ?? null) ? $message['labelIds'] : [];

        foreach (['SENT', 'INBOX'] as $preferred) {
            if (in_array($preferred, $labelIds, true)) {
                $folder = EmailFolder::query()
                    ->where('account_id', $account->id)
                    ->where('provider_folder_id', $preferred)
                    ->first();

                if ($folder !== null) {
                    return $folder;
                }
            }
        }

        foreach ($labelIds as $labelId) {
            if (! is_string($labelId)) {
                continue;
            }

            $folder = EmailFolder::query()
                ->where('account_id', $account->id)
                ->where('provider_folder_id', $labelId)
                ->first();

            if ($folder !== null) {
                return $folder;
            }
        }

        return EmailFolder::query()
            ->where('account_id', $account->id)
            ->where('slug', 'inbox')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function storeGmailAttachments(EmailAccount $account, Email $email, array $message): void
    {
        $payload = is_array($message['payload'] ?? null) ? $message['payload'] : [];
        $providerMessageId = is_string($message['id'] ?? null) ? $message['id'] : null;

        if ($providerMessageId === null) {
            return;
        }

        $this->walkGmailAttachments($account, $email, $providerMessageId, $payload);
    }

    /**
     * @param  array<string, mixed>  $part
     */
    private function walkGmailAttachments(EmailAccount $account, Email $email, string $providerMessageId, array $part): void
    {
        $filename = $part['filename'] ?? null;
        $attachmentId = is_array($part['body'] ?? null) ? ($part['body']['attachmentId'] ?? null) : null;

        if (is_string($filename) && $filename !== '' && is_string($attachmentId) && $attachmentId !== '') {
            $contents = $this->gmail->getAttachment($account, $providerMessageId, $attachmentId);

            if ($contents !== null) {
                $headers = [];

                foreach (is_array($part['headers'] ?? null) ? $part['headers'] : [] as $header) {
                    if (is_array($header) && isset($header['name'], $header['value'])) {
                        $headers[strtolower((string) $header['name'])] = (string) $header['value'];
                    }
                }

                $this->attachments->storeForEmail(
                    $email,
                    $filename,
                    $contents,
                    is_string($part['mimeType'] ?? null) ? $part['mimeType'] : null,
                    $headers['content-id'] ?? null,
                );
            }
        }

        foreach (is_array($part['parts'] ?? null) ? $part['parts'] : [] as $child) {
            if (is_array($child)) {
                $this->walkGmailAttachments($account, $email, $providerMessageId, $child);
            }
        }
    }

    /**
     * @param  list<mixed>  $messages
     */
    private function gmailThreadSubject(array $messages): ?string
    {
        foreach ($messages as $message) {
            if (! is_array($message)) {
                continue;
            }

            $parsed = $this->parseGmailMessage($message);

            if ($parsed['subject'] !== '') {
                return $parsed['subject'];
            }
        }

        return null;
    }

    private function syncOutlook(EmailAccount $account): int
    {
        $folderMap = $this->syncOutlookFolders($account);
        $state = $this->state($account);
        $synced = 0;
        $max = $this->maxMessages();

        foreach (['inbox' => 'inbox', 'sentitems' => 'sent'] as $wellKnown => $slug) {
            $folder = $folderMap[$slug] ?? EmailFolder::query()
                ->where('account_id', $account->id)
                ->where('slug', $slug)
                ->first();

            if ($folder === null || $folder->provider_folder_id === null) {
                continue;
            }

            $deltaKey = $wellKnown === 'inbox' ? 'outlook_delta_inbox' : 'outlook_delta_sent';
            $result = $this->outlook->listMessagesDelta($account, $folder->provider_folder_id, $state->{$deltaKey});
            $state->update([$deltaKey => $result['deltaLink']]);

            foreach ($result['messages'] as $message) {
                if ($this->storeOutlookMessage($account, $folder, $message)) {
                    $synced++;
                }

                if ($synced >= $max) {
                    return $synced;
                }
            }
        }

        return $synced;
    }

    /**
     * @return array<string, EmailFolder>
     */
    private function syncOutlookFolders(EmailAccount $account): array
    {
        $map = [];

        foreach ($this->outlook->listMailFolders($account) as $folder) {
            $id = $folder['id'] ?? null;
            $name = $folder['displayName'] ?? null;

            if (! is_string($id) || ! is_string($name)) {
                continue;
            }

            $wellKnown = is_string($folder['wellKnownName'] ?? null) ? $folder['wellKnownName'] : null;
            $meta = $this->folders->fromOutlookFolder($wellKnown, $name);
            $map[$meta['slug']] = $this->folders->upsert(
                $account,
                $meta['slug'],
                $meta['name'],
                $meta['icon'],
                $meta['type'],
                $id,
            );
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function storeOutlookMessage(EmailAccount $account, EmailFolder $folder, array $message): bool
    {
        $parsed = $this->parseOutlookMessage($message);
        $providerMessageId = isset($message['id']) && is_string($message['id']) ? $message['id'] : null;
        $providerThreadId = is_string($message['conversationId'] ?? null)
            ? $message['conversationId']
            : (string) ($message['id'] ?? $parsed['message_id']);

        $thread = $this->bindProviderThread(
            $account,
            $this->threadFromReply($account, $parsed['in_reply_to'])
                ?? $this->upsertThread($account, $providerThreadId, $parsed['subject'], $parsed['snippet']),
            $providerThreadId,
        );
        $existing = $this->matchStoredMessage($account, $parsed, $providerMessageId);

        if ($existing !== null) {
            $previousThreadId = $existing->thread_id;

            $existing->update([
                'message_id' => $parsed['message_id'],
                'provider_message_id' => $providerMessageId ?? $existing->provider_message_id,
                'thread_id' => $thread->id,
                'in_reply_to' => $parsed['in_reply_to'] ?? $existing->in_reply_to,
                'references' => $parsed['references'] ?? $existing->references,
            ]);

            $this->deleteOrphanThread($previousThreadId, $thread->id);

            return false;
        }

        $email = $this->insertEmail($account, $thread->id, $folder->id, $parsed, $providerMessageId);

        if (! empty($message['hasAttachments'])) {
            foreach ($this->outlook->getMessageAttachments($account, (string) $message['id']) as $attachment) {
                $this->attachments->storeForEmail(
                    $email,
                    $attachment['name'],
                    $attachment['content'],
                    $attachment['mimeType'],
                    $attachment['contentId'],
                );
            }
        }

        $this->rememberAddresses($parsed);
        MailboxEmailReceived::dispatch($email);

        return true;
    }

    /**
     * @param  array<string, mixed>  $message
     * @return array{
     *     message_id: string,
     *     from_email: string,
     *     from_name: string|null,
     *     to_emails: list<string>,
     *     cc_emails: list<string>,
     *     bcc_emails: list<string>,
     *     subject: string,
     *     snippet: string|null,
     *     body_html: string,
     *     body_plain: string,
     *     internal_date: string|null,
     *     is_unread: bool,
     *     is_starred: bool,
     *     is_draft: bool,
     *     in_reply_to: string|null,
     *     references: string|null
     * }
     */
    private function parseOutlookMessage(array $message): array
    {
        $from = is_array($message['from']['emailAddress'] ?? null) ? $message['from']['emailAddress'] : [];
        $to = [];
        $cc = [];

        foreach (is_array($message['toRecipients'] ?? null) ? $message['toRecipients'] : [] as $recipient) {
            $address = $recipient['emailAddress']['address'] ?? null;

            if (is_string($address) && $address !== '') {
                $to[] = $address;
            }
        }

        foreach (is_array($message['ccRecipients'] ?? null) ? $message['ccRecipients'] : [] as $recipient) {
            $address = $recipient['emailAddress']['address'] ?? null;

            if (is_string($address) && $address !== '') {
                $cc[] = $address;
            }
        }

        $body = is_string($message['body']['content'] ?? null) ? $message['body']['content'] : '';
        $bodyType = strtolower((string) ($message['body']['contentType'] ?? 'html'));
        $html = $body;
        $plain = strip_tags($body);

        if ($bodyType === 'text') {
            $plain = $body;
            $html = nl2br(e($body));
        }

        $inReplyTo = null;

        foreach (is_array($message['internetMessageHeaders'] ?? null) ? $message['internetMessageHeaders'] : [] as $header) {
            if (is_array($header) && strtolower((string) ($header['name'] ?? '')) === 'in-reply-to') {
                $inReplyTo = trim((string) ($header['value'] ?? ''), '<>');
            }
        }

        $received = isset($message['receivedDateTime']) && is_string($message['receivedDateTime'])
            ? date('Y-m-d H:i:s', strtotime($message['receivedDateTime']) ?: time())
            : null;

        return [
            'message_id' => isset($message['internetMessageId'])
                ? trim((string) $message['internetMessageId'], '<>')
                : (string) ($message['id'] ?? ''),
            'from_email' => is_string($from['address'] ?? null) ? $from['address'] : '',
            'from_name' => is_string($from['name'] ?? null) ? $from['name'] : null,
            'to_emails' => $to,
            'cc_emails' => $cc,
            'bcc_emails' => [],
            'subject' => is_string($message['subject'] ?? null) ? $message['subject'] : '',
            'snippet' => is_string($message['bodyPreview'] ?? null) ? $message['bodyPreview'] : null,
            'body_html' => $html,
            'body_plain' => $plain,
            'internal_date' => $received,
            'is_unread' => ! ($message['isRead'] ?? false),
            'is_starred' => false,
            'is_draft' => (bool) ($message['isDraft'] ?? false),
            'in_reply_to' => $inReplyTo,
            'references' => null,
        ];
    }

    private function syncImap(EmailAccount $account): int
    {
        foreach ($this->imap->listMailboxes($account) as $mailbox) {
            $meta = $this->folders->fromImapMailbox($mailbox['name']);
            $this->folders->upsert(
                $account,
                $meta['slug'],
                $meta['name'],
                $meta['icon'],
                $meta['type'],
                $mailbox['path'],
            );
        }

        $inbox = EmailFolder::query()
            ->where('account_id', $account->id)
            ->where('slug', 'inbox')
            ->first();

        $state = $this->state($account);
        $numbers = $this->imap->fetchMessageNumbers(
            $account,
            'INBOX',
            $state->imap_last_sync_at,
            $this->maxMessages(),
        );

        $synced = 0;

        foreach ($numbers as $uid) {
            $parsed = $this->imap->getMessage($account, 'INBOX', $uid);

            if ($parsed === null) {
                continue;
            }

            if ($this->matchStoredMessage($account, $parsed, (string) $uid) !== null) {
                continue;
            }

            $thread = $this->threadFromReply($account, $parsed['in_reply_to'])
                ?? $this->upsertThread($account, 'imap-'.$account->id.'-'.$uid, $parsed['subject'], Str::limit($parsed['body_plain'], 500, ''));

            $this->insertEmail($account, $thread->id, $inbox?->id, [
                ...$parsed,
                'snippet' => Str::limit($parsed['body_plain'], 500, ''),
                'bcc_emails' => [],
                'is_starred' => false,
                'is_draft' => false,
            ], (string) $uid);

            $this->rememberAddresses($parsed);
            $synced++;
        }

        $state->update(['imap_last_sync_at' => now()]);

        return $synced;
    }

    /**
     * @param  array{
     *     message_id: string,
     *     from_email: string,
     *     from_name?: string|null,
     *     to_emails: list<string>,
     *     cc_emails?: list<string>,
     *     bcc_emails?: list<string>,
     *     subject: string,
     *     snippet?: string|null,
     *     body_html: string,
     *     body_plain: string,
     *     internal_date?: string|null,
     *     is_unread: bool,
     *     is_starred?: bool,
     *     is_draft?: bool,
     *     in_reply_to?: string|null,
     *     references?: string|null
     * }  $parsed
     */
    private function insertEmail(EmailAccount $account, int $threadId, ?int $folderId, array $parsed, mixed $providerMessageId): Email
    {
        return Email::query()->create([
            'account_id' => $account->id,
            'thread_id' => $threadId,
            'message_id' => $parsed['message_id'],
            'provider_message_id' => is_string($providerMessageId) ? $providerMessageId : null,
            'folder_id' => $folderId,
            'from_email' => $parsed['from_email'],
            'from_name' => $parsed['from_name'] ?? null,
            'to_emails' => $parsed['to_emails'],
            'cc_emails' => $parsed['cc_emails'] ?? [],
            'bcc_emails' => $parsed['bcc_emails'] ?? [],
            'subject' => $parsed['subject'],
            'snippet' => $parsed['snippet'] ?? null,
            'body_html' => $parsed['body_html'],
            'body_plain' => $parsed['body_plain'],
            'internal_date' => $parsed['internal_date'] ?? null,
            'is_unread' => $parsed['is_unread'],
            'is_starred' => $parsed['is_starred'] ?? false,
            'is_draft' => $parsed['is_draft'] ?? false,
            'in_reply_to' => $parsed['in_reply_to'] ?? null,
            'references' => $parsed['references'] ?? null,
        ]);
    }

    private function upsertThread(EmailAccount $account, string $providerThreadId, ?string $subject, ?string $snippet): EmailThread
    {
        return EmailThread::query()->firstOrCreate(
            [
                'account_id' => $account->id,
                'thread_id' => $providerThreadId,
            ],
            [
                'subject' => $subject,
                'snippet' => $snippet,
                'last_message_at' => now(),
                'message_count' => 0,
            ],
        );
    }

    private function threadFromReply(EmailAccount $account, ?string $inReplyTo): ?EmailThread
    {
        if ($inReplyTo === null || $inReplyTo === '') {
            return null;
        }

        $parent = Email::query()
            ->where('account_id', $account->id)
            ->where('message_id', $this->normalizeMessageId($inReplyTo))
            ->first();

        return $parent?->thread;
    }

    /**
     * @param  array{message_id: string, from_email?: string, subject?: string, is_draft?: bool, internal_date?: string|null}  $parsed
     */
    private function matchStoredMessage(EmailAccount $account, array $parsed, ?string $providerMessageId): ?Email
    {
        $messageId = $this->normalizeMessageId($parsed['message_id']);
        $existing = $this->findExistingMessage($account, $messageId, $providerMessageId);

        if (($parsed['is_draft'] ?? false) === true) {
            return $existing;
        }

        if ($existing !== null) {
            $placeholder = $this->findLocalSentPlaceholder($account, $parsed);

            if ($placeholder !== null && $placeholder->id !== $existing->id) {
                $placeholderThreadId = $placeholder->thread_id;
                $placeholder->delete();
                $this->deleteOrphanThread($placeholderThreadId, $existing->thread_id);
            }

            return $existing;
        }

        return $this->findLocalSentPlaceholder($account, $parsed);
    }

    private function findExistingMessage(EmailAccount $account, string $messageId, ?string $providerMessageId): ?Email
    {
        return Email::query()
            ->where('account_id', $account->id)
            ->where(function ($query) use ($messageId, $providerMessageId): void {
                $query->where('message_id', $messageId);

                if (is_string($providerMessageId) && $providerMessageId !== '') {
                    $query->orWhere('provider_message_id', $providerMessageId)
                        ->orWhere('message_id', $providerMessageId);
                }
            })
            ->first();
    }

    /**
     * @param  array{from_email?: string, subject?: string, internal_date?: string|null}  $parsed
     */
    private function findLocalSentPlaceholder(EmailAccount $account, array $parsed): ?Email
    {
        $from = strtolower((string) ($parsed['from_email'] ?? ''));

        if ($from === '' || strcasecmp($from, $account->email) !== 0) {
            return null;
        }

        $syncedAt = isset($parsed['internal_date']) && is_string($parsed['internal_date']) && $parsed['internal_date'] !== ''
            ? Carbon::parse($parsed['internal_date'])
            : now();

        $candidates = Email::query()
            ->where('account_id', $account->id)
            ->whereRaw('LOWER(from_email) = ?', [strtolower($account->email)])
            ->where('subject', (string) ($parsed['subject'] ?? ''))
            ->whereNull('provider_message_id')
            ->where('is_draft', false)
            ->whereBetween('internal_date', [$syncedAt->copy()->subDay(), $syncedAt->copy()->addDay()])
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return $candidates
            ->sortBy(fn (Email $email): int => abs((int) $email->internal_date?->diffInSeconds($syncedAt)))
            ->first();
    }

    private function collapseLocalSentCopies(EmailAccount $account): void
    {
        $placeholders = Email::query()
            ->where('account_id', $account->id)
            ->whereNull('provider_message_id')
            ->where('is_draft', false)
            ->whereRaw('LOWER(from_email) = ?', [strtolower($account->email)])
            ->where(function ($query): void {
                $query->where('message_id', 'not like', '%@%')
                    ->orWhereHas('thread', function ($thread): void {
                        $thread->where('thread_id', 'like', 'local-%')
                            ->orWhere('thread_id', 'like', 'sent-%');
                    });
            })
            ->orderBy('id')
            ->get();

        foreach ($placeholders as $placeholder) {
            $canonical = $this->findCanonicalSentCopy($account, $placeholder);

            if ($canonical === null) {
                continue;
            }

            $placeholderThreadId = $placeholder->thread_id;
            $placeholder->delete();
            $this->deleteOrphanThread($placeholderThreadId, $canonical->thread_id);
        }
    }

    private function findCanonicalSentCopy(EmailAccount $account, Email $placeholder): ?Email
    {
        $syncedAt = $placeholder->internal_date ?? now();

        return Email::query()
            ->where('account_id', $account->id)
            ->whereKeyNot($placeholder->id)
            ->whereNotNull('provider_message_id')
            ->where('is_draft', false)
            ->where('subject', $placeholder->subject)
            ->whereRaw('LOWER(from_email) = ?', [strtolower((string) $placeholder->from_email)])
            ->whereBetween('internal_date', [$syncedAt->copy()->subDay(), $syncedAt->copy()->addDay()])
            ->orderByDesc('id')
            ->first();
    }

    private function deleteOrphanThread(?int $previousThreadId, int $keptThreadId): void
    {
        if ($previousThreadId === null || $previousThreadId === $keptThreadId) {
            return;
        }

        $orphan = EmailThread::query()->find($previousThreadId);

        if ($orphan !== null && ! Email::query()->where('thread_id', $orphan->id)->exists()) {
            $orphan->delete();
        }
    }

    private function bindProviderThread(EmailAccount $account, EmailThread $thread, string $providerThreadId): EmailThread
    {
        if ($providerThreadId === '' || $thread->thread_id === $providerThreadId) {
            return $thread;
        }

        $canonical = EmailThread::query()
            ->where('account_id', $account->id)
            ->where('thread_id', $providerThreadId)
            ->first();

        if ($canonical !== null) {
            if ($canonical->id !== $thread->id) {
                Email::query()
                    ->where('thread_id', $thread->id)
                    ->update(['thread_id' => $canonical->id]);

                if (! Email::query()->where('thread_id', $thread->id)->exists()) {
                    $thread->delete();
                }
            }

            return $canonical;
        }

        if ($this->isLocalThreadId($thread->thread_id) || $thread->thread_id === $providerThreadId) {
            $thread->update(['thread_id' => $providerThreadId]);

            return $thread->refresh();
        }

        return $this->upsertThread($account, $providerThreadId, $thread->subject, $thread->snippet);
    }

    private function isLocalThreadId(string $threadId): bool
    {
        return str_starts_with($threadId, 'local-') || str_starts_with($threadId, 'sent-');
    }

    private function normalizeMessageId(string $messageId): string
    {
        return trim($messageId, " \t<>");
    }

    /**
     * @param  array{from_email?: string, to_emails?: list<string>, cc_emails?: list<string>, bcc_emails?: list<string>}  $parsed
     */
    private function rememberAddresses(array $parsed): void
    {
        $this->uniqueEmails->remember([
            $parsed['from_email'] ?? null,
            ...($parsed['to_emails'] ?? []),
            ...($parsed['cc_emails'] ?? []),
            ...($parsed['bcc_emails'] ?? []),
        ]);
    }

    private function refreshThreadCounts(EmailAccount $account): void
    {
        $threads = EmailThread::query()
            ->where('account_id', $account->id)
            ->get();

        foreach ($threads as $thread) {
            $count = Email::query()
                ->where('thread_id', $thread->id)
                ->where('is_draft', false)
                ->count();

            $latest = Email::query()
                ->where('thread_id', $thread->id)
                ->where('is_draft', false)
                ->orderByDesc('internal_date')
                ->orderByDesc('id')
                ->first();

            $thread->update([
                'message_count' => $count,
                'last_message_at' => $latest?->internal_date,
                'snippet' => $latest !== null ? ($latest->snippet ?? $thread->snippet) : $thread->snippet,
                'subject' => $thread->subject ?: $latest?->subject,
            ]);
        }
    }

    private function state(EmailAccount $account): EmailAccountSyncState
    {
        return EmailAccountSyncState::query()->firstOrCreate(
            ['account_id' => $account->id],
        );
    }

    private function maxMessages(): int
    {
        return max(1, (int) config('mailbox.sync_max_messages_per_run', 100));
    }

    /**
     * @return array{email: string, name: string|null}
     */
    private function parseAddress(string $value): array
    {
        if (preg_match('/^(.+?)\s*<([^>]+)>$/', $value, $matches) === 1) {
            return [
                'name' => trim($matches[1], '" '),
                'email' => strtolower(trim($matches[2])),
            ];
        }

        return [
            'name' => null,
            'email' => strtolower(trim($value)),
        ];
    }

    /**
     * @return list<string>
     */
    private function splitAddresses(string $value): array
    {
        if ($value === '') {
            return [];
        }

        $parts = preg_split('/,/', $value) ?: [];
        $emails = [];

        foreach ($parts as $part) {
            $parsed = $this->parseAddress(trim($part));

            if ($parsed['email'] !== '') {
                $emails[] = $parsed['email'];
            }
        }

        return $emails;
    }
}
