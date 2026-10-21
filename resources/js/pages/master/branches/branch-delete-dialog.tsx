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
import { destroy } from '@/routes/master/branches';
import type { MasterBranch } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    branch?: MasterBranch | null;
};

export default function BranchDeleteDialog({
    open,
    onOpenChange,
    branch = null,
}: Props) {
    const { delete: submitDelete, processing } = useForm({});

    function confirmDelete(): void {
        if (!branch) {
            return;
        }

        submitDelete(destroy.url(branch.id), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete branch</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete this record?
                    </DialogDescription>
                </DialogHeader>

                {branch && (
                    <p className="text-sm">
                        <span className="font-medium">
                            {branch.branch_name}
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
                        disabled={processing || !branch}
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
