import { Head, Link, router } from '@inertiajs/react';
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
import { create, edit, index, show } from '@/routes/master/roles';
import type {
    MasterRole,
    PaginatedRoles,
    RoleFilters,
    RoleSortColumn,
} from '@/types';
import RoleDeleteDialog from './role-delete-dialog';

type Props = {
    roles: PaginatedRoles;
    filters: RoleFilters;
};

type DialogState = { type: 'delete'; role: MasterRole } | null;

const pageSizes = [10, 25, 50, 100];

export default function RolesIndex({ roles, filters }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [loading, setLoading] = useState(false);
    const [dialog, setDialog] = useState<DialogState>(null);

    const visitList = useCallback(
        (overrides: Partial<RoleFilters> = {}) => {
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

    function sortBy(column: RoleSortColumn): void {
        const sortOrder =
            filters.sortBy === column && filters.sortOrder === 'asc'
                ? 'desc'
                : 'asc';

        visitList({ sortBy: column, sortOrder, page: 1 });
    }

    function sortIcon(column: RoleSortColumn) {
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
        roles.meta.current_page,
        roles.meta.last_page,
    );

    return (
        <>
            <Head title="Roles" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Roles"
                        description="Create and manage application roles and menu access."
                    />
                    <Button asChild className="sm:mt-1">
                        <Link href={create.url()}>
                            <Plus />
                            Add role
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="relative w-full sm:max-w-xs">
                        <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search roles..."
                            className="pr-9 pl-9"
                            aria-label="Search roles"
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
                        <table className="w-full min-w-[820px] text-left text-sm">
                            <thead className="bg-muted/50 border-b">
                                <tr>
                                    <th className="px-4 py-3 font-medium">#</th>
                                    <SortableHeader
                                        label="Role name"
                                        column="role_name"
                                        icon={sortIcon('role_name')}
                                        onSort={sortBy}
                                    />
                                    <SortableHeader
                                        label="Display name"
                                        column="display_name"
                                        icon={sortIcon('display_name')}
                                        onSort={sortBy}
                                    />
                                    <SortableHeader
                                        label="Status"
                                        column="is_active"
                                        icon={sortIcon('is_active')}
                                        onSort={sortBy}
                                    />
                                    <SortableHeader
                                        label="Created date"
                                        column="created_at"
                                        icon={sortIcon('created_at')}
                                        onSort={sortBy}
                                    />
                                    <SortableHeader
                                        label="Updated date"
                                        column="updated_at"
                                        icon={sortIcon('updated_at')}
                                        onSort={sortBy}
                                    />
                                    <th className="px-4 py-3 text-right font-medium">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {roles.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="text-muted-foreground px-4 py-12 text-center"
                                        >
                                            No roles found.
                                        </td>
                                    </tr>
                                ) : (
                                    roles.data.map((role, rowIndex) => {
                                        const rowNumber =
                                            (roles.meta.current_page - 1) *
                                                roles.meta.per_page +
                                            rowIndex +
                                            1;

                                        return (
                                            <tr
                                                key={role.id}
                                                className="border-b last:border-b-0"
                                            >
                                                <td className="px-4 py-3">
                                                    {rowNumber}
                                                </td>
                                                <td className="px-4 py-3 font-mono text-xs">
                                                    {role.role_name}
                                                </td>
                                                <td className="px-4 py-3 font-medium">
                                                    {role.display_name}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge
                                                        variant={
                                                            role.is_protected
                                                                ? 'default'
                                                                : role.is_active
                                                                  ? 'default'
                                                                  : 'secondary'
                                                        }
                                                    >
                                                        {role.status_label}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3">
                                                    {role.created_at}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {role.updated_at}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="flex justify-end gap-1">
                                                        <ActionButton
                                                            label="View"
                                                            onClick={() =>
                                                                router.visit(
                                                                    show.url(
                                                                        role.id,
                                                                    ),
                                                                )
                                                            }
                                                        >
                                                            <Eye />
                                                        </ActionButton>
                                                        {role.is_protected ? (
                                                            <span className="text-muted-foreground px-2 py-2 text-xs">
                                                                Protected
                                                            </span>
                                                        ) : (
                                                            <>
                                                                <ActionButton
                                                                    label="Edit"
                                                                    onClick={() =>
                                                                        router.visit(
                                                                            edit.url(
                                                                                role.id,
                                                                            ),
                                                                        )
                                                                    }
                                                                >
                                                                    <Pencil />
                                                                </ActionButton>
                                                                <ActionButton
                                                                    label="Delete"
                                                                    onClick={() =>
                                                                        setDialog(
                                                                            {
                                                                                type: 'delete',
                                                                                role,
                                                                            },
                                                                        )
                                                                    }
                                                                >
                                                                    <Trash2 />
                                                                </ActionButton>
                                                            </>
                                                        )}
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
                        {roles.meta.total === 0
                            ? 'Showing 0 entries'
                            : `Showing ${roles.meta.from} to ${roles.meta.to} of ${roles.meta.total} records`}
                    </p>

                    {roles.meta.last_page > 1 && (
                        <div className="flex flex-wrap items-center gap-1">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                disabled={roles.meta.current_page === 1}
                                onClick={() =>
                                    visitList({
                                        page: roles.meta.current_page - 1,
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
                                            page === roles.meta.current_page
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
                                    roles.meta.current_page ===
                                    roles.meta.last_page
                                }
                                onClick={() =>
                                    visitList({
                                        page: roles.meta.current_page + 1,
                                    })
                                }
                            >
                                Next
                            </Button>
                        </div>
                    )}
                </div>
            </div>

            <RoleDeleteDialog
                open={dialog?.type === 'delete'}
                onOpenChange={(open) => {
                    if (!open) {
                        setDialog(null);
                    }
                }}
                role={dialog?.type === 'delete' ? dialog.role : null}
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
    column: RoleSortColumn;
    icon: ReactNode;
    onSort: (column: RoleSortColumn) => void;
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

RolesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Roles',
            href: index(),
        },
    ],
};
