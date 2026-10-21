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
import type { MasterTimezone } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    timezone?: MasterTimezone | null;
};

export default function TimezoneViewDialog({
    open,
    onOpenChange,
    timezone = null,
}: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>View timezone</DialogTitle>
                    <DialogDescription>
                        Read-only details for this master timezone.
                    </DialogDescription>
                </DialogHeader>

                {timezone && (
                    <dl className="grid gap-4 text-sm">
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Timezone name
                            </dt>
                            <dd className="font-medium">{timezone.name}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Display name
                            </dt>
                            <dd className="font-medium">
                                {timezone.display_name}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Code</dt>
                            <dd className="font-medium">{timezone.code}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                UTC offset
                            </dt>
                            <dd className="font-medium">
                                {timezone.utc_offset}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Status</dt>
                            <dd className="font-medium">
                                {timezone.status_label}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Created date
                            </dt>
                            <dd className="font-medium">
                                {timezone.created_on}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Updated date
                            </dt>
                            <dd className="font-medium">
                                {timezone.updated_on}
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
