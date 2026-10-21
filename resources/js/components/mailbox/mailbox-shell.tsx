import { Link, router } from '@inertiajs/react';
import {
    FileText,
    Inbox,
    Pencil,
    Plus,
    RefreshCw,
    Search,
    Star,
    X,
} from 'lucide-react';
import { createContext, useContext, useMemo, useState, type ReactNode } from 'react';
import ComposePopup from '@/components/mailbox/compose-popup';
import MailboxLabelDeleteDialog from '@/components/mailbox/mailbox-label-delete-dialog';
import MailboxLabelFormDialog from '@/components/mailbox/mailbox-label-form-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { folderDisplayName, folderIcon } from '@/lib/mailbox';
import { cn } from '@/lib/utils';
import { connect, index as mailboxIndex, drafts as draftsIndex, sync } from '@/routes/mailbox';
import type {
    MailboxAccount,
    MailboxDraft,
    MailboxFilters,
    MailboxFolder,
    MailboxLabel,
} from '@/types';

type ComposeIntent = {
    title?: string;
    draft?: MailboxDraft;
};

type ComposeContextValue = {
    openCompose: (intent?: ComposeIntent) => void;
    openCreateLabel: () => void;
};

const ComposeContext = createContext<ComposeContextValue | null>(null);

export function useMailboxCompose(): ComposeContextValue {
    const context = useContext(ComposeContext);

    if (!context) {
        throw new Error('useMailboxCompose must be used within MailboxShell');
    }

    return context;
}

const emptyDraft = (): MailboxDraft => ({
    to_emails: [],
    cc_emails: [],
    bcc_emails: [],
    subject: '',
    body_html: '',
    thread_id: null,
    in_reply_to: null,
});

type Props = {
    account: MailboxAccount;
    accounts?: MailboxAccount[];
    folders?: MailboxFolder[];
    labels?: MailboxLabel[];
    filters?: Partial<MailboxFilters>;
    active?: 'mail' | 'drafts' | 'compose' | 'connect';
    showSearch?: boolean;
    children: ReactNode;
};

export default function MailboxShell({
    account,
    accounts = [],
    folders = [],
    labels = [],
    filters,
    active = 'mail',
    showSearch = true,
    children,
}: Props) {
    const [search, setSearch] = useState(filters?.search ?? '');
    const [compose, setCompose] = useState<ComposeIntent | null>(null);
    const [labelOpen, setLabelOpen] = useState(false);
    const [editingLabel, setEditingLabel] = useState<MailboxLabel | null>(null);
    const [deletingLabel, setDeletingLabel] = useState<MailboxLabel | null>(null);

    const composeValue = useMemo<ComposeContextValue>(
        () => ({
            openCompose: (intent) => setCompose(intent ?? { title: 'New message', draft: emptyDraft() }),
            openCreateLabel: () => {
                setEditingLabel(null);
                setLabelOpen(true);
            },
        }),
        [],
    );

    function visitFolder(folder: string, extras: Partial<MailboxFilters> = {}) {
        router.get(
            mailboxIndex.url({
                query: {
                    account_id: account.id,
                    folder,
                    label: extras.label ?? undefined,
                    search: extras.search ?? '',
                    page: 1,
                    pageSize: filters?.pageSize ?? 25,
                },
            }),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function visitDrafts(query: string): void {
        router.get(
            draftsIndex.url({
                query: {
                    account_id: account.id,
                    search: query,
                    page: 1,
                    pageSize: filters?.pageSize ?? 25,
                },
            }),
            {},
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function switchAccount(accountId: number) {
        router.get(
            mailboxIndex.url({
                query: {
                    account_id: accountId,
                    folder: filters?.folder ?? 'inbox',
                    page: 1,
                    pageSize: filters?.pageSize ?? 25,
                },
            }),
        );
    }

    const currentFolder = filters?.folder ?? 'inbox';
    const currentLabel = filters?.label ?? null;

    return (
        <ComposeContext.Provider value={composeValue}>
            <div className="flex h-full min-h-0 flex-1 overflow-hidden">
                <aside className="bg-[#fafafa] flex w-[260px] shrink-0 flex-col border-r dark:bg-zinc-950">
                    <div className="flex items-center justify-end border-b px-4 py-3">
                        <Button
                            className="rounded-none bg-[#1a73e8] hover:bg-[#1557b0]"
                            onClick={() => composeValue.openCompose()}
                        >
                            <Pencil />
                            Compose
                        </Button>
                    </div>

                    <div className="flex-1 overflow-auto">
                        <section className="border-b py-3">
                            <p className="text-muted-foreground px-4 pb-2 text-[11px] font-semibold tracking-wide uppercase">
                                Favorites
                            </p>
                            <nav>
                                <SidebarLink
                                    active={active === 'mail' && currentFolder === 'inbox' && !currentLabel}
                                    icon={Inbox}
                                    label="Inbox"
                                    onClick={() => visitFolder('inbox')}
                                />
                                <SidebarLink
                                    active={active === 'mail' && currentFolder === 'starred'}
                                    icon={Star}
                                    label="Starred"
                                    onClick={() => visitFolder('starred')}
                                />
                                <SidebarLink
                                    active={active === 'drafts'}
                                    icon={FileText}
                                    label="Drafts"
                                    href={draftsIndex.url({ query: { account_id: account.id } })}
                                />
                            </nav>
                        </section>

                        <section className="border-b py-3">
                            <p className="text-muted-foreground truncate px-4 pb-2 text-[11px] font-semibold tracking-wide uppercase">
                                {account.email}
                            </p>
                            <nav>
                                {folders.map((folder) => {
                                    const Icon = folderIcon(folder.slug);

                                    return (
                                        <SidebarLink
                                            key={folder.id}
                                            active={
                                                active === 'mail' &&
                                                currentFolder === folder.slug &&
                                                !currentLabel
                                            }
                                            icon={Icon}
                                            label={folderDisplayName(folder.slug, folder.name)}
                                            count={folder.unread_count}
                                            onClick={() => visitFolder(folder.slug)}
                                        />
                                    );
                                })}
                            </nav>
                        </section>

                        <section className="border-b py-3">
                            <div className="text-muted-foreground flex items-center justify-between px-4 pb-2 text-[11px] font-semibold tracking-wide uppercase">
                                <span>Labels</span>
                                <button
                                    type="button"
                                    className="hover:text-foreground"
                                    onClick={() => {
                                        setEditingLabel(null);
                                        setLabelOpen(true);
                                    }}
                                    title="Create label"
                                >
                                    <Plus className="size-3.5" />
                                </button>
                            </div>
                            <nav>
                                {labels.map((label) => (
                                    <div key={label.id} className="group flex items-center">
                                        <button
                                            type="button"
                                            onClick={() =>
                                                visitFolder(currentFolder, { label: label.id })
                                            }
                                            className={cn(
                                                'flex min-w-0 flex-1 items-center gap-3 px-4 py-2 text-left text-sm',
                                                currentLabel === label.id
                                                    ? 'rounded-r-full border-l-[3px] border-[#1967d2] bg-[#e8f0fe] font-medium text-[#1967d2]'
                                                    : 'hover:bg-[#f1f3f4] border-l-[3px] border-transparent',
                                            )}
                                        >
                                            <span
                                                className="size-3.5 shrink-0 rounded-sm"
                                                style={{ backgroundColor: label.color ?? '#5f6368' }}
                                            />
                                            <span className="truncate">{label.name}</span>
                                        </button>
                                        <button
                                            type="button"
                                            className="text-muted-foreground hover:text-foreground px-1.5 opacity-0 group-hover:opacity-100"
                                            onClick={() => {
                                                setEditingLabel(label);
                                                setLabelOpen(true);
                                            }}
                                            title="Rename label"
                                        >
                                            <Pencil className="size-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            className="text-muted-foreground hover:text-destructive px-2 opacity-0 group-hover:opacity-100"
                                            onClick={() => setDeletingLabel(label)}
                                            title="Delete label"
                                        >
                                            <X className="size-3.5" />
                                        </button>
                                    </div>
                                ))}
                            </nav>
                        </section>

                        <section className="py-3">
                            <p className="text-muted-foreground px-4 pb-2 text-[11px] font-semibold tracking-wide uppercase">
                                More
                            </p>
                            <SidebarLink
                                active={active === 'connect'}
                                label="Connect mailbox"
                                href={connect.url()}
                            />
                        </section>
                    </div>
                </aside>

                <div className="flex min-w-0 flex-1 flex-col">
                    {showSearch && (
                        <header className="flex items-center gap-3 border-b bg-white px-4 py-2 dark:bg-zinc-950">
                            <form
                                className="flex min-w-0 flex-1 items-center gap-2"
                                onSubmit={(event) => {
                                    event.preventDefault();

                                    if (active === 'drafts') {
                                        visitDrafts(search);

                                        return;
                                    }

                                    visitFolder(currentFolder, { search });
                                }}
                            >
                                <Search className="text-muted-foreground size-4 shrink-0" />
                                <Input
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    placeholder="Search mail"
                                    className="max-w-xl rounded-none"
                                />
                                <Button type="submit" variant="outline" className="rounded-none">
                                    Search
                                </Button>
                            </form>
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="outline" className="max-w-64 truncate rounded-none">
                                        {account.email}
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" className="w-64">
                                    <DropdownMenuLabel>Accounts</DropdownMenuLabel>
                                    {accounts.map((item) => (
                                        <DropdownMenuItem
                                            key={item.id}
                                            onClick={() => switchAccount(item.id)}
                                        >
                                            {item.email}
                                        </DropdownMenuItem>
                                    ))}
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem asChild>
                                        <Link href={connect.url()}>Manage accounts</Link>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                            <Button
                                variant="outline"
                                className="rounded-none"
                                onClick={() =>
                                    router.post(
                                        sync.url(),
                                        { account_id: account.id },
                                        { preserveScroll: true, preserveState: false },
                                    )
                                }
                            >
                                <RefreshCw />
                                Sync
                            </Button>
                        </header>
                    )}
                    <div className="min-h-0 flex-1 overflow-auto">{children}</div>
                </div>
            </div>

            {compose && (
                <ComposePopup
                    key={compose.draft?.id ?? 'new'}
                    account={account}
                    draft={compose.draft ?? emptyDraft()}
                    title={compose.title ?? 'New message'}
                    onClose={() => setCompose(null)}
                />
            )}

            <MailboxLabelFormDialog
                open={labelOpen}
                onOpenChange={(open) => {
                    setLabelOpen(open);

                    if (!open) {
                        setEditingLabel(null);
                    }
                }}
                accountId={account.id}
                label={editingLabel}
            />

            <MailboxLabelDeleteDialog
                open={deletingLabel !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeletingLabel(null);
                    }
                }}
                label={deletingLabel}
            />
        </ComposeContext.Provider>
    );
}

function SidebarLink({
    active,
    icon: Icon,
    label,
    count,
    href,
    onClick,
}: {
    active: boolean;
    icon?: typeof Inbox;
    label: string;
    count?: number;
    href?: string;
    onClick?: () => void;
}) {
    const className = cn(
        'flex w-full items-center gap-3 px-4 py-2 text-left text-sm',
        active
            ? 'rounded-r-full border-l-[3px] border-[#1967d2] bg-[#e8f0fe] font-medium text-[#1967d2]'
            : 'hover:bg-[#f1f3f4] border-l-[3px] border-transparent',
    );

    const content = (
        <>
            {Icon && <Icon className="size-[18px] shrink-0 opacity-85" />}
            <span className="min-w-0 flex-1 truncate">{label}</span>
            {count !== undefined && count > 0 && (
                <Badge variant="secondary">{count}</Badge>
            )}
        </>
    );

    if (href) {
        return (
            <Link href={href} className={className}>
                {content}
            </Link>
        );
    }

    return (
        <button type="button" onClick={onClick} className={className}>
            {content}
        </button>
    );
}
