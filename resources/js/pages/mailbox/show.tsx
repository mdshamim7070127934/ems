import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    ChevronDown,
    Forward,
    MoreVertical,
    Reply,
    ReplyAll,
    ShieldAlert,
    Star,
    FolderInput,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import ComposeForm from '@/components/mailbox/compose-form';
import MailboxShell from '@/components/mailbox/mailbox-shell';
import { LabelToolbarButton } from '@/components/mailbox/label-assign-menu';
import SenderBlockAction from '@/components/mailbox/sender-block-action';
import ThreadAttachments from '@/components/mailbox/thread-attachments';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    formatExactMailboxDate,
    formatMailboxDate,
    initials,
} from '@/lib/mailbox';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import {
    index as mailboxIndex,
    action,
    bulkMove,
} from '@/routes/mailbox';
import { assignThreads } from '@/routes/mailbox/labels';
import { prefill } from '@/routes/mailbox/compose';
import type {
    MailboxAccount,
    MailboxDraft,
    MailboxEmail,
    MailboxFolder,
    MailboxLabel,
    MailboxThread,
} from '@/types';

type Props = {
    accounts: MailboxAccount[];
    account: MailboxAccount;
    thread: MailboxThread;
    emails: MailboxEmail[];
    folders: MailboxFolder[];
    labels: MailboxLabel[];
    sender_blocked: boolean;
};

type PrefillResponse = {
    success: boolean;
    data?: MailboxDraft;
};

export default function MailboxShow({
    accounts,
    account,
    thread,
    emails,
    folders,
    labels,
    sender_blocked,
}: Props) {
    const latest = emails[emails.length - 1];
    const threadSender = emails[0];
    const threadSenderEmail = thread.from_email || threadSender?.from_email;
    const [expanded, setExpanded] = useState<number[]>(latest ? [latest.id] : []);
    const [replyDraft, setReplyDraft] = useState<MailboxDraft | null>(null);
    const [replyTitle, setReplyTitle] = useState('Reply');

    function postAction(nextAction: string, emailId?: number) {
        router.post(action.url(), {
            action: nextAction,
            thread_id: thread.id,
            email_id: emailId,
        });
    }

    function assignLabel(labelId: number) {
        router.post(assignThreads.url(), {
            account_id: account.id,
            label_id: labelId,
            thread_ids: [thread.id],
        });
    }

    async function startCompose(mode: 'reply' | 'reply_all' | 'forward', email: MailboxEmail) {
        const query =
            mode === 'reply'
                ? { reply: email.id, account_id: account.id }
                : mode === 'reply_all'
                    ? { reply_all: email.id, account_id: account.id }
                    : { forward: email.id, account_id: account.id };

        const response = await fetch(prefill.url({ query }), {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            return;
        }

        const payload = (await response.json()) as PrefillResponse;

        if (!payload.data) {
            return;
        }

        setReplyTitle(mode === 'forward' ? 'Forward' : mode === 'reply_all' ? 'Reply all' : 'Reply');
        setReplyDraft({
            ...payload.data,
            bcc_emails: payload.data.bcc_emails ?? [],
        });
    }

    return (
        <>
            <Head title={thread.subject || 'Thread'} />
            <MailboxShell
                account={account}
                accounts={accounts}
                folders={folders}
                labels={labels}
                showSearch={false}
            >
                <div className="flex h-full min-h-0 flex-col">
                    <header className="border-b bg-white px-6 py-4 dark:bg-zinc-950">
                        <Button asChild variant="ghost" size="sm" className="mb-2 px-0">
                            <Link href={mailboxIndex.url({ query: { account_id: account.id } })}>
                                <ArrowLeft />
                                Back
                            </Link>
                        </Button>
                        <div className="flex items-start justify-between gap-3">
                            <div className="min-w-0">
                                <h1 className="text-xl font-medium">
                                    {thread.subject || '(no subject)'}
                                </h1>
                                {thread.labels?.length > 0 && (
                                    <div className="mt-1 flex flex-wrap gap-1">
                                        {thread.labels.map((label) => (
                                            <Badge
                                                key={label.id}
                                                className="rounded-full text-white"
                                                style={{ backgroundColor: label.color ?? '#5f6368' }}
                                            >
                                                {label.name}
                                            </Badge>
                                        ))}
                                    </div>
                                )}
                                <p className="text-muted-foreground mt-2 text-sm">
                                    {emails.length} message{emails.length === 1 ? '' : 's'} in this conversation
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <LabelToolbarButton labels={labels} onAssign={assignLabel} />
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="outline" size="sm">
                                            <FolderInput />
                                            Move to
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" className="w-56">
                                        <DropdownMenuLabel>Folders</DropdownMenuLabel>
                                        {folders.map((folder) => (
                                            <DropdownMenuItem
                                                key={folder.id}
                                                onClick={() =>
                                                    router.post(bulkMove.url(), {
                                                        folder_id: folder.id,
                                                        thread_ids: [thread.id],
                                                    })
                                                }
                                            >
                                                {folder.name}
                                            </DropdownMenuItem>
                                        ))}
                                    </DropdownMenuContent>
                                </DropdownMenu>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        postAction(thread.is_starred ? 'unstar' : 'star')
                                    }
                                >
                                    <Star className={thread.is_starred ? 'fill-amber-400 text-amber-400' : ''} />
                                </Button>
                                {latest && (
                                    <>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => postAction('unread', latest.id)}
                                        >
                                            Mark unread
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => postAction('spam', latest.id)}
                                        >
                                            <ShieldAlert />
                                            Spam
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => postAction('trash', latest.id)}
                                        >
                                            <Trash2 />
                                            Trash
                                        </Button>
                                    </>
                                )}
                            </div>
                        </div>
                        {threadSenderEmail &&
                            sender_blocked &&
                            threadSenderEmail.toLowerCase() !==
                                account.email.toLowerCase() && (
                            <div className="mt-3 flex flex-wrap items-center gap-2 rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-100">
                                <ShieldAlert className="size-4 shrink-0" />
                                <span>
                                    This sender is blocked ({threadSenderEmail}).
                                    Messages from this address are hidden in your
                                    inbox but still appear in search.
                                </span>
                                <SenderBlockAction
                                    variant="link"
                                    email={threadSenderEmail}
                                    accountEmail={account.email}
                                    accountId={account.id}
                                    blocked
                                />
                            </div>
                        )}
                    </header>

                    <div className="min-h-0 flex-1 overflow-auto px-6 py-4">
                        <div className="space-y-3">
                            {emails.map((email) => {
                                const isOpen = expanded.includes(email.id) || emails.length === 1;
                                const snippet = email.snippet || email.body_plain || '';
                                const isOwn =
                                    email.from_email.toLowerCase() ===
                                    account.email.toLowerCase();

                                return (
                                    <article key={email.id} className="rounded-lg border bg-white dark:bg-zinc-950">
                                        <div className="flex items-start gap-2 px-4 py-3">
                                            <button
                                                type="button"
                                                className="flex min-w-0 flex-1 items-start gap-3 text-left"
                                                onClick={() =>
                                                    setExpanded((current) =>
                                                        current.includes(email.id)
                                                            ? current.filter((id) => id !== email.id)
                                                            : [...current, email.id],
                                                    )
                                                }
                                            >
                                                <span className="bg-[#1a73e8] mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold text-white">
                                                    {initials(email.from_name, email.from_email)}
                                                </span>
                                                <div className="min-w-0 flex-1">
                                                    <div className="flex items-start justify-between gap-3">
                                                        <div className="min-w-0">
                                                            <p className="truncate font-medium">
                                                                {email.from_name || email.from_email}
                                                                <span className="text-muted-foreground ml-1 font-normal">
                                                                    &lt;{email.from_email}&gt;
                                                                </span>
                                                            </p>
                                                            {isOpen ? (
                                                                <p className="text-muted-foreground text-xs">
                                                                    To {email.to_emails.join(', ')}
                                                                    {email.cc_emails.length > 0 &&
                                                                        ` • Cc ${email.cc_emails.join(', ')}`}
                                                                </p>
                                                            ) : (
                                                                <p className="text-muted-foreground truncate text-sm">
                                                                    {snippet}
                                                                </p>
                                                            )}
                                                        </div>
                                                        <span
                                                            className="text-muted-foreground shrink-0 text-xs"
                                                            title={formatExactMailboxDate(email.internal_date)}
                                                        >
                                                            {formatMailboxDate(email.internal_date)}
                                                            <ChevronDown
                                                                className={cn(
                                                                    'ml-1 inline size-4 transition',
                                                                    isOpen && 'rotate-180',
                                                                )}
                                                            />
                                                        </span>
                                                    </div>
                                                </div>
                                            </button>
                                            {!isOwn && (
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="size-8 shrink-0"
                                                            aria-label="More options"
                                                            title="More"
                                                        >
                                                            <MoreVertical />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end" className="w-64">
                                                        <SenderBlockAction
                                                            email={email.from_email}
                                                            accountEmail={account.email}
                                                            accountId={account.id}
                                                            blocked={email.sender_blocked}
                                                        />
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            )}
                                        </div>
                                        {isOpen && (
                                            <div className="px-4 pb-4">
                                                <ThreadAttachments attachments={email.attachments} />
                                                <div
                                                    className="email-body prose dark:prose-invert max-w-none text-sm"
                                                    dangerouslySetInnerHTML={{
                                                        __html: email.body_html || email.body_plain || '',
                                                    }}
                                                />
                                                <div className="mt-4 flex flex-wrap gap-2">
                                                    <Button
                                                        size="sm"
                                                        onClick={() => startCompose('reply', email)}
                                                    >
                                                        <Reply />
                                                        Reply
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() => startCompose('reply_all', email)}
                                                    >
                                                        <ReplyAll />
                                                        Reply all
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() => startCompose('forward', email)}
                                                    >
                                                        <Forward />
                                                        Forward
                                                    </Button>
                                                </div>
                                            </div>
                                        )}
                                    </article>
                                );
                            })}
                        </div>

                        {replyDraft && (
                            <div className="mt-4 flex max-h-[min(32rem,70vh)] min-h-[18rem] flex-col overflow-hidden rounded-lg border bg-white p-4 dark:bg-zinc-950">
                                <div className="mb-3 flex shrink-0 items-center justify-between">
                                    <h2 className="font-medium">{replyTitle}</h2>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => setReplyDraft(null)}
                                    >
                                        Close
                                    </Button>
                                </div>
                                <div className="min-h-0 flex-1 overflow-hidden">
                                    <ComposeForm
                                        account={account}
                                        draft={replyDraft}
                                        variant="thread"
                                        onDiscard={() => setReplyDraft(null)}
                                    />
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </MailboxShell>
        </>
    );
}

MailboxShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Mail', href: mailboxIndex() },
        { title: 'Thread', href: mailboxIndex() },
    ],
};
