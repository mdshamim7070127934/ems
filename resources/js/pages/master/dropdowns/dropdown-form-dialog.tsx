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
import { store, update } from '@/routes/master/dropdowns';
import type {
    DropdownModuleOption,
    MasterDropdown,
    ModuleStatus,
} from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    dropdown?: MasterDropdown | null;
    moduleOptions: DropdownModuleOption[];
};

const emptyForm = {
    module_id: '',
    name: '',
    status: 'active' as ModuleStatus,
};

export default function DropdownFormDialog({
    open,
    onOpenChange,
    dropdown = null,
    moduleOptions,
}: Props) {
    const isEditing = dropdown !== null;
    const { data, setData, post, put, processing, errors, reset, clearErrors } =
        useForm(emptyForm);

    const selectableModules = useMemo(() => {
        if (
            !dropdown ||
            moduleOptions.some(
                (module) => module.module_id === dropdown.module_id,
            )
        ) {
            return moduleOptions;
        }

        return [
            {
                module_id: dropdown.module_id,
                module_name: dropdown.module_name,
            },
            ...moduleOptions,
        ];
    }, [dropdown, moduleOptions]);

    useEffect(() => {
        if (!open) {
            return;
        }

        clearErrors();

        if (dropdown) {
            setData({
                module_id: String(dropdown.module_id),
                name: dropdown.name,
                status: dropdown.status,
            });

            return;
        }

        reset();
        setData(emptyForm);
    }, [dropdown, open]);

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        };

        if (isEditing && dropdown) {
            put(update.url(dropdown.option_type_id), options);

            return;
        }

        post(store.url(), options);
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Edit dropdown' : 'Add dropdown'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEditing
                            ? 'Update the dropdown name, module, and status.'
                            : 'Create a new master dropdown.'}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="module_id">Module</Label>
                        <Select
                            value={data.module_id}
                            onValueChange={(value) =>
                                setData('module_id', value)
                            }
                            disabled={processing}
                        >
                            <SelectTrigger id="module_id" className="w-full">
                                <SelectValue placeholder="Select module" />
                            </SelectTrigger>
                            <SelectContent>
                                {selectableModules.map((module) => (
                                    <SelectItem
                                        key={module.module_id}
                                        value={String(module.module_id)}
                                    >
                                        {module.module_name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.module_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            value={data.name}
                            onChange={(event) =>
                                setData('name', event.target.value)
                            }
                            required
                            autoFocus
                            maxLength={100}
                            disabled={processing}
                            placeholder="Dropdown name"
                        />
                        <InputError message={errors.name} />
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
