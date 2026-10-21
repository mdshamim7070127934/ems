import { useForm } from '@inertiajs/react';
import { useEffect, type FormEvent } from 'react';
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
import { store, update } from '@/routes/master/modules';
import type { MasterModule, ModuleStatus } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    module?: MasterModule | null;
};

const emptyForm = {
    module_name: '',
    status: 'active' as ModuleStatus,
};

export default function ModuleFormDialog({
    open,
    onOpenChange,
    module = null,
}: Props) {
    const isEditing = module !== null;
    const { data, setData, post, put, processing, errors, reset, clearErrors } =
        useForm(emptyForm);

    useEffect(() => {
        if (!open) {
            return;
        }

        clearErrors();

        if (module) {
            setData({
                module_name: module.module_name,
                status: module.status,
            });

            return;
        }

        reset();
        setData(emptyForm);
    }, [module, open]);

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        };

        if (isEditing && module) {
            put(update.url(module.module_id), options);

            return;
        }

        post(store.url(), options);
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Edit module' : 'Add module'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEditing
                            ? 'Update the module name and status.'
                            : 'Create a new master module.'}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="module_name">Name</Label>
                        <Input
                            id="module_name"
                            name="module_name"
                            value={data.module_name}
                            onChange={(event) =>
                                setData('module_name', event.target.value)
                            }
                            required
                            autoFocus
                            maxLength={100}
                            disabled={processing}
                            placeholder="Module name"
                        />
                        <InputError message={errors.module_name} />
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
