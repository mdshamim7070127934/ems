<?php

namespace App\Services\Mailbox;

use App\Enums\Mailbox\FolderType;
use App\Models\Mailbox\EmailAccount;
use App\Models\Mailbox\EmailFolder;
use Illuminate\Support\Str;

class MailboxFolderService
{
    /**
     * @return array{name: string, slug: string, icon: string, type: FolderType}
     */
    public function fromGmailLabel(string $labelId, string $name): array
    {
        return match ($labelId) {
            'INBOX' => $this->system('Inbox', 'inbox'),
            'STARRED' => $this->system('Starred', 'starred'),
            'SENT' => $this->system('Sent', 'sent'),
            'DRAFT' => $this->system('Drafts', 'draft'),
            'SPAM' => $this->system('Spam', 'spam'),
            'TRASH' => $this->system('Trash', 'trash'),
            default => [
                'name' => $name,
                'slug' => 'label-'.Str::slug($name),
                'icon' => 'folder',
                'type' => FolderType::Custom,
            ],
        };
    }

    /**
     * @return array{name: string, slug: string, icon: string, type: FolderType}
     */
    public function fromOutlookFolder(?string $wellKnownName, string $displayName): array
    {
        $known = strtolower((string) $wellKnownName);

        return match ($known) {
            'inbox' => $this->system('Inbox', 'inbox'),
            'sentitems' => $this->system('Sent', 'sent'),
            'drafts' => $this->system('Drafts', 'draft'),
            'junkemail' => $this->system('Spam', 'spam'),
            'deleteditems' => $this->system('Trash', 'trash'),
            default => match (strtolower($displayName)) {
                'inbox' => $this->system('Inbox', 'inbox'),
                'sent items', 'sent' => $this->system('Sent', 'sent'),
                'drafts', 'draft' => $this->system('Drafts', 'draft'),
                'junk email', 'spam' => $this->system('Spam', 'spam'),
                'deleted items', 'trash' => $this->system('Trash', 'trash'),
                default => [
                    'name' => $displayName,
                    'slug' => 'folder-'.Str::slug($displayName),
                    'icon' => 'folder',
                    'type' => FolderType::Custom,
                ],
            },
        };
    }

    /**
     * @return array{name: string, slug: string, icon: string, type: FolderType}
     */
    public function fromImapMailbox(string $name): array
    {
        $normalized = strtolower(trim($name));

        return match ($normalized) {
            'inbox' => $this->system('Inbox', 'inbox'),
            'sent', 'sent mail', '[gmail]/sent mail' => $this->system('Sent', 'sent-mail'),
            'drafts', 'draft', '[gmail]/drafts' => $this->system('Drafts', 'draft-mail'),
            'trash', '[gmail]/trash' => $this->system('Trash', 'trash'),
            'spam', 'junk', '[gmail]/spam' => $this->system('Spam', 'spam'),
            'starred', '[gmail]/starred' => $this->system('Starred', 'starred'),
            default => [
                'name' => $this->imapDisplayName($name),
                'slug' => Str::slug($this->imapDisplayName($name)) ?: 'folder',
                'icon' => 'folder',
                'type' => FolderType::Custom,
            ],
        };
    }

    public function upsert(EmailAccount $account, string $slug, string $name, string $icon, FolderType $type, ?string $providerFolderId = null): EmailFolder
    {
        return EmailFolder::query()->updateOrCreate(
            [
                'account_id' => $account->id,
                'slug' => $slug,
            ],
            [
                'name' => $name,
                'icon' => $icon,
                'type' => $type,
                'provider_folder_id' => $providerFolderId,
            ],
        );
    }

    public function ensureSent(EmailAccount $account): EmailFolder
    {
        $existing = EmailFolder::query()
            ->where('account_id', $account->id)
            ->where('slug', 'sent')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return $this->upsert($account, 'sent', 'Sent', 'sent', FolderType::System, 'SENT');
    }

    /**
     * @return array{name: string, slug: string, icon: string, type: FolderType}
     */
    private function system(string $name, string $slug): array
    {
        $icon = match ($slug) {
            'inbox' => 'inbox',
            'starred' => 'starred',
            'sent', 'sent-mail' => 'sent',
            'draft', 'draft-mail' => 'draft',
            'trash' => 'trash',
            'spam' => 'spam',
            default => 'folder',
        };

        return [
            'name' => $name,
            'slug' => $slug,
            'icon' => $icon,
            'type' => FolderType::System,
        ];
    }

    private function imapDisplayName(string $name): string
    {
        if (preg_match('/^\[([^\]]+)\]\s*\/?\s*(.*)$/s', $name, $matches) === 1) {
            $rest = trim($matches[2]);

            return $rest !== '' ? trim($matches[1]).'-'.$rest : trim($matches[1]);
        }

        return $name;
    }
}
