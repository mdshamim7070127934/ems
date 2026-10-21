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
import { cn } from '@/lib/utils';
import { store, update } from '@/routes/master/email-types';
import type { MasterEmailType, ModuleStatus } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    emailType?: MasterEmailType | null;
};

const emptyForm = {
    code: '',
    name: '',
    description: '',
    sort_order: 0,
    status: 'active' as ModuleStatus,
};

export default function EmailTypeFormDialog({
    open,
    onOpenChange,
    emailType = null,
}: Props) {
    const isEditing = emailType !== null;
    const { data, setData, post, put, processing, errors, reset, clearErrors } =
        useForm(emptyForm);

    useEffect(() => {
        if (!open) {
            return;
        }

        clearErrors();

        if (emailType) {
            setData({
                code: emailType.code,
                name: emailType.name,
                description: emailType.description ?? '',
                sort_order: emailType.sort_order,
                status: emailType.status,
            });

            return;
        }

        reset();
        setData(emptyForm);
    }, [open, emailType]);

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        };

        if (isEditing && emailType) {
            put(update.url(emailType.id), options);

            return;
        }

        post(store.url(), options);
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Edit email type' : 'Add email type'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEditing
                            ? 'Update this email type.'
                            : 'Create a new email type used by email accounts.'}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="code">Code</Label>
                        <Input
                            id="code"
                            value={data.code}
                            onChange={(event) =>
                                setData(
                                    'code',
                                    event.target.value.toUpperCase(),
                                )
                            }
                            required
                            autoFocus={!isEditing}
                            maxLength={50}
                            disabled={processing}
                            placeholder="e.g. REGISTRATION"
                            pattern="[A-Z][A-Z0-9_]*"
                            title="Uppercase letters, numbers, and underscores only. Must start with a letter."
                        />
                        <InputError message={errors.code} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(event) =>
                                setData('name', event.target.value)
                            }
                            required
                            maxLength={100}
                            disabled={processing}
                            placeholder="e.g. Registration"
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">Description</Label>
                        <textarea
                            id="description"
                            value={data.description}
                            onChange={(event) =>
                                setData('description', event.target.value)
                            }
                            disabled={processing}
                            placeholder="Optional description"
                            rows={3}
                            className={cn(
                                'border-input placeholder:text-muted-foreground flex min-h-20 w-full rounded-md border bg-transparent px-3 py-2 text-base shadow-xs outline-none md:text-sm',
                                'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                                'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50',
                            )}
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="sort_order">Sort order</Label>
                        <Input
                            id="sort_order"
                            type="number"
                            min={0}
                            value={data.sort_order}
                            onChange={(event) =>
                                setData(
                                    'sort_order',
                                    Number(event.target.value),
                                )
                            }
                            required
                            disabled={processing}
                        />
                        <InputError message={errors.sort_order} />
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
