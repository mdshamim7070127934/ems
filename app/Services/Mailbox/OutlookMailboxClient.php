<?php

namespace App\Services\Mailbox;

use App\Models\Mailbox\EmailAccount;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OutlookMailboxClient
{
    public function __construct(
        private MailboxOAuthConnectionService $oauth,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function listMailFolders(EmailAccount $account): array
    {
        $payload = $this->get($account, 'https://graph.microsoft.com/v1.0/me/mailFolders');

        /** @var list<array<string, mixed>> $folders */
        $folders = is_array($payload['value'] ?? null) ? $payload['value'] : [];

        return $folders;
    }

    /**
     * @return array{messages: list<array<string, mixed>>, deltaLink: string|null}
     */
    public function listMessagesDelta(EmailAccount $account, string $folderId, ?string $deltaLink = null): array
    {
        $messages = [];
        $url = $deltaLink;

        if ($url === null || $url === '') {
            $select = 'id,conversationId,subject,body,bodyPreview,from,toRecipients,ccRecipients,receivedDateTime,isRead,isDraft,hasAttachments,internetMessageId,internetMessageHeaders';
            $url = 'https://graph.microsoft.com/v1.0/me/mailFolders/'.rawurlencode($folderId).'/messages/delta?$select='.rawurlencode($select);
        }

        $finalDeltaLink = null;

        do {
            $payload = $this->get($account, $url);

            if ($payload === []) {
                return [
                    'messages' => $messages,
                    'deltaLink' => $deltaLink,
                ];
            }

            foreach (is_array($payload['value'] ?? null) ? $payload['value'] : [] as $entry) {
                if (! is_array($entry) || isset($entry['@removed'])) {
                    continue;
                }

                $messages[] = $entry;
            }

            if (isset($payload['@odata.deltaLink']) && is_string($payload['@odata.deltaLink'])) {
                $finalDeltaLink = $payload['@odata.deltaLink'];
                break;
            }

            $url = isset($payload['@odata.nextLink']) && is_string($payload['@odata.nextLink'])
                ? $payload['@odata.nextLink']
                : null;
        } while ($url !== null);

        return [
            'messages' => $messages,
            'deltaLink' => $finalDeltaLink,
        ];
    }

    /**
     * @return list<array{name: string, content: string, mimeType: string|null, contentId: string|null}>
     */
    public function getMessageAttachments(EmailAccount $account, string $messageId): array
    {
        $payload = $this->get(
            $account,
            'https://graph.microsoft.com/v1.0/me/messages/'.rawurlencode($messageId).'/attachments',
        );

        $attachments = [];

        foreach (is_array($payload['value'] ?? null) ? $payload['value'] : [] as $attachment) {
            if (! is_array($attachment)) {
                continue;
            }

            $type = $attachment['@odata.type'] ?? '';

            if (! is_string($type) || ! str_contains($type, 'fileAttachment')) {
                continue;
            }

            $contentBytes = $attachment['contentBytes'] ?? null;

            if (! is_string($contentBytes) || $contentBytes === '') {
                continue;
            }

            $content = base64_decode($contentBytes, true);

            if ($content === false) {
                continue;
            }

            $attachments[] = [
                'name' => is_string($attachment['name'] ?? null) ? $attachment['name'] : 'attachment',
                'content' => $content,
                'mimeType' => is_string($attachment['contentType'] ?? null) ? $attachment['contentType'] : null,
                'contentId' => is_string($attachment['contentId'] ?? null) ? $attachment['contentId'] : null,
            ];
        }

        return $attachments;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    public function sendMail(EmailAccount $account, array $message): bool
    {
        $response = $this->http($account)
            ->post('https://graph.microsoft.com/v1.0/me/sendMail', [
                'message' => $message,
                'saveToSentItems' => true,
            ]);

        if ($response->successful()) {
            return true;
        }

        Log::error('Microsoft Graph mailbox send failed.', [
            'email_account_id' => $account->id,
            'status' => $response->status(),
        ]);

        return false;
    }

    public function sendMailMime(EmailAccount $account, string $rawMime): bool
    {
        $response = $this->http($account)
            ->withHeaders(['Content-Type' => 'text/plain'])
            ->withBody(base64_encode($rawMime), 'text/plain')
            ->post('https://graph.microsoft.com/v1.0/me/sendMail');

        if ($response->successful()) {
            return true;
        }

        Log::error('Microsoft Graph mailbox MIME send failed.', [
            'email_account_id' => $account->id,
            'status' => $response->status(),
        ]);

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function get(EmailAccount $account, string $url): array
    {
        $response = $this->http($account)->get($url);

        if (! $response->successful()) {
            Log::warning('Microsoft Graph mailbox request failed.', [
                'email_account_id' => $account->id,
                'status' => $response->status(),
            ]);

            return [];
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json() ?? [];

        return $payload;
    }

    private function http(EmailAccount $account): PendingRequest
    {
        $account = $this->oauth->ensureValidAccessToken($account);

        return Http::connectTimeout(5)
            ->timeout(30)
            ->acceptJson()
            ->withToken((string) $account->access_token);
    }
}
