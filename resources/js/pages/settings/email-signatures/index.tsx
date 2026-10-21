import { Head, router } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    Pencil,
    Plus,
    Search,
    Star,
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
import { index } from '@/routes/email-signatures';
import type {
    EmailSignature,
    EmailSignatureFilters,
    EmailSignatureSortColumn,
    PaginatedEmailSignatures,
} from '@/types';
import EmailSignatureDefaultDialog from './email-signature-default-dialog';
import EmailSignatureDeleteDialog from './email-signature-delete-dialog';
import EmailSignatureFormDialog from './email-signature-form-dialog';

type Props = {
    signatures: PaginatedEmailSignatures;
    filters: EmailSignatureFilters;
};

type DialogState =
    | { type: 'create' }
    | { type: 'edit'; signature: EmailSignature }
    | { type: 'delete'; signature: EmailSignature }
    | { type: 'default'; signature: EmailSignature }
    | null;

const pageSizes = [10, 25, 50, 100];

export default function EmailSignaturesIndex({ signatures, filters }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [loading, setLoading] = useState(false);
    const [dialog, setDialog] = useState<DialogState>(null);

    const visitList = useCallback(
        (overrides: Partial<EmailSignatureFilters> = {}) => {
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

    function sortBy(column: EmailSignatureSortColumn): void {
        const sortOrder =
            filters.sortBy === column && filters.sortOrder === 'asc'
                ? 'desc'
                : 'asc';

        visitList({ sortBy: column, sortOrder, page: 1 });
    }

    function sortIcon(column: EmailSignatureSortColumn) {
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
        signatures.meta.current_page,
        signatures.meta.last_page,
    );

    return (
        <>
            <Head title="Email signatures" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Email signatures"
                    description="Create signatures and choose which one is inserted when you compose a new message."
                />

                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="relative w-full sm:max-w-xs">
                        <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search signatures..."
                            className="pr-9 pl-9"
                            aria-label="Search signatures"
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

                    <div className="flex items-center gap-2">
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
                                        <SelectItem
                                            key={size}
                                            value={String(size)}
                                        >
                                            {size}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <span className="text-muted-foreground">
                                entries
                            </span>
                        </div>
                        <Button
                            type="button"
                            size="sm"
                            onClick={() => setDialog({ type: 'create' })}
                        >
                            <Plus />
                            Add signature
                        </Button>
                    </div>
                </div>

                <div className="relative overflow-hidden rounded-xl border">
                    {loading && (
                        <div className="bg-background/70 absolute inset-0 z-10 flex items-center justify-center">
                            <Spinner className="size-6" />
                        </div>
                    )}

                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[640px] text-left text-sm">
                            <thead className="bg-muted/50 border-b">
                                <tr>
                                    <th className="px-4 py-3 font-medium">#</th>
                                    <SortableHeader
                                        label="Name"
                                        column="signature_name"
                                        icon={sortIcon('signature_name')}
                                        onSort={sortBy}
                                    />
                                    <SortableHeader
                                        label="Status"
                                        column="status"
                                        icon={sortIcon('status')}
                                        onSort={sortBy}
                                    />
                                    <SortableHeader
                                        label="Default"
                                        column="is_default"
                                        icon={sortIcon('is_default')}
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
                                {signatures.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="text-muted-foreground px-4 py-12 text-center"
                                        >
                                            No signatures found.
                                        </td>
                                    </tr>
                                ) : (
                                    signatures.data.map(
                                        (signature, rowIndex) => {
                                            const rowNumber =
                                                (signatures.meta.current_page -
                                                    1) *
                                                    signatures.meta.per_page +
                                                rowIndex +
                                                1;

                                            return (
                                                <tr
                                                    key={signature.id}
                                                    className="border-b last:border-b-0"
                                                >
                                                    <td className="px-4 py-3">
                                                        {rowNumber}
                                                    </td>
                                                    <td className="px-4 py-3 font-medium">
                                                        {
                                                            signature.signature_name
                                                        }
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <Badge
                                                            variant={
                                                                signature.status ===
                                                                'active'
                                                                    ? 'default'
                                                                    : 'secondary'
                                                            }
                                                        >
                                                            {
                                                                signature.status_label
                                                            }
                                                        </Badge>
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {signature.is_default ? (
                                                            <Badge>Default</Badge>
                                                        ) : (
                                                            <span className="text-muted-foreground">
                                                                —
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {signature.created_at}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <div className="flex justify-end gap-1">
                                                            <ActionButton
                                                                label="Edit"
                                                                onClick={() =>
                                                                    setDialog({
                                                                        type: 'edit',
                                                                        signature,
                                                                    })
                                                                }
                                                            >
                                                                <Pencil />
                                                            </ActionButton>
                                                            <ActionButton
                                                                label={
                                                                    signature.is_default
                                                                        ? 'Remove default'
                                                                        : signature.status !==
                                                                            'active'
                                                                          ? 'Activate before setting default'
                                                                          : 'Set as default'
                                                                }
                                                                disabled={
                                                                    signature.status !==
                                                                        'active' &&
                                                                    !signature.is_default
                                                                }
                                                                onClick={() =>
                                                                    setDialog({
                                                                        type: 'default',
                                                                        signature,
                                                                    })
                                                                }
                                                            >
                                                                <Star
                                                                    className={
                                                                        signature.is_default
                                                                            ? 'fill-current'
                                                                            : undefined
                                                                    }
                                                                />
                                                            </ActionButton>
                                                            <ActionButton
                                                                label="Delete"
                                                                onClick={() =>
                                                                    setDialog({
                                                                        type: 'delete',
                                                                        signature,
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
                        {signatures.meta.total === 0
                            ? 'Showing 0 entries'
                            : `Showing ${signatures.meta.from} to ${signatures.meta.to} of ${signatures.meta.total} records`}
                    </p>

                    {signatures.meta.last_page > 1 && (
                        <div className="flex flex-wrap items-center gap-1">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                disabled={signatures.meta.current_page === 1}
                                onClick={() =>
                                    visitList({
                                        page:
                                            signatures.meta.current_page - 1,
                                    })
                                }
                            >
                                Previous
                            </Button>
                            {pageNumbers.map((page, pageIndex) =>
                                page === 'ellipsis' ? (
                                    <span
                                        key={`ellipsis-${pageIndex}`}
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
                                            signatures.meta.current_page
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
                                    signatures.meta.current_page ===
                                    signatures.meta.last_page
                                }
                                onClick={() =>
                                    visitList({
                                        page:
                                            signatures.meta.current_page + 1,
                                    })
                                }
                            >
                                Next
                            </Button>
                        </div>
                    )}
                </div>
            </div>

            <EmailSignatureFormDialog
                open={dialog?.type === 'create' || dialog?.type === 'edit'}
                onOpenChange={(open) => {
                    if (!open) {
                        setDialog(null);
                    }
                }}
                signature={
                    dialog?.type === 'edit' ? dialog.signature : null
                }
            />
            <EmailSignatureDefaultDialog
                open={dialog?.type === 'default'}
                onOpenChange={(open) => {
                    if (!open) {
                        setDialog(null);
                    }
                }}
                signature={
                    dialog?.type === 'default' ? dialog.signature : null
                }
            />
            <EmailSignatureDeleteDialog
                open={dialog?.type === 'delete'}
                onOpenChange={(open) => {
                    if (!open) {
                        setDialog(null);
                    }
                }}
                signature={
                    dialog?.type === 'delete' ? dialog.signature : null
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
    column: EmailSignatureSortColumn;
    icon: ReactNode;
    onSort: (column: EmailSignatureSortColumn) => void;
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
    disabled = false,
}: {
    label: string;
    children: ReactNode;
    onClick: () => void;
    disabled?: boolean;
}) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label={label}
                    disabled={disabled}
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
