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
import { destroy } from '@/routes/master/timezones';
import type { MasterTimezone } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    timezone?: MasterTimezone | null;
};

export default function TimezoneDeleteDialog({
    open,
    onOpenChange,
    timezone = null,
}: Props) {
    const { delete: submitDelete, processing } = useForm({});

    function confirmDelete(): void {
        if (!timezone) {
            return;
        }

        submitDelete(destroy.url(timezone.id), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete timezone</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete this timezone?
                    </DialogDescription>
                </DialogHeader>

                {timezone && (
                    <p className="text-sm">
                        <span className="font-medium">{timezone.name}</span>{' '}
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
                        disabled={processing || !timezone}
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
