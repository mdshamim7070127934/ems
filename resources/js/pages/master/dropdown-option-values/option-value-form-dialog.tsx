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
import { store, update } from '@/routes/master/dropdown-option-values';
import type {
    DropdownModuleOption,
    DropdownParentOption,
    DropdownTypeOption,
    MasterDropdownOptionValue,
    ModuleStatus,
} from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    optionValue?: MasterDropdownOptionValue | null;
    moduleOptions: DropdownModuleOption[];
    dropdownOptions: DropdownTypeOption[];
    parentOptions: DropdownParentOption[];
};

const emptyForm = {
    module_id: '',
    option_type_id: '',
    parent_id: '0',
    option_value: '',
    position: '',
    user_access_id: '',
    master_type: '',
    status: 'active' as ModuleStatus,
};

export default function OptionValueFormDialog({
    open,
    onOpenChange,
    optionValue = null,
    moduleOptions,
    dropdownOptions,
    parentOptions,
}: Props) {
    const isEditing = optionValue !== null;
    const { data, setData, post, put, processing, errors, reset, clearErrors } =
        useForm(emptyForm);

    const selectableModules = useMemo(() => {
        if (
            !optionValue ||
            moduleOptions.some(
                (module) => module.module_id === optionValue.module_id,
            )
        ) {
            return moduleOptions;
        }

        return [
            {
                module_id: optionValue.module_id,
                module_name: optionValue.module_name,
            },
            ...moduleOptions,
        ];
    }, [moduleOptions, optionValue]);

    const selectableDropdowns = useMemo(() => {
        const filtered = dropdownOptions.filter(
            (dropdown) => String(dropdown.module_id) === data.module_id,
        );

        if (
            !optionValue ||
            String(optionValue.module_id) !== data.module_id ||
            filtered.some(
                (dropdown) =>
                    dropdown.option_type_id === optionValue.option_type_id,
            )
        ) {
            return filtered;
        }

        return [
            {
                option_type_id: optionValue.option_type_id,
                module_id: optionValue.module_id,
                name: optionValue.dropdown_name,
            },
            ...filtered,
        ];
    }, [data.module_id, dropdownOptions, optionValue]);

    const selectableParents = useMemo(() => {
        return parentOptions.filter((parent) => {
            if (String(parent.option_type_id) !== data.option_type_id) {
                return false;
            }

            return !optionValue || parent.id !== optionValue.id;
        });
    }, [data.option_type_id, optionValue, parentOptions]);

    useEffect(() => {
        if (!open) {
            return;
        }

        clearErrors();

        if (optionValue) {
            setData({
                module_id: String(optionValue.module_id),
                option_type_id: String(optionValue.option_type_id),
                parent_id: String(optionValue.parent_id),
                option_value: optionValue.option_value,
                position:
                    optionValue.position === null
                        ? ''
                        : String(optionValue.position),
                user_access_id: optionValue.user_access_id ?? '',
                master_type: optionValue.master_type ?? '',
                status: optionValue.status,
            });

            return;
        }

        reset();
        setData(emptyForm);
    }, [open, optionValue]);

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        };

        if (isEditing && optionValue) {
            put(update.url(optionValue.id), options);

            return;
        }

        post(store.url(), options);
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Edit option value' : 'Add option value'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEditing
                            ? 'Update this dropdown option value.'
                            : 'Create a new dropdown option value.'}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="module_id">Module</Label>
                        <Select
                            value={data.module_id}
                            onValueChange={(value) =>
                                setData({
                                    ...data,
                                    module_id: value,
                                    option_type_id: '',
                                    parent_id: '0',
                                })
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
                        <Label htmlFor="option_type_id">Dropdown</Label>
                        <Select
                            value={data.option_type_id}
                            onValueChange={(value) =>
                                setData({
                                    ...data,
                                    option_type_id: value,
                                    parent_id: '0',
                                })
                            }
                            disabled={processing || data.module_id === ''}
                        >
                            <SelectTrigger
                                id="option_type_id"
                                className="w-full"
                            >
                                <SelectValue placeholder="Select dropdown" />
                            </SelectTrigger>
                            <SelectContent>
                                {selectableDropdowns.map((dropdown) => (
                                    <SelectItem
                                        key={dropdown.option_type_id}
                                        value={String(dropdown.option_type_id)}
                                    >
                                        {dropdown.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.option_type_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="parent_id">Parent</Label>
                        <Select
                            value={data.parent_id}
                            onValueChange={(value) =>
                                setData('parent_id', value)
                            }
                            disabled={processing || data.option_type_id === ''}
                        >
                            <SelectTrigger id="parent_id" className="w-full">
                                <SelectValue placeholder="No parent" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="0">None</SelectItem>
                                {selectableParents.map((parent) => (
                                    <SelectItem
                                        key={parent.id}
                                        value={String(parent.id)}
                                    >
                                        {parent.option_value}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.parent_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="option_value">Option value</Label>
                        <Input
                            id="option_value"
                            name="option_value"
                            value={data.option_value}
                            onChange={(event) =>
                                setData('option_value', event.target.value)
                            }
                            required
                            maxLength={100}
                            disabled={processing}
                            placeholder="Option value"
                        />
                        <InputError message={errors.option_value} />
                    </div>

                    <div className="grid gap-2 sm:grid-cols-2 sm:gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="position">Position</Label>
                            <Input
                                id="position"
                                name="position"
                                type="number"
                                min={0}
                                value={data.position}
                                onChange={(event) =>
                                    setData('position', event.target.value)
                                }
                                disabled={processing}
                                placeholder="Optional"
                            />
                            <InputError message={errors.position} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="master_type">Master type</Label>
                            <Input
                                id="master_type"
                                name="master_type"
                                value={data.master_type}
                                onChange={(event) =>
                                    setData('master_type', event.target.value)
                                }
                                maxLength={255}
                                disabled={processing}
                                placeholder="Service / Product"
                            />
                            <InputError message={errors.master_type} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="user_access_id">User access</Label>
                        <Input
                            id="user_access_id"
                            name="user_access_id"
                            value={data.user_access_id}
                            onChange={(event) =>
                                setData('user_access_id', event.target.value)
                            }
                            disabled={processing}
                            placeholder="Optional"
                        />
                        <InputError message={errors.user_access_id} />
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
