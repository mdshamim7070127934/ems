import { useForm } from '@inertiajs/react';
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
import { Spinner } from '@/components/ui/spinner';
import { destroy } from '@/routes/mailbox/labels';
import type { MailboxLabel } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    label?: MailboxLabel | null;
};

export default function MailboxLabelDeleteDialog({
    open,
    onOpenChange,
    label = null,
}: Props) {
    const { delete: submitDelete, processing } = useForm({});

    function confirmDelete(): void {
        if (!label || processing) {
            return;
        }

        submitDelete(destroy.url(label.id), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    }

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
                    <DialogTitle>Delete label</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete this label? It will be
                        removed from all assigned emails.
                    </DialogDescription>
                </DialogHeader>

                {label && (
                    <p className="text-sm">
                        <span className="font-medium">{label.name}</span> will
                        be permanently removed.
                    </p>
                )}

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
                    <Button
                        type="button"
                        variant="destructive"
                        disabled={processing || !label}
                        onClick={confirmDelete}
                    >
                        {processing && <Spinner />}
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
