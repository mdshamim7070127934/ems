import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    Eye,
    Pencil,
    Plus,
    RefreshCcw,
    Search,
    Trash2,
    X,
} from 'lucide-react';
import { useCallback, useEffect, useState, type ReactNode } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { dashboard } from '@/routes';
import {
    connect,
    edit,
    index,
    reconnect,
    show,
} from '@/routes/master/email-accounts';
import type {
    EmailAccountFilters,
    EmailAccountSortColumn,
    MasterEmailAccount,
    PaginatedEmailAccounts,
} from '@/types';
import EmailAccountDeleteDialog from './email-account-delete-dialog';

type Props = {
    emailAccounts: PaginatedEmailAccounts;
    filters: EmailAccountFilters;
};

type DialogState =
    | { type: 'delete'; emailAccount: MasterEmailAccount }
    | null;

const pageSizes = [10, 25, 50, 100];

export default function EmailAccountsIndex({ emailAccounts, filters }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [loading, setLoading] = useState(false);
    const [dialog, setDialog] = useState<DialogState>(null);

    const visitList = useCallback(
        (overrides: Partial<EmailAccountFilters> = {}) => {
            const next = { ...filters, ...overrides };

            setLoading(true);

            router.get(
                index.url({
                    query: {
                        page: next.page,
                        pageSize: next.pageSize,
                        search: next.search,
                        sortBy: next.sortBy,
                        sortOrder: next.sortOrder,
                    },
                }),
                {},
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    onFinish: () => setLoading(false),
                },
            );
        },
        [filters],
    );

    useEffect(() => {
        setSearch(filters.search);
    }, [filters.search]);

    useEffect(() => {
        const timeout = window.setTimeout(() => {
            if (search === filters.search) {
                return;
            }

            visitList({ search, page: 1 });
        }, 300);

        return () => window.clearTimeout(timeout);
    }, [filters.search, search, visitList]);

    function sortBy(column: EmailAccountSortColumn): void {
        const sortOrder =
            filters.sortBy === column && filters.sortOrder === 'asc'
                ? 'desc'
                : 'asc';

        visitList({ sortBy: column, sortOrder, page: 1 });
    }

    function sortIcon(column: EmailAccountSortColumn) {
        if (filters.sortBy !== column) {
            return <ArrowUpDown className="size-3.5 opacity-40" />;
        }

        return filters.sortOrder === 'asc' ? (
            <ArrowUp className="size-3.5" />
        ) : (
            <ArrowDown className="size-3.5" />
        );
    }

    const pageNumbers = visiblePages(
        emailAccounts.meta.current_page,
        emailAccounts.meta.last_page,
    );

    return (
        <>
            <Head title="Email accounts" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Email accounts"
                        description="Connect Gmail or Outlook with OAuth, or configure SMTP for other providers."
                    />
                    <Button asChild className="sm:mt-1">
                        <Link href={connect.url()}>
                            <Plus />
                            Connect account
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="relative w-full sm:max-w-xs">
                        <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search email accounts..."
                            className="pr-9 pl-9"
                            aria-label="Search email accounts"
                        />
                        {search !== '' && (
                            <button
                                type="button"
                                className="text-muted-foreground hover:text-foreground absolute top-1/2 right-2 -translate-y-1/2 rounded-sm p-1"
                                onClick={() => setSearch('')}
                                aria-label="Clear search"
                            >
                                <X className="size-4" />
                            </button>
                        )}
                    </div>

                    <div className="flex items-center gap-2 text-sm">
                        <span className="text-muted-foreground">Show</span>
                        <Select
                            value={String(filters.pageSize)}
                            onValueChange={(value) =>
                                visitList({
                                    pageSize: Number(value),
                                    page: 1,
                                })
                            }
                        >
                            <SelectTrigger
                                size="sm"
                                aria-label="Records per page"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {pageSizes.map((size) => (
                                    <SelectItem key={size} value={String(size)}>
                                        {size}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <span className="text-muted-foreground">entries</span>
                    </div>
                </div>

                <div className="relative overflow-hidden rounded-xl border">
                    {loading && (
                        <div className="bg-background/70 absolute inset-0 z-10 flex items-center justify-center">
                            <Spinner className="size-6" />
                        </div>
                    )}

                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[1200px] text-left text-sm">
                            <thead className="bg-muted/50 border-b">
                                <tr>
                                    <th className="px-4 py-3 font-medium">#</th>
                                    <SortableHeader
                                        label="Name"
                                        column="name"
                                        icon={sortIcon('name')}
                                        onSort={sortBy}
                                    />
                                    <SortableHeader
                                        label="Provider"
                                        column="provider"
                                        icon={sortIcon('provider')}
                                        onSort={sortBy}
                                    />
                                    <SortableHeader
                                        label="From email"
                                        column="from_email"
                                        icon={sortIcon('from_email')}
                                        onSort={sortBy}
                                    />
                                    <th className="px-4 py-3 font-medium">
                                        Email types
                                    </th>
                                    <SortableHeader
                                        label="Default"
                                        column="is_default"
                                        icon={sortIcon('is_default')}
                                        onSort={sortBy}
                                    />
                                    <th className="px-4 py-3 font-medium">
                                        Connection
                                    </th>
                                    <SortableHeader
                                        label="Status"
                                        column="status"
                                        icon={sortIcon('status')}
                                        onSort={sortBy}
                                    />
                                    <SortableHeader
                                        label="Created date"
                                        column="created_at"
                                        icon={sortIcon('created_at')}
                                        onSort={sortBy}
                                    />
                                    <th className="px-4 py-3 text-right font-medium">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {emailAccounts.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={10}
                                            className="text-muted-foreground px-4 py-12 text-center"
                                        >
                                            No email accounts found.
                                        </td>
                                    </tr>
                                ) : (
                                    emailAccounts.data.map(
                                        (account, rowIndex) => {
                                            const rowNumber =
                                                (emailAccounts.meta
                                                    .current_page -
                                                    1) *
                                                    emailAccounts.meta
                                                        .per_page +
                                                rowIndex +
                                                1;

                                            return (
                                                <tr
                                                    key={account.id}
                                                    className="border-b last:border-b-0"
                                                >
                                                    <td className="px-4 py-3">
                                                        {rowNumber}
                                                    </td>
                                                    <td className="px-4 py-3 font-medium">
                                                        {account.name}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {account.provider_label}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {account.from_email}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {account.email_type_names ||
                                                            '—'}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {account.is_default
                                                            ? 'Yes'
                                                            : 'No'}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <Badge
                                                            variant={
                                                                account.reauthorization_required
                                                                    ? 'destructive'
                                                                    : account.connection_status ===
                                                                        'connected'
                                                                      ? 'default'
                                                                      : 'secondary'
                                                            }
                                                        >
                                                            {
                                                                account.connection_status_label
                                                            }
                                                        </Badge>
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <Badge
                                                            variant={
                                                                account.status ===
                                                                'active'
                                                                    ? 'default'
                                                                    : 'secondary'
                                                            }
                                                        >
                                                            {
                                                                account.status_label
                                                            }
                                                        </Badge>
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {account.created_at}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <div className="flex justify-end gap-1">
                                                            <ActionButton
                                                                label="View"
                                                                onClick={() =>
                                                                    router.visit(
                                                                        show.url(
                                                                            account.id,
                                                                        ),
                                                                    )
                                                                }
                                                            >
                                                                <Eye />
                                                            </ActionButton>
                                                            {account.uses_oauth &&
                                                                account.reauthorization_required && (
                                                                    <ActionButton
                                                                        label="Reconnect"
                                                                        onClick={() => {
                                                                            window.location.href =
                                                                                reconnect.url(
                                                                                    account.id,
                                                                                );
                                                                        }}
                                                                    >
                                                                        <RefreshCcw />
                                                                    </ActionButton>
                                                                )}
                                                            <ActionButton
                                                                label="Edit"
                                                                onClick={() =>
                                                                    router.visit(
                                                                        edit.url(
                                                                            account.id,
                                                                        ),
                                                                    )
                                                                }
                                                            >
                                                                <Pencil />
                                                            </ActionButton>
                                                            <ActionButton
                                                                label="Delete"
                                                                onClick={() =>
                                                                    setDialog({
                                                                        type: 'delete',
                                                                        emailAccount:
                                                                            account,
                                                                    })
                                                                }
                                                            >
                                                                <Trash2 />
                                                            </ActionButton>
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        },
                                    )
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-muted-foreground text-sm">
                        {emailAccounts.meta.total === 0
                            ? 'Showing 0 entries'
                            : `Showing ${emailAccounts.meta.from} to ${emailAccounts.meta.to} of ${emailAccounts.meta.total} records`}
                    </p>

                    {emailAccounts.meta.last_page > 1 && (
                        <div className="flex flex-wrap items-center gap-1">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                disabled={
                                    emailAccounts.meta.current_page === 1
                                }
                                onClick={() =>
                                    visitList({
                                        page:
                                            emailAccounts.meta.current_page - 1,
                                    })
                                }
                            >
                                Previous
                            </Button>
                            {pageNumbers.map((page, index) =>
                                page === 'ellipsis' ? (
                                    <span
                                        key={`ellipsis-${index}`}
                                        className="text-muted-foreground px-2"
                                    >
                                        …
                                    </span>
                                ) : (
                                    <Button
                                        key={page}
                                        type="button"
                                        variant={
                                            page ===
                                            emailAccounts.meta.current_page
                                                ? 'default'
                                                : 'outline'
                                        }
                                        size="sm"
                                        onClick={() => visitList({ page })}
                                    >
                                        {page}
                                    </Button>
                                ),
                            )}
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                disabled={
                                    emailAccounts.meta.current_page ===
                                    emailAccounts.meta.last_page
                                }
                                onClick={() =>
                                    visitList({
                                        page:
                                            emailAccounts.meta.current_page + 1,
                                    })
                                }
                            >
                                Next
                            </Button>
                        </div>
                    )}
                </div>
            </div>

            <EmailAccountDeleteDialog
                open={dialog?.type === 'delete'}
                onOpenChange={(open) => {
                    if (!open) {
                        setDialog(null);
                    }
                }}
                emailAccount={
                    dialog?.type === 'delete' ? dialog.emailAccount : null
                }
            />
        </>
    );
}

function SortableHeader({
    label,
    column,
    icon,
    onSort,
}: {
    label: string;
    column: EmailAccountSortColumn;
    icon: ReactNode;
    onSort: (column: EmailAccountSortColumn) => void;
}) {
    return (
        <th className="px-4 py-3 font-medium">
            <button
                type="button"
                className="inline-flex items-center gap-1"
                onClick={() => onSort(column)}
            >
                {label}
                {icon}
            </button>
        </th>
    );
}

function ActionButton({
    label,
    children,
    onClick,
}: {
    label: string;
    children: ReactNode;
    onClick: () => void;
}) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label={label}
                    onClick={onClick}
                >
                    {children}
                </Button>
            </TooltipTrigger>
            <TooltipContent>{label}</TooltipContent>
        </Tooltip>
    );
}

function visiblePages(
    current: number,
    last: number,
): Array<number | 'ellipsis'> {
    if (last <= 1) {
        return [];
    }

    if (last <= 7) {
        return Array.from({ length: last }, (_, index) => index + 1);
    }

    const pages = new Set<number>([1, last, current, current - 1, current + 1]);
    const sorted = [...pages]
        .filter((page) => page >= 1 && page <= last)
        .sort((left, right) => left - right);
    const result: Array<number | 'ellipsis'> = [];

    sorted.forEach((page, index) => {
        const previous = sorted[index - 1];

        if (previous !== undefined && page - previous > 1) {
            result.push('ellipsis');
        }

        result.push(page);
    });

    return result;
}

EmailAccountsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Email accounts',
            href: index(),
        },
    ],
};
