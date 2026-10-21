import { useForm } from '@inertiajs/react';
import { useEffect, type FormEvent } from 'react';
import InputError from '@/components/input-error';
import MailboxBodyEditor from '@/components/mailbox/mailbox-body-editor';
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
import { store, update } from '@/routes/email-signatures';
import type { EmailSignature, ModuleStatus } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    signature?: EmailSignature | null;
};

const emptyForm = {
    signature_name: '',
    signature_html: '',
    status: 'active' as ModuleStatus,
};

export default function EmailSignatureFormDialog({
    open,
    onOpenChange,
    signature = null,
}: Props) {
    const isEditing = signature !== null;
    const { data, setData, post, patch, processing, errors, reset, clearErrors } =
        useForm(emptyForm);

    useEffect(() => {
        if (!open) {
            return;
        }

        clearErrors();

        if (signature) {
            setData({
                signature_name: signature.signature_name,
                signature_html: signature.signature_html ?? '',
                status: signature.status,
            });

            return;
        }

        reset();
        setData(emptyForm);
    }, [open, signature]);

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        if (processing || data.signature_name.trim() === '') {
            return;
        }

        const options = {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        };

        if (isEditing && signature) {
            patch(update.url(signature.id), options);

            return;
        }

        post(store.url(), options);
    }

    const canSubmit = !processing && data.signature_name.trim() !== '';

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
            <DialogContent className="flex max-h-[90vh] flex-col overflow-hidden sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Edit signature' : 'Add signature'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEditing
                            ? 'Update this signature name, status, and HTML body.'
                            : 'Create a signature that can be inserted into compose and reply.'}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="grid min-h-0 gap-4 overflow-y-auto">
                    <div className="grid gap-2">
                        <Label htmlFor="signature-name">Name</Label>
                        <Input
                            id="signature-name"
                            value={data.signature_name}
                            onChange={(event) =>
                                setData('signature_name', event.target.value)
                            }
                            required
                            maxLength={255}
                            disabled={processing}
                            autoFocus
                        />
                        <InputError message={errors.signature_name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="signature-status">Status</Label>
                        <Select
                            value={data.status}
                            onValueChange={(value) => {
                                if (value === 'active' || value === 'inactive') {
                                    setData('status', value);
                                }
                            }}
                            disabled={processing}
                        >
                            <SelectTrigger id="signature-status" className="w-full">
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="active">Active</SelectItem>
                                <SelectItem value="inactive">Inactive</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.status} />
                    </div>

                    <div className="grid gap-2">
                        <Label>Signature</Label>
                        {open && (
                            <div className="h-[240px] overflow-hidden rounded-md border">
                                <MailboxBodyEditor
                                    key={signature?.id ?? 'create'}
                                    className="h-full min-h-0"
                                    value={data.signature_html}
                                    onChange={(html) =>
                                        setData('signature_html', html)
                                    }
                                    placeholder="Signature HTML..."
                                    minHeightClass="min-h-0"
                                />
                            </div>
                        )}
                        <InputError message={errors.signature_html} />
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
                        <Button type="submit" disabled={!canSubmit}>
                            {processing && <Spinner />}
                            {isEditing ? 'Update' : 'Create'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
