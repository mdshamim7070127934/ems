import { Head, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import MailboxDraftDeleteDialog from '@/components/mailbox/mailbox-draft-delete-dialog';
import MailboxShell, { useMailboxCompose } from '@/components/mailbox/mailbox-shell';
import { Button } from '@/components/ui/button';
import { formatMailboxDate } from '@/lib/mailbox';
import { dashboard } from '@/routes';
import { drafts as draftsIndex, index as mailboxIndex } from '@/routes/mailbox';
import { show as draftsShow } from '@/routes/mailbox/drafts';
import type {
    MailboxAccount,
    MailboxDraft,
    MailboxDraftFilters,
    MailboxFolder,
    MailboxLabel,
    PaginatedMailboxDrafts,
} from '@/types';

type Props = {
    accounts: MailboxAccount[];
    account: MailboxAccount;
    folders: MailboxFolder[];
    labels: MailboxLabel[];
    drafts: PaginatedMailboxDrafts;
    filters: MailboxDraftFilters;
};

type DraftsListProps = {
    account: MailboxAccount;
    drafts: PaginatedMailboxDrafts;
    filters: MailboxDraftFilters;
};

const pageSizes = [10, 25, 50, 100];

function DraftsList({ account, drafts, filters }: DraftsListProps) {
    const { openCompose } = useMailboxCompose();
    const [deleting, setDeleting] = useState<MailboxDraft | null>(null);

    function visitList(overrides: Partial<MailboxDraftFilters> = {}): void {
        router.get(
            draftsIndex.url({
                query: {
                    account_id: account.id,
                    search: overrides.search ?? filters.search,
                    page: overrides.page ?? filters.page,
                    pageSize: overrides.pageSize ?? filters.pageSize,
                },
            }),
            {},
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    async function editDraft(draft: MailboxDraft): Promise<void> {
        const draftId = draft.id;

        if (draftId === undefined) {
            openCompose({
                title: draft.subject || 'Draft',
                draft,
            });

            return;
        }

        try {
            const response = await fetch(draftsShow.url(draftId), {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const payload = (await response.json().catch(() => null)) as
                | { data?: MailboxDraft }
                | null;

            if (response.ok && payload?.data) {
                openCompose({
                    title: payload.data.subject || 'Draft',
                    draft: payload.data,
                });

                return;
            }
        } catch {
            // Fall back to the list payload when the latest draft cannot be fetched.
        }

        openCompose({
            title: draft.subject || 'Draft',
            draft,
        });
    }

    return (
        <div className="flex h-full min-h-0 flex-1 flex-col">
            <div className="flex items-center justify-between border-b px-4 py-3">
                <h1 className="text-lg font-medium">Drafts</h1>
                <select
                    className="border-input bg-background h-8 rounded-none border px-2 text-sm"
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
                {drafts.data.length === 0 && (
                    <p className="text-muted-foreground p-10 text-center text-sm">
                        {filters.search !== ''
                            ? 'No drafts match your search.'
                            : 'No drafts.'}
                    </p>
                )}
                {drafts.data.map((draft) => {
                    const draftId = draft.id;

                    return (
                        <div
                            key={draftId}
                            className="email-list-row flex cursor-pointer items-start gap-2 border-b bg-white px-3 py-2 hover:bg-[#f8f9fa] dark:bg-transparent dark:hover:bg-accent/40"
                        >
                            <button
                                type="button"
                                className="min-w-0 flex-1 cursor-pointer text-left"
                                onClick={() => editDraft(draft)}
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <p className="truncate font-normal text-[#202124] dark:text-foreground">
                                            {draft.subject || '(no subject)'}
                                        </p>
                                        <p className="truncate text-sm text-[#5f6368]">
                                            {(draft.to_emails ?? []).join(', ') ||
                                                'No recipients'}
                                        </p>
                                    </div>
                                    <span className="text-muted-foreground shrink-0 text-xs">
                                        {formatMailboxDate(draft.updated_at ?? null)}
                                    </span>
                                </div>
                            </button>
                            {draftId !== undefined && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="size-7"
                                    title="Delete"
                                    aria-label="Delete"
                                    onClick={() => setDeleting(draft)}
                                >
                                    <Trash2 />
                                </Button>
                            )}
                        </div>
                    );
                })}
            </div>
            {drafts.meta.last_page > 1 && (
                <div className="bg-muted/40 flex items-center justify-between border-t px-3 py-2 text-sm">
                    <span className="text-muted-foreground">
                        Showing {drafts.meta.from}–{drafts.meta.to} of {drafts.meta.total}
                    </span>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            className="rounded-none"
                            disabled={drafts.meta.current_page <= 1}
                            onClick={() =>
                                visitList({
                                    page: drafts.meta.current_page - 1,
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
                                drafts.meta.current_page >= drafts.meta.last_page
                            }
                            onClick={() =>
                                visitList({
                                    page: drafts.meta.current_page + 1,
                                })
                            }
                        >
                            Next
                        </Button>
                    </div>
                </div>
            )}
            <MailboxDraftDeleteDialog
                open={deleting !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeleting(null);
                    }
                }}
                draftId={deleting?.id}
                subject={deleting?.subject}
                preserveState={false}
            />
        </div>
    );
}

export default function MailboxDrafts({
    accounts,
    account,
    folders,
    labels,
    drafts,
    filters,
}: Props) {
    return (
        <>
            <Head title="Drafts" />
            <MailboxShell
                account={account}
                accounts={accounts}
                folders={folders}
                labels={labels}
                active="drafts"
                filters={{
                    search: filters.search,
                    page: filters.page,
                    pageSize: filters.pageSize,
                }}
            >
                <DraftsList account={account} drafts={drafts} filters={filters} />
            </MailboxShell>
        </>
    );
}

MailboxDrafts.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Mail', href: mailboxIndex() },
        { title: 'Drafts', href: draftsIndex() },
    ],
};
