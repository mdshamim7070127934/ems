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
import { destroy } from '@/routes/master/roles';
import type { MasterRole } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    role?: MasterRole | null;
};

export default function RoleDeleteDialog({
    open,
    onOpenChange,
    role = null,
}: Props) {
    const { delete: submitDelete, processing } = useForm({});

    function confirmDelete(): void {
        if (!role) {
            return;
        }

        submitDelete(destroy.url(role.id), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete role</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete this record?
                    </DialogDescription>
                </DialogHeader>

                {role && (
                    <p className="text-sm">
                        <span className="font-medium">{role.display_name}</span>{' '}
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
                        disabled={processing || !role}
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
