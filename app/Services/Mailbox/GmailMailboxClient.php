<?php

namespace App\Services\Mailbox;

use App\Models\Mailbox\EmailAccount;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GmailMailboxClient
{
    public function __construct(
        private MailboxOAuthConnectionService $oauth,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function listLabels(EmailAccount $account): array
    {
        $payload = $this->get($account, '/users/me/labels');

        /** @var list<array<string, mixed>> $labels */
        $labels = is_array($payload['labels'] ?? null) ? $payload['labels'] : [];

        return $labels;
    }

    /**
     * @return array{history: list<array<string, mixed>>, historyId: string|null, nextPageToken: string|null}|null
     */
    public function listHistory(EmailAccount $account, string $startHistoryId, ?string $pageToken = null): ?array
    {
        $query = [
            'startHistoryId' => $startHistoryId,
            'maxResults' => 500,
        ];

        if ($pageToken !== null && $pageToken !== '') {
            $query['pageToken'] = $pageToken;
        }

        $response = $this->http($account)
            ->get('https://gmail.googleapis.com/gmail/v1/users/me/history', $query);

        if ($response->status() === 404) {
            return null;
        }

        if (! $response->successful()) {
            Log::warning('Gmail history request failed.', [
                'email_account_id' => $account->id,
                'status' => $response->status(),
            ]);

            return null;
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json() ?? [];

        $history = [];

        foreach (is_array($payload['history'] ?? null) ? $payload['history'] : [] as $entry) {
            if (is_array($entry)) {
                $history[] = $entry;
            }
        }

        return [
            'history' => $history,
            'historyId' => isset($payload['historyId']) && is_string($payload['historyId']) ? $payload['historyId'] : null,
            'nextPageToken' => isset($payload['nextPageToken']) && is_string($payload['nextPageToken']) ? $payload['nextPageToken'] : null,
        ];
    }

    /**
     * @return array{threads: list<array{id: string}>, nextPageToken: string|null}
     */
    public function listThreadIds(EmailAccount $account, string $labelId, int $maxResults, ?string $pageToken = null): array
    {
        $query = [
            'labelIds' => $labelId,
            'maxResults' => $maxResults,
        ];

        if ($pageToken !== null && $pageToken !== '') {
            $query['pageToken'] = $pageToken;
        }

        $payload = $this->get($account, '/users/me/threads', $query);
        $threads = [];

        foreach (is_array($payload['threads'] ?? null) ? $payload['threads'] : [] as $thread) {
            if (is_array($thread) && isset($thread['id']) && is_string($thread['id'])) {
                $threads[] = ['id' => $thread['id']];
            }
        }

        return [
            'threads' => $threads,
            'nextPageToken' => isset($payload['nextPageToken']) && is_string($payload['nextPageToken']) ? $payload['nextPageToken'] : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getThread(EmailAccount $account, string $threadId): ?array
    {
        $payload = $this->get($account, '/users/me/threads/'.rawurlencode($threadId), [
            'format' => 'full',
        ]);

        return $payload === [] ? null : $payload;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getProfile(EmailAccount $account): ?array
    {
        $payload = $this->get($account, '/users/me/profile');

        return isset($payload['historyId']) ? $payload : null;
    }

    public function getAttachment(EmailAccount $account, string $messageId, string $attachmentId): ?string
    {
        $payload = $this->get(
            $account,
            '/users/me/messages/'.rawurlencode($messageId).'/attachments/'.rawurlencode($attachmentId),
        );

        $data = $payload['data'] ?? null;

        if (! is_string($data) || $data === '') {
            return null;
        }

        $decoded = base64_decode(strtr($data, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMessage(EmailAccount $account, string $messageId): ?array
    {
        $payload = $this->get($account, '/users/me/messages/'.rawurlencode($messageId), [
            'format' => 'full',
        ]);

        return $payload === [] ? null : $payload;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function sendRaw(EmailAccount $account, string $rawMessage, ?string $threadId = null): ?array
    {
        $payload = [
            'raw' => rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '='),
        ];

        if (is_string($threadId) && $threadId !== '') {
            $payload['threadId'] = $threadId;
        }

        $response = $this->http($account)
            ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', $payload);

        if (! $response->successful()) {
            $error = $response->json('error.message');

            Log::error('Gmail mailbox send failed.', [
                'email_account_id' => $account->id,
                'status' => $response->status(),
                'error' => is_string($error) ? $error : null,
            ]);

            return null;
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json() ?? [];

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function get(EmailAccount $account, string $path, array $query = []): array
    {
        $response = $this->http($account)
            ->get('https://gmail.googleapis.com/gmail/v1'.$path, $query);

        if (! $response->successful()) {
            Log::warning('Gmail mailbox request failed.', [
                'email_account_id' => $account->id,
                'path' => $path,
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
