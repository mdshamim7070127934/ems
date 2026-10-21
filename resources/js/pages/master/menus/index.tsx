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
import { index } from '@/routes/master/menus';
import type {
    MasterMenu,
    MenuFilters,
    MenuParentOption,
    MenuSortColumn,
    PaginatedMenus,
} from '@/types';
import MenuDeleteDialog from './menu-delete-dialog';
import MenuFormDialog from './menu-form-dialog';
import MenuViewDialog from './menu-view-dialog';

type Props = {
    menus: PaginatedMenus;
    filters: MenuFilters;
    parentOptions: MenuParentOption[];
};

type DialogState =
    | { type: 'add' }
    | { type: 'edit'; menu: MasterMenu }
    | { type: 'view'; menu: MasterMenu }
    | { type: 'delete'; menu: MasterMenu }
    | null;

const pageSizes = [10, 25, 50, 100];

export default function MenusIndex({ menus, filters, parentOptions }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [loading, setLoading] = useState(false);
    const [dialog, setDialog] = useState<DialogState>(null);

    const visitList = useCallback(
        (overrides: Partial<MenuFilters> = {}) => {
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

    function sortBy(column: MenuSortColumn): void {
        const sortOrder =
            filters.sortBy === column && filters.sortOrder === 'asc'
                ? 'desc'
                : 'asc';

        visitList({ sortBy: column, sortOrder, page: 1 });
    }

    function sortIcon(column: MenuSortColumn) {
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
        menus.meta.current_page,
        menus.meta.last_page,
    );

    return (
        <>
            <Head title="Menus" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Menus"
                        description="Manage master navigation menus used across web and mobile."
                    />
                    <Button
                        onClick={() => setDialog({ type: 'add' })}
                        className="sm:mt-1"
                    >
                        <Plus />
                        Add menu
                    </Button>
                </div>

                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="relative w-full sm:max-w-xs">
                        <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search menus..."
                            className="pr-9 pl-9"
                            aria-label="Search menus"
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
                        <table className="w-full min-w-[900px] text-left text-sm">
                            <thead className="bg-muted/50 border-b">
                                <tr>
                                    <th className="px-4 py-3 font-medium">#</th>
                                    <SortableHeader
                                        label="Menu name"
                                        column="menu_name"
                                        icon={sortIcon('menu_name')}
                                        onSort={sortBy}
                                    />
                                    <SortableHeader
                                        label="Parent"
                                        column="parent_name"
                                        icon={sortIcon('parent_name')}
                                        onSort={sortBy}
                                    />
                                    <SortableHeader
                                        label="Route"
                                        column="menu_route"
                                        icon={sortIcon('menu_route')}
                                        onSort={sortBy}
                                    />
                                    <SortableHeader
                                        label="Order"
                                        column="menu_order"
                                        icon={sortIcon('menu_order')}
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
                                {menus.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={8}
                                            className="text-muted-foreground px-4 py-12 text-center"
                                        >
                                            No menus found.
                                        </td>
                                    </tr>
                                ) : (
                                    menus.data.map((menu, rowIndex) => {
                                        const rowNumber =
                                            (menus.meta.current_page - 1) *
                                                menus.meta.per_page +
                                            rowIndex +
                                            1;

                                        return (
                                            <tr
                                                key={menu.id}
                                                className="border-b last:border-b-0"
                                            >
                                                <td className="px-4 py-3">
                                                    {rowNumber}
                                                </td>
                                                <td className="px-4 py-3 font-medium">
                                                    {menu.menu_name}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {menu.parent_name ??
                                                        'Main menu'}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {menu.menu_route}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {menu.menu_order}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge
                                                        variant={
                                                            menu.status ===
                                                            'active'
                                                                ? 'default'
                                                                : 'secondary'
                                                        }
                                                    >
                                                        {menu.status_label}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3">
                                                    {menu.created_at}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="flex justify-end gap-1">
                                                        <ActionButton
                                                            label="View"
                                                            onClick={() =>
                                                                setDialog({
                                                                    type: 'view',
                                                                    menu,
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
                                                                    menu,
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
                                                                    menu,
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
                        {menus.meta.total === 0
                            ? 'Showing 0 records'
                            : `Showing ${menus.meta.from} to ${menus.meta.to} of ${menus.meta.total} records`}
                    </p>

                    {menus.meta.last_page > 1 && (
                        <div className="flex flex-wrap items-center gap-1">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                disabled={menus.meta.current_page <= 1}
                                onClick={() =>
                                    visitList({
                                        page: menus.meta.current_page - 1,
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
                                            page === menus.meta.current_page
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
                                    menus.meta.current_page >=
                                    menus.meta.last_page
                                }
                                onClick={() =>
                                    visitList({
                                        page: menus.meta.current_page + 1,
                                    })
                                }
                            >
                                Next
                            </Button>
                        </div>
                    )}
                </div>
            </div>

            <MenuFormDialog
                open={dialog?.type === 'add' || dialog?.type === 'edit'}
                onOpenChange={(open) => {
                    if (!open) {
                        setDialog(null);
                    }
                }}
                menu={dialog?.type === 'edit' ? dialog.menu : null}
                parentOptions={parentOptions}
            />
            <MenuViewDialog
                open={dialog?.type === 'view'}
                onOpenChange={(open) => {
                    if (!open) {
                        setDialog(null);
                    }
                }}
                menu={dialog?.type === 'view' ? dialog.menu : null}
            />
            <MenuDeleteDialog
                open={dialog?.type === 'delete'}
                onOpenChange={(open) => {
                    if (!open) {
                        setDialog(null);
                    }
                }}
                menu={dialog?.type === 'delete' ? dialog.menu : null}
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
    column: MenuSortColumn;
    icon: ReactNode;
    onSort: (column: MenuSortColumn) => void;
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

MenusIndex.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Menus',
            href: index(),
        },
    ],
};
