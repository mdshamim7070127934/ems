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
import { destroy } from '@/routes/mailbox/drafts';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    draftId?: number | null;
    subject?: string | null;
    preserveState?: boolean;
    onDeleted?: () => void;
};

export default function MailboxDraftDeleteDialog({
    open,
    onOpenChange,
    draftId = null,
    subject = null,
    preserveState = true,
    onDeleted,
}: Props) {
    const { delete: submitDelete, processing } = useForm({});
    const hasSavedDraft = draftId !== null && draftId !== undefined;
    const label = subject?.trim() ? subject : '(no subject)';

    function confirmDelete(): void {
        if (processing) {
            return;
        }

        if (!hasSavedDraft) {
            onOpenChange(false);
            onDeleted?.();

            return;
        }

        submitDelete(destroy.url(draftId), {
            preserveScroll: true,
            preserveState,
            onSuccess: () => {
                onOpenChange(false);
                onDeleted?.();
            },
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
            <DialogContent className="z-[80]">
                <DialogHeader>
                    <DialogTitle>
                        {hasSavedDraft ? 'Delete draft' : 'Discard message'}
                    </DialogTitle>
                    <DialogDescription>
                        {hasSavedDraft
                            ? 'Are you sure you want to delete this draft? This cannot be undone.'
                            : 'Are you sure you want to discard this message?'}
                    </DialogDescription>
                </DialogHeader>

                {hasSavedDraft && (
                    <p className="text-sm">
                        <span className="font-medium">{label}</span> will be
                        permanently removed.
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
                        disabled={processing}
                        onClick={confirmDelete}
                    >
                        {processing && <Spinner />}
                        {hasSavedDraft ? 'Delete' : 'Discard'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
