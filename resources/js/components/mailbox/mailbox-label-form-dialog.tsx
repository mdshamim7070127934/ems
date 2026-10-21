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
import { Spinner } from '@/components/ui/spinner';
import { store, update } from '@/routes/mailbox/labels';
import type { MailboxLabel } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    accountId: number;
    label?: MailboxLabel | null;
};

const labelColors = [
    { value: '#1967d2', label: 'Blue' },
    { value: '#188038', label: 'Green' },
    { value: '#d93025', label: 'Red' },
    { value: '#f9ab00', label: 'Yellow' },
    { value: '#5f6368', label: 'Gray' },
];

const emptyForm = {
    account_id: 0,
    name: '',
    color: '',
};

export default function MailboxLabelFormDialog({
    open,
    onOpenChange,
    accountId,
    label = null,
}: Props) {
    const isEditing = label !== null;
    const { data, setData, post, patch, processing, errors, reset, clearErrors, transform } =
        useForm(emptyForm);

    useEffect(() => {
        if (!open) {
            return;
        }

        clearErrors();

        if (label) {
            setData({
                account_id: accountId,
                name: label.name,
                color: label.color ?? '',
            });

            return;
        }

        reset();
        setData({
            account_id: accountId,
            name: '',
            color: '',
        });
    }, [accountId, label, open]);

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        if (processing || data.name.trim() === '') {
            return;
        }

        transform((form) => ({
            ...form,
            name: form.name.trim(),
            color: form.color === '' ? null : form.color,
        }));

        const options = {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        };

        if (isEditing && label) {
            patch(update.url(label.id), options);

            return;
        }

        post(store.url(), options);
    }

    const canSubmit = !processing && data.name.trim() !== '';

    return (
        <Dialog
            open={open}
            onOpenChange={(nextOpen) => {
                if (processing && !nextOpen) {
                    return;
                }

                onOpenChange(nextOpen);
            }}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Edit label' : 'Create label'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEditing
                            ? 'Update this label name and color.'
                            : 'Create a label to organize emails in this mailbox.'}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="space-y-3">
                    <div className="space-y-2">
                        <Label htmlFor="mailbox-label-name">Label name</Label>
                        <Input
                            id="mailbox-label-name"
                            value={data.name}
                            onChange={(event) => setData('name', event.target.value)}
                            placeholder="e.g. Work, Important"
                            required
                            maxLength={100}
                            autoFocus
                            disabled={processing}
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="mailbox-label-color">Color (optional)</Label>
                        <select
                            id="mailbox-label-color"
                            className="border-input bg-background h-9 w-full rounded-md border px-3 text-sm disabled:cursor-not-allowed disabled:opacity-50"
                            value={data.color}
                            onChange={(event) => setData('color', event.target.value)}
                            disabled={processing}
                        >
                            <option value="">None</option>
                            {label?.color &&
                                !labelColors.some((color) => color.value === label.color) && (
                                    <option value={label.color}>Current</option>
                                )}
                            {labelColors.map((color) => (
                                <option key={color.value} value={color.value}>
                                    {color.label}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.color} />
                    </div>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button
                                type="button"
                                variant="outline"
                                disabled={processing}
                            >
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={!canSubmit}>
                            {processing && <Spinner />}
                            {isEditing ? 'Save' : 'Create'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
