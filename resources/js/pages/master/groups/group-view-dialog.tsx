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
import type { MasterGroup } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    group?: MasterGroup | null;
};

export default function GroupViewDialog({
    open,
    onOpenChange,
    group = null,
}: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>View group</DialogTitle>
                    <DialogDescription>
                        Read-only details for this master group.
                    </DialogDescription>
                </DialogHeader>

                {group && (
                    <dl className="grid gap-4 text-sm">
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Group name
                            </dt>
                            <dd className="font-medium">{group.group_name}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Description
                            </dt>
                            <dd className="font-medium">{group.description}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Owner</dt>
                            <dd className="font-medium">{group.owner_name}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Status</dt>
                            <dd className="font-medium">
                                {group.status_label}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Created date
                            </dt>
                            <dd className="font-medium">{group.created_at}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Updated date
                            </dt>
                            <dd className="font-medium">{group.updated_at}</dd>
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
