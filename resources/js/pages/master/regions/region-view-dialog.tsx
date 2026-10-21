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
import type { MasterRegion } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    region?: MasterRegion | null;
};

export default function RegionViewDialog({
    open,
    onOpenChange,
    region = null,
}: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>View region</DialogTitle>
                    <DialogDescription>
                        Read-only details for this master region.
                    </DialogDescription>
                </DialogHeader>

                {region && (
                    <dl className="grid gap-4 text-sm">
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Region name
                            </dt>
                            <dd className="font-medium">
                                {region.region_name}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Country</dt>
                            <dd className="font-medium">
                                {region.country_name}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Owner</dt>
                            <dd className="font-medium">{region.owner_name}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Status</dt>
                            <dd className="font-medium">
                                {region.status_label}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Created date
                            </dt>
                            <dd className="font-medium">{region.created_at}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Updated date
                            </dt>
                            <dd className="font-medium">{region.updated_at}</dd>
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
