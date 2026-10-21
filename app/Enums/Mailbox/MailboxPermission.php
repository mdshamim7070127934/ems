<?php

namespace App\Enums\Mailbox;

enum MailboxPermission: string
{
    case Inbox = 'email.inbox';
    case Starred = 'email.starred';
    case Sent = 'email.sent';
    case Drafts = 'email.drafts';
    case Spam = 'email.spam';
    case Trash = 'email.trash';
    case Connect = 'email.connect';
    case Send = 'email.send';
    case Sync = 'email.sync';
    case Assign = 'email.assign';

    public function label(): string
    {
        return match ($this) {
            self::Inbox => 'Inbox',
            self::Starred => 'Starred',
            self::Sent => 'Sent',
            self::Drafts => 'Drafts',
            self::Spam => 'Spam',
            self::Trash => 'Trash',
            self::Connect => 'Connect mailbox',
            self::Send => 'Send email',
            self::Sync => 'Sync mailbox',
            self::Assign => 'Assign labels',
        };
    }

    public static function forFolderSlug(string $slug): ?self
    {
        return match ($slug) {
            'inbox' => self::Inbox,
            'starred' => self::Starred,
            'sent', 'sent-mail' => self::Sent,
            'draft', 'draft-mail' => self::Drafts,
            'spam' => self::Spam,
            'trash' => self::Trash,
            default => null,
        };
    }
}
