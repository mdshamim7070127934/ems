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
import type { MasterDropdown } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    dropdown?: MasterDropdown | null;
};

export default function DropdownViewDialog({
    open,
    onOpenChange,
    dropdown = null,
}: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>View dropdown</DialogTitle>
                    <DialogDescription>
                        Read-only details for this master dropdown.
                    </DialogDescription>
                </DialogHeader>

                {dropdown && (
                    <dl className="grid gap-4 text-sm">
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Name</dt>
                            <dd className="font-medium">{dropdown.name}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Module</dt>
                            <dd className="font-medium">
                                {dropdown.module_name}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Status</dt>
                            <dd className="font-medium">
                                {dropdown.status_label}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Created date
                            </dt>
                            <dd className="font-medium">
                                {dropdown.created_on}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Updated date
                            </dt>
                            <dd className="font-medium">
                                {dropdown.updated_on}
                            </dd>
                        </div>
                    </dl>
                )}

                <DialogFooter>
                    <DialogClose asChild>
                        <Button type="button" variant="secondary">
                            Close
                        </Button>
                    </DialogClose>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
