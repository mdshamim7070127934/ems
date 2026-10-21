import { useForm } from '@inertiajs/react';
import { useEffect, useMemo, type FormEvent } from 'react';
import InputError from '@/components/input-error';
import SearchableSelect from '@/components/searchable-select';
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
import { store, update } from '@/routes/master/groups';
import type { MasterGroup, ModuleStatus, UserOption } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    group?: MasterGroup | null;
    userOptions: UserOption[];
};

const emptyForm = {
    group_name: '',
    description: '',
    owner_id: '',
    status: 'active' as ModuleStatus,
};

export default function GroupFormDialog({
    open,
    onOpenChange,
    group = null,
    userOptions,
}: Props) {
    const isEditing = group !== null;
    const { data, setData, post, put, processing, errors, reset, clearErrors } =
        useForm(emptyForm);

    const selectableUsers = useMemo(() => {
        if (
            !group ||
            userOptions.some((user) => user.id === group.owner_id)
        ) {
            return userOptions;
        }

        return [
            { id: group.owner_id, name: group.owner_name },
            ...userOptions,
        ];
    }, [group, userOptions]);

    useEffect(() => {
        if (!open) {
            return;
        }

        clearErrors();

        if (group) {
            setData({
                group_name: group.group_name,
                description: group.description,
                owner_id: String(group.owner_id),
                status: group.status,
            });

            return;
        }

        reset();
        setData(emptyForm);
    }, [group, open]);

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        };

        if (isEditing && group) {
            put(update.url(group.id), options);

            return;
        }

        post(store.url(), options);
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="flex max-h-[90vh] flex-col overflow-visible sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Edit group' : 'Add group'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEditing
                            ? 'Update this master group.'
                            : 'Create a new master group.'}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="group_name">Group name</Label>
                        <Input
                            id="group_name"
                            name="group_name"
                            value={data.group_name}
                            onChange={(event) =>
                                setData('group_name', event.target.value)
                            }
                            required
                            autoFocus
                            maxLength={250}
                            disabled={processing}
                            placeholder="Group name"
                        />
                        <InputError message={errors.group_name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">Description</Label>
                        <textarea
                            id="description"
                            name="description"
                            value={data.description}
                            onChange={(event) =>
                                setData('description', event.target.value)
                            }
                            required
                            disabled={processing}
                            placeholder="Group description"
                            rows={3}
                            className={cn(
                                'border-input placeholder:text-muted-foreground flex min-h-20 w-full rounded-md border bg-transparent px-3 py-2 text-base shadow-xs outline-none md:text-sm',
                                'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                                'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50',
                            )}
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="relative z-20 grid gap-2">
                        <Label htmlFor="owner_id">Owner</Label>
                        <SearchableSelect
                            id="owner_id"
                            value={data.owner_id}
                            disabled={processing}
                            placeholder="Select owner"
                            searchPlaceholder="Type to search owner..."
                            emptyText="No users found."
                            options={selectableUsers.map((user) => ({
                                value: String(user.id),
                                label: user.name,
                            }))}
                            onValueChange={(value) =>
                                setData('owner_id', value)
                            }
                        />
                        <InputError message={errors.owner_id} />
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
