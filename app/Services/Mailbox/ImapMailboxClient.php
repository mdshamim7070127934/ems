<?php

namespace App\Services\Mailbox;

use App\Enums\Master\SmtpEncryption;
use App\Models\Mailbox\EmailAccount;
use RuntimeException;

class ImapMailboxClient
{
    /**
     * @return list<array{name: string, path: string}>
     */
    public function listMailboxes(EmailAccount $account): array
    {
        $stream = $this->connect($account, '');
        $mailboxes = imap_list($stream, $this->mailboxRoot($account, ''), '*');
        imap_close($stream);

        if (! is_array($mailboxes)) {
            return [];
        }

        $result = [];

        foreach ($mailboxes as $mailbox) {
            $path = (string) preg_replace('/^\{[^}]+\}/', '', $mailbox);
            $name = $path === '' ? 'INBOX' : $path;
            $result[] = [
                'name' => $name,
                'path' => $path === '' ? 'INBOX' : $path,
            ];
        }

        return $result;
    }

    /**
     * @return list<int>
     */
    public function fetchMessageNumbers(EmailAccount $account, string $folder, ?\DateTimeInterface $since, int $limit): array
    {
        $stream = $this->connect($account, $folder);

        if ($since !== null) {
            $search = imap_search($stream, 'SINCE "'.$since->format('d-M-Y').'"', SE_UID);
        } else {
            $search = imap_search($stream, 'ALL', SE_UID);
        }

        imap_close($stream);

        if (! is_array($search)) {
            return [];
        }

        rsort($search, SORT_NUMERIC);

        return array_slice(array_map('intval', $search), 0, $limit);
    }

    /**
     * @return array{
     *     message_id: string,
     *     in_reply_to: string|null,
     *     references: string|null,
     *     from_email: string,
     *     from_name: string|null,
     *     to_emails: list<string>,
     *     cc_emails: list<string>,
     *     subject: string,
     *     body_html: string,
     *     body_plain: string,
     *     internal_date: string|null,
     *     is_unread: bool
     * }|null
     */
    public function getMessage(EmailAccount $account, string $folder, int $uid): ?array
    {
        $stream = $this->connect($account, $folder);
        $overview = imap_fetch_overview($stream, (string) $uid, FT_UID);
        $headerRaw = imap_fetchheader($stream, $uid, FT_UID);

        if (! is_string($headerRaw) || $headerRaw === '') {
            imap_close($stream);

            return null;
        }

        $header = imap_rfc822_parse_headers($headerRaw);
        $structure = imap_fetchstructure($stream, $uid, FT_UID);
        $plain = $this->extractBody($stream, $uid, $structure, 'TEXT/PLAIN');
        $html = $this->extractBody($stream, $uid, $structure, 'TEXT/HTML');
        imap_close($stream);

        $row = is_array($overview) ? ($overview[0] ?? null) : null;

        if ($row === null) {
            return null;
        }

        $fromEmail = '';
        $fromName = null;

        if (isset($header->from[0])) {
            $fromEmail = strtolower(trim(($header->from[0]->mailbox ?? '').'@'.($header->from[0]->host ?? '')));
            $fromName = isset($header->from[0]->personal) ? (string) $header->from[0]->personal : null;
        }

        $messageId = isset($header->message_id) ? trim((string) $header->message_id, '<>') : 'imap-'.$account->id.'-'.$uid;

        return [
            'message_id' => $messageId,
            'in_reply_to' => isset($header->in_reply_to) ? trim((string) $header->in_reply_to, '<>') : null,
            'references' => isset($header->references) ? (string) $header->references : null,
            'from_email' => $fromEmail,
            'from_name' => $fromName,
            'to_emails' => $this->addresses($header->to ?? []),
            'cc_emails' => $this->addresses($header->cc ?? []),
            'subject' => isset($row->subject) ? (string) $row->subject : '',
            'body_html' => $html !== '' ? $html : nl2br(e($plain)),
            'body_plain' => $plain !== '' ? $plain : strip_tags($html),
            'internal_date' => isset($row->date) ? date('Y-m-d H:i:s', strtotime((string) $row->date) ?: time()) : null,
            'is_unread' => isset($row->seen) ? ((int) $row->seen === 0) : true,
        ];
    }

    /**
     * @param  object|array<int, object>|null  $addresses
     * @return list<string>
     */
    private function addresses(object|array|null $addresses): array
    {
        if (! is_array($addresses)) {
            return [];
        }

        $emails = [];

        foreach ($addresses as $address) {
            $mailbox = $address->mailbox ?? null;
            $host = $address->host ?? null;

            if (is_string($mailbox) && is_string($host) && $mailbox !== '' && $host !== '') {
                $emails[] = strtolower($mailbox.'@'.$host);
            }
        }

        return $emails;
    }

    private function extractBody(mixed $stream, int $uid, mixed $structure, string $mime): string
    {
        if (! is_object($structure)) {
            return '';
        }

        $partNumber = $this->findPart($structure, $mime, '');

        if ($partNumber === null) {
            return '';
        }

        $body = imap_fetchbody($stream, $uid, $partNumber === '' ? '1' : $partNumber, FT_UID);

        if (! is_string($body) || $body === '') {
            return '';
        }

        $encoding = $this->partEncoding($structure, $partNumber);

        return match ($encoding) {
            3 => (string) base64_decode($body, true),
            4 => (string) quoted_printable_decode($body),
            default => $body,
        };
    }

    private function findPart(object $structure, string $mime, string $prefix): ?string
    {
        $type = $this->mimeType($structure);

        if (strcasecmp($type, $mime) === 0) {
            return $prefix === '' ? '1' : $prefix;
        }

        if (! isset($structure->parts) || ! is_array($structure->parts)) {
            return null;
        }

        foreach ($structure->parts as $index => $part) {
            if (! is_object($part)) {
                continue;
            }

            $number = $prefix === '' ? (string) ($index + 1) : $prefix.'.'.($index + 1);
            $found = $this->findPart($part, $mime, $number);

            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    private function partEncoding(object $structure, string $partNumber): int
    {
        if ($partNumber === '1' && isset($structure->encoding)) {
            return (int) $structure->encoding;
        }

        return 0;
    }

    private function mimeType(object $structure): string
    {
        $types = ['TEXT', 'MULTIPART', 'MESSAGE', 'APPLICATION', 'AUDIO', 'IMAGE', 'VIDEO', 'OTHER'];
        $primary = $types[$structure->type ?? 0] ?? 'TEXT';
        $subtype = isset($structure->subtype) ? (string) $structure->subtype : 'PLAIN';

        return $primary.'/'.$subtype;
    }

    private function connect(EmailAccount $account, string $folder): mixed
    {
        if (! function_exists('imap_open')) {
            throw new RuntimeException('The PHP IMAP extension is not installed.');
        }

        $mailbox = $this->mailboxRoot($account, $folder);
        $username = $account->imap_username ?: $account->email;
        $password = (string) $account->imap_password;
        $stream = @imap_open($mailbox, $username, $password, 0, 1);

        if ($stream === false) {
            throw new RuntimeException('Could not connect to the IMAP server.');
        }

        return $stream;
    }

    private function mailboxRoot(EmailAccount $account, string $folder): string
    {
        $encryption = $account->imap_encryption;
        $flags = '/imap';

        if ($encryption === SmtpEncryption::Ssl) {
            $flags .= '/ssl';
        } elseif ($encryption === SmtpEncryption::Tls) {
            $flags .= '/tls';
        } else {
            $flags .= '/notls';
        }

        $host = $account->imap_host ?: 'localhost';
        $port = $account->imap_port ?: 993;
        $folderName = $folder === '' ? '' : $folder;

        return sprintf('{%s:%d%s}%s', $host, $port, $flags, $folderName);
    }
}
