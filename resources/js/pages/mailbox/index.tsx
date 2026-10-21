import { Head, Link, router } from '@inertiajs/react';
import {
    ChevronDown,
    MoreVertical,
    Paperclip,
    Star,
    FolderInput,
} from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import MailboxShell from '@/components/mailbox/mailbox-shell';
import { LabelToolbarButton } from '@/components/mailbox/label-assign-menu';
import SenderBlockAction from '@/components/mailbox/sender-block-action';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { formatMailboxDate } from '@/lib/mailbox';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import {
    index as mailboxIndex,
    action,
    bulkMove,
} from '@/routes/mailbox';
import { assignThreads } from '@/routes/mailbox/labels';
import { show as showThread } from '@/routes/mailbox/threads';
import type {
    MailboxAccount,
    MailboxFilters,
    MailboxFolder,
    MailboxLabel,
    PaginatedMailboxThreads,
} from '@/types';

type Props = {
    accounts: MailboxAccount[];
    account: MailboxAccount;
    folders: MailboxFolder[];
    labels: MailboxLabel[];
    threads: PaginatedMailboxThreads;
    filters: MailboxFilters;
};

const pageSizes = [10, 25, 50, 100];

export default function MailboxIndex({
    accounts,
    account,
    folders,
    labels,
    threads,
    filters,
}: Props) {
    const [selectedIds, setSelectedIds] = useState<number[]>([]);

    const visitList = useCallback(
        (overrides: Partial<MailboxFilters> = {}) => {
            const next = { ...filters, ...overrides };

            router.get(
                mailboxIndex.url({
                    query: {
                        account_id: next.account_id ?? account.id,
                        folder: next.folder,
                        label: next.label ?? undefined,
                        search: next.search,
                        page: next.page,
                        pageSize: next.pageSize,
                    },
                }),
                {},
                { preserveState: true, preserveScroll: true, replace: true },
            );
        },
        [account.id, filters],
    );

    useEffect(() => {
        setSelectedIds([]);
    }, [account.id, filters.folder, filters.label, filters.page]);

    function toggleThread(id: number, checked: boolean) {
        setSelectedIds((current) =>
            checked ? [...current, id] : current.filter((value) => value !== id),
        );
    }

    function selectBy(kind: 'all' | 'none' | 'read' | 'unread' | 'starred' | 'unstarred') {
        if (kind === 'none') {
            setSelectedIds([]);

            return;
        }

        const matches = threads.data.filter((thread) => {
            if (kind === 'all') {
                return true;
            }

            if (kind === 'read') {
                return !thread.is_unread;
            }

            if (kind === 'unread') {
                return thread.is_unread;
            }

            if (kind === 'starred') {
                return thread.is_starred;
            }

            return !thread.is_starred;
        });

        setSelectedIds(matches.map((thread) => thread.id));
    }

    function moveSelected(folderId: number) {
        if (selectedIds.length === 0 || folderId < 1) {
            return;
        }

        router.post(bulkMove.url(), {
            folder_id: folderId,
            thread_ids: selectedIds,
        });
    }

    function labelSelected(labelId: number) {
        if (selectedIds.length === 0 || labelId < 1) {
            return;
        }

        router.post(assignThreads.url(), {
            account_id: account.id,
            label_id: labelId,
            thread_ids: selectedIds,
        });
    }

    const allSelected =
        threads.data.length > 0 && selectedIds.length === threads.data.length;

    return (
        <>
            <Head title="Mail" />
            <MailboxShell
                account={account}
                accounts={accounts}
                folders={folders}
                labels={labels}
                filters={filters}
                active="mail"
            >
                <div className="flex h-full min-h-0 flex-col">
                    <div className="bg-muted/40 flex flex-wrap items-center gap-1 border-b px-2 py-1">
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="outline" size="sm" className="rounded-none">
                                    <Checkbox
                                        checked={allSelected}
                                        onCheckedChange={(checked) =>
                                            selectBy(checked === true ? 'all' : 'none')
                                        }
                                        aria-label="Select"
                                    />
                                    <ChevronDown />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent>
                                <DropdownMenuItem onClick={() => selectBy('all')}>All</DropdownMenuItem>
                                <DropdownMenuItem onClick={() => selectBy('none')}>None</DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem onClick={() => selectBy('read')}>Read</DropdownMenuItem>
                                <DropdownMenuItem onClick={() => selectBy('unread')}>Unread</DropdownMenuItem>
                                <DropdownMenuItem onClick={() => selectBy('starred')}>Starred</DropdownMenuItem>
                                <DropdownMenuItem onClick={() => selectBy('unstarred')}>Unstarred</DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <LabelToolbarButton
                            labels={labels}
                            canAssign={selectedIds.length > 0}
                            onAssign={labelSelected}
                            className="rounded-none"
                        />

                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="rounded-none"
                                    disabled={selectedIds.length === 0}
                                >
                                    <FolderInput />
                                    Move to
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent className="w-56">
                                <DropdownMenuLabel>Folders</DropdownMenuLabel>
                                {folders.map((folder) => (
                                    <DropdownMenuItem
                                        key={folder.id}
                                        onClick={() => moveSelected(folder.id)}
                                    >
                                        {folder.name}
                                    </DropdownMenuItem>
                                ))}
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <select
                            className="border-input bg-background ml-auto h-8 rounded-none border px-2 text-sm"
                            value={filters.pageSize}
                            onChange={(event) =>
                                visitList({
                                    pageSize: Number(event.target.value),
                                    page: 1,
                                })
                            }
                        >
                            {pageSizes.map((size) => (
                                <option key={size} value={size}>
                                    {size}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="min-h-0 flex-1 overflow-auto">
                        {threads.data.length === 0 && (
                            <p className="text-muted-foreground p-10 text-center text-sm">
                                No messages in this folder.
                            </p>
                        )}
                        {threads.data.map((thread) => (
                            <div
                                key={thread.id}
                                className={cn(
                                    'email-list-row flex items-start gap-2 border-b px-3 py-2',
                                    thread.is_unread
                                        ? 'bg-[#e8f0fe] hover:bg-[#d2e3fc] dark:bg-blue-950/40'
                                        : 'bg-white hover:bg-[#f8f9fa] dark:bg-transparent dark:hover:bg-accent/40',
                                )}
                            >
                                <Checkbox
                                    className="mt-1"
                                    checked={selectedIds.includes(thread.id)}
                                    onCheckedChange={(checked) =>
                                        toggleThread(thread.id, checked === true)
                                    }
                                    aria-label={`Select ${thread.subject || 'thread'}`}
                                />
                                <button
                                    type="button"
                                    className="mt-0.5"
                                    onClick={() =>
                                        router.post(action.url(), {
                                            action: thread.is_starred ? 'unstar' : 'star',
                                            thread_id: thread.id,
                                        })
                                    }
                                    aria-label={thread.is_starred ? 'Unstar' : 'Star'}
                                >
                                    <Star
                                        className={cn(
                                            'size-4',
                                            thread.is_starred
                                                ? 'fill-amber-400 text-amber-400'
                                                : 'text-muted-foreground',
                                        )}
                                    />
                                </button>
                                <Link
                                    href={showThread.url(thread.id)}
                                    className="min-w-0 flex-1"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <p
                                                className={cn(
                                                    'truncate',
                                                    thread.is_unread
                                                        ? 'font-bold text-[#202124]'
                                                        : 'font-normal text-[#5f6368]',
                                                )}
                                            >
                                                {thread.from_name || thread.from_email || '(unknown)'}
                                            </p>
                                            <p
                                                className={cn(
                                                    'truncate text-sm',
                                                    thread.is_unread
                                                        ? 'font-semibold text-[#202124]'
                                                        : 'text-[#5f6368]',
                                                )}
                                            >
                                                {thread.subject || '(no subject)'}
                                            </p>
                                {thread.labels?.length > 0 && (
                                    <div className="mt-1 flex flex-wrap gap-1">
                                        {thread.labels.map((label) => (
                                                        <Badge
                                                            key={label.id}
                                                            className="rounded-full text-white"
                                                            style={{
                                                                backgroundColor: label.color ?? '#5f6368',
                                                            }}
                                                        >
                                                            {label.name}
                                                        </Badge>
                                                    ))}
                                                </div>
                                            )}
                                            <p className="truncate text-xs text-[#80868b]">
                                                {thread.snippet}
                                            </p>
                                        </div>
                                        <div className="text-muted-foreground flex shrink-0 items-center gap-1 text-xs">
                                            {thread.has_attachments && (
                                                <Paperclip className="size-4" />
                                            )}
                                            <span>{formatMailboxDate(thread.last_message_at)}</span>
                                        </div>
                                    </div>
                                </Link>
                                {thread.from_email &&
                                    thread.from_email.toLowerCase() !==
                                        account.email.toLowerCase() && (
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-7"
                                                onClick={(event) => event.stopPropagation()}
                                            >
                                                <MoreVertical />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <SenderBlockAction
                                                email={thread.from_email}
                                                accountEmail={account.email}
                                                accountId={account.id}
                                                blocked={thread.sender_blocked}
                                            />
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                )}
                            </div>
                        ))}
                    </div>

                    {threads.meta.last_page > 1 && (
                        <div className="bg-muted/40 flex items-center justify-between border-t px-3 py-2 text-sm">
                            <span className="text-muted-foreground">
                                Showing {threads.meta.from}–{threads.meta.to} of {threads.meta.total}
                            </span>
                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="rounded-none"
                                    disabled={threads.meta.current_page <= 1}
                                    onClick={() =>
                                        visitList({
                                            page: threads.meta.current_page - 1,
                                        })
                                    }
                                >
                                    Previous
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="rounded-none"
                                    disabled={
                                        threads.meta.current_page >= threads.meta.last_page
                                    }
                                    onClick={() =>
                                        visitList({
                                            page: threads.meta.current_page + 1,
                                        })
                                    }
                                >
                                    Next
                                </Button>
                            </div>
                        </div>
                    )}
                </div>
            </MailboxShell>
        </>
    );
}

MailboxIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Mail', href: mailboxIndex() },
    ],
};
