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
import type { MasterCity } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    city?: MasterCity | null;
};

export default function CityViewDialog({
    open,
    onOpenChange,
    city = null,
}: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>View city</DialogTitle>
                    <DialogDescription>
                        Read-only details for this master city.
                    </DialogDescription>
                </DialogHeader>

                {city && (
                    <dl className="grid gap-4 text-sm">
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Name</dt>
                            <dd className="font-medium">{city.name}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Country</dt>
                            <dd className="font-medium">{city.country_name}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">State</dt>
                            <dd className="font-medium">{city.state_name}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Status</dt>
                            <dd className="font-medium">{city.status_label}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Created date
                            </dt>
                            <dd className="font-medium">{city.created_on}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Updated date
                            </dt>
                            <dd className="font-medium">{city.updated_on}</dd>
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
