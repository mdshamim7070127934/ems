import { Head, router } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    Eye,
    Pencil,
    Plus,
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
import { index } from '@/routes/master/dropdown-option-values';
import type {
    DropdownModuleOption,
    DropdownParentOption,
    DropdownTypeOption,
    MasterDropdownOptionValue,
    OptionValueFilters,
    OptionValueSortColumn,
    PaginatedOptionValues,
} from '@/types';
import OptionValueDeleteDialog from './option-value-delete-dialog';
import OptionValueFormDialog from './option-value-form-dialog';
import OptionValueViewDialog from './option-value-view-dialog';

type Props = {
    optionValues: PaginatedOptionValues;
    filters: OptionValueFilters;
    moduleOptions: DropdownModuleOption[];
    dropdownOptions: DropdownTypeOption[];
    parentOptions: DropdownParentOption[];
};

type DialogState =
    | { type: 'add' }
    | { type: 'edit'; optionValue: MasterDropdownOptionValue }
    | { type: 'view'; optionValue: MasterDropdownOptionValue }
    | { type: 'delete'; optionValue: MasterDropdownOptionValue }
    | null;

const pageSizes = [10, 25, 50, 100];

export default function OptionValuesIndex({
    optionValues,
    filters,
    moduleOptions,
    dropdownOptions,
    parentOptions,
}: Props) {
    const [search, setSearch] = useState(filters.search);
    const [loading, setLoading] = useState(false);
    const [dialog, setDialog] = useState<DialogState>(null);

    const visitList = useCallback(
        (overrides: Partial<OptionValueFilters> = {}) => {
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

    function sortBy(column: OptionValueSortColumn): void {
        const sortOrder =
            filters.sortBy === column && filters.sortOrder === 'asc'
                ? 'desc'
                : 'asc';

        visitList({ sortBy: column, sortOrder, page: 1 });
    }

    function sortIcon(column: OptionValueSortColumn) {
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
        optionValues.meta.current_page,
        optionValues.meta.last_page,
    );

    return (
        <>
            <Head title="Option Values" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Option Values"
                        description="Manage values for master dropdowns."
                    />
                    <Button
                        onClick={() => setDialog({ type: 'add' })}
                        className="sm:mt-1"
                    >
                        <Plus />
                        Add option value
                    </Button>
                </div>

                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="relative w-full sm:max-w-xs">
                        <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search option values..."
                            className="pr-9 pl-9"
                            aria-label="Search option values"
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
                        <table className="w-full min-w-[980px] text-left text-sm">
                            <thead className="bg-muted/50 border-b">
                                <tr>
                                    <th className="px-4 py-3 font-medium">#</th>
                                    <SortableHeader
                                        label="Option value"
                                        column="option_value"
                                        icon={sortIcon('option_value')}
                                        onSort={sortBy}
                                    />
                                    <SortableHeader
                                        label="Dropdown"
                                        column="dropdown_name"
                                        icon={sortIcon('dropdown_name')}
                                        onSort={sortBy}
                                    />
                                    <SortableHeader
                                        label="Module"
                                        column="module_name"
                                        icon={sortIcon('module_name')}
                                        onSort={sortBy}
                                    />
                                    <th className="px-4 py-3 font-medium">
                                        Parent
                                    </th>
                                    <SortableHeader
                                        label="Position"
                                        column="position"
                                        icon={sortIcon('position')}
                                        onSort={sortBy}
                                    />
                                    <SortableHeader
                                        label="Status"
                                        column="status"
                                        icon={sortIcon('status')}
                                        onSort={sortBy}
                                    />
                                    <SortableHeader
                                        label="Created date"
                                        column="created_on"
                                        icon={sortIcon('created_on')}
                                        onSort={sortBy}
                                    />
                                    <th className="px-4 py-3 text-right font-medium">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {optionValues.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={9}
                                            className="text-muted-foreground px-4 py-12 text-center"
                                        >
                                            No option values found.
                                        </td>
                                    </tr>
                                ) : (
                                    optionValues.data.map(
                                        (optionValue, rowIndex) => {
                                            const rowNumber =
                                                (optionValues.meta
                                                    .current_page -
                                                    1) *
                                                    optionValues.meta.per_page +
                                                rowIndex +
                                                1;

                                            return (
                                                <tr
                                                    key={optionValue.id}
                                                    className="border-b last:border-b-0"
                                                >
                                                    <td className="px-4 py-3">
                                                        {rowNumber}
                                                    </td>
                                                    <td className="px-4 py-3 font-medium">
                                                        {
                                                            optionValue.option_value
                                                        }
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {
                                                            optionValue.dropdown_name
                                                        }
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {
                                                            optionValue.module_name
                                                        }
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {optionValue.parent_value ??
                                                            'None'}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {optionValue.position ??
                                                            '—'}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <Badge
                                                            variant={
                                                                optionValue.status ===
                                                                'active'
                                                                    ? 'default'
                                                                    : 'secondary'
                                                            }
                                                        >
                                                            {
                                                                optionValue.status_label
                                                            }
                                                        </Badge>
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {optionValue.created_on}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <div className="flex justify-end gap-1">
                                                            <ActionButton
                                                                label="View"
                                                                onClick={() =>
                                                                    setDialog({
                                                                        type: 'view',
                                                                        optionValue,
                                                                    })
                                                                }
                                                            >
                                                                <Eye />
                                                            </ActionButton>
                                                            <ActionButton
                                                                label="Edit"
                                                                onClick={() =>
                                                                    setDialog({
                                                                        type: 'edit',
                                                                        optionValue,
                                                                    })
                                                                }
                                                            >
                                                                <Pencil />
                                                            </ActionButton>
                                                            <ActionButton
                                                                label="Delete"
                                                                onClick={() =>
                                                                    setDialog({
                                                                        type: 'delete',
                                                                        optionValue,
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
                        {optionValues.meta.total === 0
                            ? 'Showing 0 records'
                            : `Showing ${optionValues.meta.from} to ${optionValues.meta.to} of ${optionValues.meta.total} records`}
                    </p>

                    {optionValues.meta.last_page > 1 && (
                        <div className="flex flex-wrap items-center gap-1">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                disabled={optionValues.meta.current_page <= 1}
                                onClick={() =>
                                    visitList({
                                        page:
                                            optionValues.meta.current_page - 1,
                                    })
                                }
                            >
                                Previous
                            </Button>
                            {pageNumbers.map((page, index) =>
                                page === 'ellipsis' ? (
                                    <span
                                        key={`ellipsis-${index}`}
                                        className="text-muted-foreground px-2 text-sm"
                                    >
                                        …
                                    </span>
                                ) : (
                                    <Button
                                        key={page}
                                        type="button"
                                        variant={
                                            page ===
                                            optionValues.meta.current_page
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
                                    optionValues.meta.current_page >=
                                    optionValues.meta.last_page
                                }
                                onClick={() =>
                                    visitList({
                                        page:
                                            optionValues.meta.current_page + 1,
                                    })
                                }
                            >
                                Next
                            </Button>
                        </div>
                    )}
                </div>
            </div>

            <OptionValueFormDialog
                open={dialog?.type === 'add' || dialog?.type === 'edit'}
                onOpenChange={(open) => {
                    if (!open) {
                        setDialog(null);
                    }
                }}
                optionValue={
                    dialog?.type === 'edit' ? dialog.optionValue : null
                }
                moduleOptions={moduleOptions}
                dropdownOptions={dropdownOptions}
                parentOptions={parentOptions}
            />
            <OptionValueViewDialog
                open={dialog?.type === 'view'}
                onOpenChange={(open) => {
                    if (!open) {
                        setDialog(null);
                    }
                }}
                optionValue={
                    dialog?.type === 'view' ? dialog.optionValue : null
                }
            />
            <OptionValueDeleteDialog
                open={dialog?.type === 'delete'}
                onOpenChange={(open) => {
                    if (!open) {
                        setDialog(null);
                    }
                }}
                optionValue={
                    dialog?.type === 'delete' ? dialog.optionValue : null
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
    column: OptionValueSortColumn;
    icon: ReactNode;
    onSort: (column: OptionValueSortColumn) => void;
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

OptionValuesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Option Values',
            href: index(),
        },
    ],
};
