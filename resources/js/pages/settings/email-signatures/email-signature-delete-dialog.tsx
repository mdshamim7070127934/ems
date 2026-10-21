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
import { destroy } from '@/routes/email-signatures';
import type { EmailSignature } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    signature?: EmailSignature | null;
};

export default function EmailSignatureDeleteDialog({
    open,
    onOpenChange,
    signature = null,
}: Props) {
    const { delete: submitDelete, processing } = useForm({});

    function confirmDelete(): void {
        if (!signature || processing) {
            return;
        }

        submitDelete(destroy.url(signature.id), {
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
                    <DialogTitle>Delete signature</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete this signature? This
                        cannot be undone.
                    </DialogDescription>
                </DialogHeader>

                {signature && (
                    <p className="text-sm">
                        <span className="font-medium">
                            {signature.signature_name}
                        </span>{' '}
                        will be permanently removed.
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
                        disabled={processing || !signature}
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
