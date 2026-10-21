import { Head, Link } from '@inertiajs/react';
import { useMemo } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { edit, index } from '@/routes/master/roles';
import type { MasterRole, RoleMenuGroup } from '@/types';

type Props = {
    role: MasterRole;
    menuGroups: RoleMenuGroup[];
};

export default function RoleShow({ role, menuGroups }: Props) {
    const selected = useMemo(() => new Set(role.menu_ids), [role.menu_ids]);

    return (
        <>
            <Head title={`View role - ${role.display_name}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="View role"
                        description="Read-only details for this application role."
                    />
                    <div className="flex gap-2 sm:mt-1">
                        {!role.is_protected && (
                            <Button asChild>
                                <Link href={edit.url(role.id)}>Edit role</Link>
                            </Button>
                        )}
                        <Button asChild variant="outline">
                            <Link href={index.url()}>Back to roles</Link>
                        </Button>
                    </div>
                </div>

                {role.is_protected && (
                    <div className="bg-muted rounded-xl border p-4 text-sm">
                        This is a Super Administrator role with full access to
                        all menus. It cannot be edited or deleted.
                    </div>
                )}

                <div className="flex max-w-3xl flex-col gap-6">
                    <dl className="grid gap-4 text-sm sm:grid-cols-2">
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Role name</dt>
                            <dd className="font-mono text-xs">
                                {role.role_name}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Display name
                            </dt>
                            <dd className="font-medium">{role.display_name}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Status</dt>
                            <dd>
                                <Badge
                                    variant={
                                        role.is_protected || role.is_active
                                            ? 'default'
                                            : 'secondary'
                                    }
                                >
                                    {role.status_label}
                                </Badge>
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Super administrator
                            </dt>
                            <dd className="font-medium">
                                {role.is_super_admin ? 'Yes' : 'No'}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Created date
                            </dt>
                            <dd className="font-medium">{role.created_at}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Updated date
                            </dt>
                            <dd className="font-medium">{role.updated_at}</dd>
                        </div>
                    </dl>

                    <div className="grid gap-3">
                        <div>
                            <Label>Menu permissions</Label>
                            <p className="text-muted-foreground mt-1 text-sm">
                                Menus this role can see and access.
                            </p>
                        </div>

                        {menuGroups.length === 0 ? (
                            <p className="text-muted-foreground rounded-xl border p-4 text-sm">
                                No active menus are available.
                            </p>
                        ) : (
                            menuGroups.map((group) => (
                                <div
                                    key={group.id}
                                    className="grid gap-3 rounded-xl border p-4"
                                >
                                    <p className="font-medium">{group.label}</p>
                                    <div className="grid gap-2">
                                        {group.menus.map((menu) => (
                                            <div
                                                key={menu.id}
                                                className="flex items-center gap-2"
                                            >
                                                <Checkbox
                                                    id={`view-menu-${menu.id}`}
                                                    checked={selected.has(
                                                        menu.id,
                                                    )}
                                                    disabled
                                                />
                                                <Label
                                                    htmlFor={`view-menu-${menu.id}`}
                                                    className="font-normal"
                                                >
                                                    {menu.menu_name}
                                                    <span className="text-muted-foreground ml-2 text-xs">
                                                        ({menu.menu_route})
                                                    </span>
                                                </Label>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

RoleShow.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Roles',
            href: index(),
        },
        {
            title: 'View role',
            href: index(),
        },
    ],
};
