import { Head, router } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    Eye,
    Pencil,
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
import { dashboard } from '@/routes';
import { index, makeDefault } from '@/routes/master/editor-settings';
import type {
    EditorSettingFilters,
    EditorSettingSortColumn,
    MasterEditorSetting,
    PaginatedEditorSettings,
} from '@/types';
import EditorSettingDeleteDialog from './editor-setting-delete-dialog';
import EditorSettingFormDialog from './editor-setting-form-dialog';
import EditorSettingViewDialog from './editor-setting-view-dialog';

type Props = {
    editorSettings: PaginatedEditorSettings;
    filters: EditorSettingFilters;
};

type DialogState =
    | { type: 'edit'; editorSetting: MasterEditorSetting }
    | { type: 'view'; editorSetting: MasterEditorSetting }
    | { type: 'delete'; editorSetting: MasterEditorSetting }
    | null;

const pageSizes = [10, 25, 50, 100];

export default function EditorSettingsIndex({
    editorSettings,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.search);
    const [loading, setLoading] = useState(false);
    const [dialog, setDialog] = useState<DialogState>(null);

    const visitList = useCallback(
        (overrides: Partial<EditorSettingFilters> = {}) => {
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

    function sortBy(column: EditorSettingSortColumn): void {
        const sortOrder =
            filters.sortBy === column && filters.sortOrder === 'asc'
                ? 'desc'
                : 'asc';

        visitList({ sortBy: column, sortOrder, page: 1 });
    }

    function sortIcon(column: EditorSettingSortColumn) {
        if (filters.sortBy !== column) {
            return <ArrowUpDown className="size-3.5 opacity-40" />;
        }

        return filters.sortOrder === 'asc' ? (
            <ArrowUp className="size-3.5" />
        ) : (
            <ArrowDown className="size-3.5" />
        );
    }

    function setAsDefault(editorSetting: MasterEditorSetting): void {
        if (editorSetting.is_default || editorSetting.status !== 'active') {
            return;
        }

        router.post(
            makeDefault.url(editorSetting.id),
            {},
            { preserveScroll: true },
        );
    }

    const pageNumbers = visiblePages(
        editorSettings.meta.current_page,
        editorSettings.meta.last_page,
    );

    return (
        <>
            <Head title="Editor settings" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Editor settings"
                    description="Choose the system-wide default editor used for compose and reply."
                />

                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="relative w-full sm:max-w-xs">
                        <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search editors..."
                            className="pr-9 pl-9"
                            aria-label="Search editor settings"
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
                                        label="Code"
                                        column="code"
                                        icon={sortIcon('code')}
                                        onSort={sortBy}
                                    />
                                    <SortableHeader
                                        label="Name"
                                        column="name"
                                        icon={sortIcon('name')}
                                        onSort={sortBy}
                                    />
                                    <SortableHeader
                                        label="Default"
                                        column="is_default"
                                        icon={sortIcon('is_default')}
                                        onSort={sortBy}
                                    />
                                    <SortableHeader
                                        label="Sort order"
                                        column="sort_order"
                                        icon={sortIcon('sort_order')}
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
                                {editorSettings.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={8}
                                            className="text-muted-foreground px-4 py-12 text-center"
                                        >
                                            No editor settings found.
                                        </td>
                                    </tr>
                                ) : (
                                    editorSettings.data.map(
                                        (editorSetting, rowIndex) => {
                                            const rowNumber =
                                                (editorSettings.meta
                                                    .current_page -
                                                    1) *
                                                    editorSettings.meta
                                                        .per_page +
                                                rowIndex +
                                                1;

                                            return (
                                                <tr
                                                    key={editorSetting.id}
                                                    className="border-b last:border-b-0"
                                                >
                                                    <td className="px-4 py-3">
                                                        {rowNumber}
                                                    </td>
                                                    <td className="px-4 py-3 font-mono text-xs">
                                                        {editorSetting.code}
                                                    </td>
                                                    <td className="px-4 py-3 font-medium">
                                                        {editorSetting.name}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {editorSetting.is_default ? (
                                                            <Badge>Default</Badge>
                                                        ) : (
                                                            <span className="text-muted-foreground">
                                                                —
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {editorSetting.sort_order}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <Badge
                                                            variant={
                                                                editorSetting.status ===
                                                                'active'
                                                                    ? 'default'
                                                                    : 'secondary'
                                                            }
                                                        >
                                                            {
                                                                editorSetting.status_label
                                                            }
                                                        </Badge>
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {editorSetting.created_at}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <div className="flex justify-end gap-1">
                                                            <ActionButton
                                                                label="View"
                                                                onClick={() =>
                                                                    setDialog({
                                                                        type: 'view',
                                                                        editorSetting,
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
                                                                        editorSetting,
                                                                    })
                                                                }
                                                            >
                                                                <Pencil />
                                                            </ActionButton>
                                                            <ActionButton
                                                                label={
                                                                    editorSetting.is_default
                                                                        ? 'Current default'
                                                                        : editorSetting.status !==
                                                                            'active'
                                                                          ? 'Activate before setting default'
                                                                          : 'Set as default'
                                                                }
                                                                disabled={
                                                                    editorSetting.is_default ||
                                                                    editorSetting.status !==
                                                                        'active'
                                                                }
                                                                onClick={() =>
                                                                    setAsDefault(
                                                                        editorSetting,
                                                                    )
                                                                }
                                                            >
                                                                <Star
                                                                    className={
                                                                        editorSetting.is_default
                                                                            ? 'fill-current'
                                                                            : undefined
                                                                    }
                                                                />
                                                            </ActionButton>
                                                            <ActionButton
                                                                label={
                                                                    editorSetting.is_default
                                                                        ? 'Default cannot be deleted'
                                                                        : 'Delete'
                                                                }
                                                                disabled={
                                                                    editorSetting.is_default
                                                                }
                                                                onClick={() =>
                                                                    setDialog({
                                                                        type: 'delete',
                                                                        editorSetting,
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
                        {editorSettings.meta.total === 0
                            ? 'Showing 0 entries'
                            : `Showing ${editorSettings.meta.from} to ${editorSettings.meta.to} of ${editorSettings.meta.total} records`}
                    </p>

                    {editorSettings.meta.last_page > 1 && (
                        <div className="flex flex-wrap items-center gap-1">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                disabled={
                                    editorSettings.meta.current_page === 1
                                }
                                onClick={() =>
                                    visitList({
                                        page:
                                            editorSettings.meta.current_page -
                                            1,
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
                                            editorSettings.meta.current_page
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
                                    editorSettings.meta.current_page ===
                                    editorSettings.meta.last_page
                                }
                                onClick={() =>
                                    visitList({
                                        page:
                                            editorSettings.meta.current_page +
                                            1,
                                    })
                                }
                            >
                                Next
                            </Button>
                        </div>
                    )}
                </div>
            </div>

            <EditorSettingFormDialog
                open={dialog?.type === 'edit'}
                onOpenChange={(open) => {
                    if (!open) {
                        setDialog(null);
                    }
                }}
                editorSetting={
                    dialog?.type === 'edit' ? dialog.editorSetting : null
                }
            />
            <EditorSettingViewDialog
                open={dialog?.type === 'view'}
                onOpenChange={(open) => {
                    if (!open) {
                        setDialog(null);
                    }
                }}
                editorSetting={
                    dialog?.type === 'view' ? dialog.editorSetting : null
                }
            />
            <EditorSettingDeleteDialog
                open={dialog?.type === 'delete'}
                onOpenChange={(open) => {
                    if (!open) {
                        setDialog(null);
                    }
                }}
                editorSetting={
                    dialog?.type === 'delete' ? dialog.editorSetting : null
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
    column: EditorSettingSortColumn;
    icon: ReactNode;
    onSort: (column: EditorSettingSortColumn) => void;
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

EditorSettingsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Editor settings',
            href: index(),
        },
    ],
};
