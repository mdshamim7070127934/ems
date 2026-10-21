import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo, type FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { index, store, update } from '@/routes/master/roles';
import type { MasterRole, RoleMenuGroup } from '@/types';

type Props = {
    role?: MasterRole | null;
    menuGroups: RoleMenuGroup[];
};

export default function RoleForm({ role = null, menuGroups }: Props) {
    const isEditing = role !== null;
    const isProtected = role?.is_protected === true;
    const { data, setData, post, put, processing, errors } = useForm({
        role_name: role?.role_name ?? '',
        display_name: role?.display_name ?? '',
        is_active: role?.is_active ?? true,
        menu_ids: role?.menu_ids ?? [],
    });

    const selected = useMemo(() => new Set(data.menu_ids), [data.menu_ids]);

    function toggleMenu(menuId: number, checked: boolean): void {
        if (checked) {
            setData('menu_ids', [...data.menu_ids, menuId]);

            return;
        }

        setData(
            'menu_ids',
            data.menu_ids.filter((id) => id !== menuId),
        );
    }

    function toggleGroup(group: RoleMenuGroup, checked: boolean): void {
        const groupIds = group.menus.map((menu) => menu.id);

        if (checked) {
            setData('menu_ids', [
                ...new Set([...data.menu_ids, ...groupIds]),
            ]);

            return;
        }

        setData(
            'menu_ids',
            data.menu_ids.filter((id) => !groupIds.includes(id)),
        );
    }

    function groupChecked(group: RoleMenuGroup): boolean {
        return (
            group.menus.length > 0 &&
            group.menus.every((menu) => selected.has(menu.id))
        );
    }

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        if (isProtected) {
            return;
        }

        if (isEditing && role) {
            put(update.url(role.id));

            return;
        }

        post(store.url());
    }

    return (
        <>
            <Head title={isEditing ? 'Edit role' : 'Add role'} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title={isEditing ? 'Edit role' : 'Add role'}
                        description={
                            isEditing
                                ? 'Update role details and menu access.'
                                : 'Create a new application role.'
                        }
                    />
                    <Button asChild variant="outline" className="sm:mt-1">
                        <Link href={index.url()}>Back to roles</Link>
                    </Button>
                </div>

                {isProtected && (
                    <div className="bg-muted rounded-xl border p-4 text-sm">
                        This is a Super Administrator role with full access to
                        all menus. It cannot be edited or deleted.
                    </div>
                )}

                <form
                    onSubmit={submit}
                    className="flex max-w-3xl flex-col gap-6"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="role_name">Role name</Label>
                        <Input
                            id="role_name"
                            value={data.role_name}
                            onChange={(event) =>
                                setData('role_name', event.target.value)
                            }
                            required
                            autoFocus={!isEditing}
                            maxLength={100}
                            disabled={processing || isProtected}
                            placeholder="e.g. manager"
                            pattern="[a-z][a-z0-9_]*"
                            title="Lowercase letters, numbers, and underscores only. Must start with a letter."
                        />
                        <p className="text-muted-foreground text-xs">
                            System key used internally. Lowercase, no spaces
                            (e.g. <code>manager</code>).
                        </p>
                        <InputError message={errors.role_name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="display_name">Display name</Label>
                        <Input
                            id="display_name"
                            value={data.display_name}
                            onChange={(event) =>
                                setData('display_name', event.target.value)
                            }
                            required
                            maxLength={100}
                            disabled={processing || isProtected}
                            placeholder="e.g. Manager"
                        />
                        <InputError message={errors.display_name} />
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="is_active"
                            checked={data.is_active}
                            onCheckedChange={(checked) =>
                                setData('is_active', checked === true)
                            }
                            disabled={processing || isProtected}
                        />
                        <Label htmlFor="is_active" className="font-normal">
                            Active
                        </Label>
                    </div>
                    <InputError message={errors.is_active} />

                    <div className="grid gap-3">
                        <div>
                            <Label>Menu permissions</Label>
                            <p className="text-muted-foreground mt-1 text-sm">
                                Select which menus users with this role can see
                                and access.
                            </p>
                            <InputError message={errors.menu_ids} />
                        </div>

                        {menuGroups.length === 0 ? (
                            <p className="text-muted-foreground rounded-xl border p-4 text-sm">
                                No active menus are available. Create menus
                                first, then assign them here.
                            </p>
                        ) : (
                            menuGroups.map((group) => (
                                <div
                                    key={group.id}
                                    className="grid gap-3 rounded-xl border p-4"
                                >
                                    <div className="flex items-center gap-2">
                                        <Checkbox
                                            id={`group-${group.id}`}
                                            checked={groupChecked(group)}
                                            onCheckedChange={(checked) =>
                                                toggleGroup(
                                                    group,
                                                    checked === true,
                                                )
                                            }
                                            disabled={
                                                processing || isProtected
                                            }
                                        />
                                        <Label
                                            htmlFor={`group-${group.id}`}
                                            className="font-medium"
                                        >
                                            {group.label}
                                        </Label>
                                    </div>
                                    <div className="grid gap-2 pl-6">
                                        {group.menus.map((menu) => (
                                            <div
                                                key={menu.id}
                                                className="flex items-center gap-2"
                                            >
                                                <Checkbox
                                                    id={`menu-${menu.id}`}
                                                    checked={selected.has(
                                                        menu.id,
                                                    )}
                                                    onCheckedChange={(
                                                        checked,
                                                    ) =>
                                                        toggleMenu(
                                                            menu.id,
                                                            checked === true,
                                                        )
                                                    }
                                                    disabled={
                                                        processing ||
                                                        isProtected
                                                    }
                                                />
                                                <Label
                                                    htmlFor={`menu-${menu.id}`}
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

                    <div className="flex gap-2">
                        <Button
                            type="submit"
                            disabled={processing || isProtected}
                        >
                            {processing && <Spinner />}
                            {isEditing ? 'Update role' : 'Create role'}
                        </Button>
                        <Button asChild type="button" variant="secondary">
                            <Link href={index.url()}>Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

RoleForm.layout = {
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
            title: 'Role',
            href: index(),
        },
    ],
};
