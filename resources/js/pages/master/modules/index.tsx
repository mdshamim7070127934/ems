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
import { index } from '@/routes/master/modules';
import type {
    MasterModule,
    ModuleFilters,
    ModuleSortColumn,
    PaginatedModules,
} from '@/types';
import ModuleDeleteDialog from './module-delete-dialog';
import ModuleFormDialog from './module-form-dialog';
import ModuleViewDialog from './module-view-dialog';

type Props = {
    modules: PaginatedModules;
    filters: ModuleFilters;
};

type DialogState =
    | { type: 'add' }
    | { type: 'edit'; module: MasterModule }
    | { type: 'view'; module: MasterModule }
    | { type: 'delete'; module: MasterModule }
    | null;

const pageSizes = [10, 25, 50, 100];

export default function ModulesIndex({ modules, filters }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [loading, setLoading] = useState(false);
    const [dialog, setDialog] = useState<DialogState>(null);

    const visitList = useCallback(
        (overrides: Partial<ModuleFilters> = {}) => {
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

    function sortBy(column: ModuleSortColumn): void {
        const sortOrder =
            filters.sortBy === column && filters.sortOrder === 'asc'
                ? 'desc'
                : 'asc';

        visitList({ sortBy: column, sortOrder, page: 1 });
    }

    function sortIcon(column: ModuleSortColumn) {
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
        modules.meta.current_page,
        modules.meta.last_page,
    );

    return (
        <>
            <Head title="Modules" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Modules"
                        description="Manage master modules used across the application."
                    />
                    <Button
                        onClick={() => setDialog({ type: 'add' })}
                        className="sm:mt-1"
                    >
                        <Plus />
                        Add module
                    </Button>
                </div>

                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="relative w-full sm:max-w-xs">
                        <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search modules..."
                            className="pr-9 pl-9"
                            aria-label="Search modules"
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
                        <table className="w-full min-w-[720px] text-left text-sm">
                            <thead className="bg-muted/50 border-b">
                                <tr>
                                    <th className="px-4 py-3 font-medium">#</th>
                                    <SortableHeader
                                        label="Name"
                                        column="module_name"
                                        icon={sortIcon('module_name')}
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
                                    <SortableHeader
                                        label="Updated date"
                                        column="updated_on"
                                        icon={sortIcon('updated_on')}
                                        onSort={sortBy}
                                    />
                                    <th className="px-4 py-3 text-right font-medium">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {modules.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="text-muted-foreground px-4 py-12 text-center"
                                        >
                                            No modules found.
                                        </td>
                                    </tr>
                                ) : (
                                    modules.data.map((module, rowIndex) => {
                                        const rowNumber =
                                            (modules.meta.current_page - 1) *
                                                modules.meta.per_page +
                                            rowIndex +
                                            1;

                                        return (
                                            <tr
                                                key={module.module_id}
                                                className="border-b last:border-b-0"
                                            >
                                                <td className="px-4 py-3">
                                                    {rowNumber}
                                                </td>
                                                <td className="px-4 py-3 font-medium">
                                                    {module.module_name}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge
                                                        variant={
                                                            module.status ===
                                                            'active'
                                                                ? 'default'
                                                                : 'secondary'
                                                        }
                                                    >
                                                        {module.status_label}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3">
                                                    {module.created_on}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {module.updated_on}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="flex justify-end gap-1">
                                                        <ActionButton
                                                            label="View"
                                                            onClick={() =>
                                                                setDialog({
                                                                    type: 'view',
                                                                    module,
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
                                                                    module,
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
                                                                    module,
                                                                })
                                                            }
                                                        >
                                                            <Trash2 />
                                                        </ActionButton>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-muted-foreground text-sm">
                        {modules.meta.total === 0
                            ? 'Showing 0 records'
                            : `Showing ${modules.meta.from} to ${modules.meta.to} of ${modules.meta.total} records`}
                    </p>

                    {modules.meta.last_page > 1 && (
                        <div className="flex flex-wrap items-center gap-1">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                disabled={modules.meta.current_page <= 1}
                                onClick={() =>
                                    visitList({
                                        page: modules.meta.current_page - 1,
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
                                            page === modules.meta.current_page
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
                                    modules.meta.current_page >=
                                    modules.meta.last_page
                                }
                                onClick={() =>
                                    visitList({
                                        page: modules.meta.current_page + 1,
                                    })
                                }
                            >
                                Next
                            </Button>
                        </div>
                    )}
                </div>
            </div>

            <ModuleFormDialog
                open={dialog?.type === 'add' || dialog?.type === 'edit'}
                onOpenChange={(open) => {
                    if (!open) {
                        setDialog(null);
                    }
                }}
                module={dialog?.type === 'edit' ? dialog.module : null}
            />
            <ModuleViewDialog
                open={dialog?.type === 'view'}
                onOpenChange={(open) => {
                    if (!open) {
                        setDialog(null);
                    }
                }}
                module={dialog?.type === 'view' ? dialog.module : null}
            />
            <ModuleDeleteDialog
                open={dialog?.type === 'delete'}
                onOpenChange={(open) => {
                    if (!open) {
                        setDialog(null);
                    }
                }}
                module={dialog?.type === 'delete' ? dialog.module : null}
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
    column: ModuleSortColumn;
    icon: ReactNode;
    onSort: (column: ModuleSortColumn) => void;
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

ModulesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Modules',
            href: index(),
        },
    ],
};
