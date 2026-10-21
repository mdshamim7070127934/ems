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
import { destroy } from '@/routes/master/email-accounts';
import type { MasterEmailAccount } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    emailAccount?: MasterEmailAccount | null;
};

export default function EmailAccountDeleteDialog({
    open,
    onOpenChange,
    emailAccount = null,
}: Props) {
    const { delete: submitDelete, processing } = useForm({});

    function confirmDelete(): void {
        if (!emailAccount) {
            return;
        }

        submitDelete(destroy.url(emailAccount.id), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete email account</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete this record?
                    </DialogDescription>
                </DialogHeader>

                {emailAccount && (
                    <p className="text-sm">
                        <span className="font-medium">{emailAccount.name}</span>{' '}
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
                        disabled={processing || !emailAccount}
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
