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
import type { MasterState } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    state?: MasterState | null;
};

export default function StateViewDialog({
    open,
    onOpenChange,
    state = null,
}: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>View state</DialogTitle>
                    <DialogDescription>
                        Read-only details for this master state.
                    </DialogDescription>
                </DialogHeader>

                {state && (
                    <dl className="grid gap-4 text-sm">
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Name</dt>
                            <dd className="font-medium">{state.name}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Country</dt>
                            <dd className="font-medium">
                                {state.country_name}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Status</dt>
                            <dd className="font-medium">
                                {state.status_label}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Created date
                            </dt>
                            <dd className="font-medium">{state.created_on}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Updated date
                            </dt>
                            <dd className="font-medium">{state.updated_on}</dd>
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
