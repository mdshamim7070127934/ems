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
import { destroy } from '@/routes/master/modules';
import type { MasterModule } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    module?: MasterModule | null;
};

export default function ModuleDeleteDialog({
    open,
    onOpenChange,
    module = null,
}: Props) {
    const { delete: submitDelete, processing } = useForm({});

    function confirmDelete(): void {
        if (!module) {
            return;
        }

        submitDelete(destroy.url(module.module_id), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete module</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete this record?
                    </DialogDescription>
                </DialogHeader>

                {module && (
                    <p className="text-sm">
                        <span className="font-medium">
                            {module.module_name}
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
                        disabled={processing || !module}
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
