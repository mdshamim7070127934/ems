import { useForm } from '@inertiajs/react';
import { useEffect, useMemo, type FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { store, update } from '@/routes/master/menus';
import type { MasterMenu, MenuParentOption, ModuleStatus } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    menu?: MasterMenu | null;
    parentOptions: MenuParentOption[];
};

const emptyForm = {
    menu_parent_id: '0',
    menu_name: '',
    column_description: '',
    menu_route: '',
    menu_order: '0',
    screen_name: '',
    web_icon: '',
    mobile_icon: '',
    status: 'active' as ModuleStatus,
};

export default function MenuFormDialog({
    open,
    onOpenChange,
    menu = null,
    parentOptions,
}: Props) {
    const isEditing = menu !== null;
    const { data, setData, post, put, processing, errors, reset, clearErrors } =
        useForm(emptyForm);

    const selectableParents = useMemo(() => {
        return parentOptions.filter((parent) => !menu || parent.id !== menu.id);
    }, [menu, parentOptions]);

    useEffect(() => {
        if (!open) {
            return;
        }

        clearErrors();

        if (menu) {
            setData({
                menu_parent_id: String(menu.menu_parent_id),
                menu_name: menu.menu_name,
                column_description: menu.column_description ?? '',
                menu_route: menu.menu_route,
                menu_order: String(menu.menu_order),
                screen_name: menu.screen_name ?? '',
                web_icon: menu.web_icon ?? '',
                mobile_icon: menu.mobile_icon ?? '',
                status: menu.status,
            });

            return;
        }

        reset();
        setData(emptyForm);
    }, [menu, open]);

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        };

        if (isEditing && menu) {
            put(update.url(menu.id), options);

            return;
        }

        post(store.url(), options);
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Edit menu' : 'Add menu'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEditing
                            ? 'Update this master menu item.'
                            : 'Create a new master menu item.'}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="menu_parent_id">Parent</Label>
                        <Select
                            value={data.menu_parent_id}
                            onValueChange={(value) =>
                                setData('menu_parent_id', value)
                            }
                            disabled={processing}
                        >
                            <SelectTrigger
                                id="menu_parent_id"
                                className="w-full"
                            >
                                <SelectValue placeholder="Main menu" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="0">Main menu</SelectItem>
                                {selectableParents.map((parent) => (
                                    <SelectItem
                                        key={parent.id}
                                        value={String(parent.id)}
                                    >
                                        {parent.menu_name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.menu_parent_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="menu_name">Menu name</Label>
                        <Input
                            id="menu_name"
                            name="menu_name"
                            value={data.menu_name}
                            onChange={(event) =>
                                setData('menu_name', event.target.value)
                            }
                            required
                            autoFocus
                            maxLength={100}
                            disabled={processing}
                            placeholder="Menu name"
                        />
                        <InputError message={errors.menu_name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="menu_route">Route</Label>
                        <Input
                            id="menu_route"
                            name="menu_route"
                            value={data.menu_route}
                            onChange={(event) =>
                                setData('menu_route', event.target.value)
                            }
                            required
                            maxLength={100}
                            disabled={processing}
                            placeholder="/dashboard"
                        />
                        <InputError message={errors.menu_route} />
                    </div>

                    <div className="grid gap-2 sm:grid-cols-2 sm:gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="menu_order">Order</Label>
                            <Input
                                id="menu_order"
                                name="menu_order"
                                type="number"
                                value={data.menu_order}
                                onChange={(event) =>
                                    setData('menu_order', event.target.value)
                                }
                                required
                                disabled={processing}
                            />
                            <InputError message={errors.menu_order} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="screen_name">Screen name</Label>
                            <Input
                                id="screen_name"
                                name="screen_name"
                                value={data.screen_name}
                                onChange={(event) =>
                                    setData('screen_name', event.target.value)
                                }
                                maxLength={255}
                                disabled={processing}
                                placeholder="Optional"
                            />
                            <InputError message={errors.screen_name} />
                        </div>
                    </div>

                    <div className="grid gap-2 sm:grid-cols-2 sm:gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="web_icon">Web icon</Label>
                            <Input
                                id="web_icon"
                                name="web_icon"
                                value={data.web_icon}
                                onChange={(event) =>
                                    setData('web_icon', event.target.value)
                                }
                                maxLength={255}
                                disabled={processing}
                                placeholder="Optional"
                            />
                            <InputError message={errors.web_icon} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="mobile_icon">Mobile icon</Label>
                            <Input
                                id="mobile_icon"
                                name="mobile_icon"
                                value={data.mobile_icon}
                                onChange={(event) =>
                                    setData('mobile_icon', event.target.value)
                                }
                                maxLength={255}
                                disabled={processing}
                                placeholder="Optional"
                            />
                            <InputError message={errors.mobile_icon} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="column_description">Description</Label>
                        <textarea
                            id="column_description"
                            name="column_description"
                            value={data.column_description}
                            onChange={(event) =>
                                setData(
                                    'column_description',
                                    event.target.value,
                                )
                            }
                            disabled={processing}
                            placeholder="Optional"
                            rows={3}
                            className={cn(
                                'border-input placeholder:text-muted-foreground flex min-h-20 w-full rounded-md border bg-transparent px-3 py-2 text-base shadow-xs outline-none md:text-sm',
                                'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                                'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50',
                            )}
                        />
                        <InputError message={errors.column_description} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="status">Status</Label>
                        <Select
                            value={data.status}
                            onValueChange={(value) => {
                                if (
                                    value === 'active' ||
                                    value === 'inactive'
                                ) {
                                    setData('status', value);
                                }
                            }}
                            disabled={processing}
                        >
                            <SelectTrigger id="status" className="w-full">
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="active">Active</SelectItem>
                                <SelectItem value="inactive">
                                    Inactive
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.status} />
                    </div>

                    <DialogFooter>
                        <DialogClose asChild>
                            <Button
                                type="button"
                                variant="secondary"
                                disabled={processing}
                            >
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            {isEditing ? 'Update' : 'Save'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
