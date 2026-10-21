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
import type { MasterCountry } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    country?: MasterCountry | null;
};

export default function CountryViewDialog({
    open,
    onOpenChange,
    country = null,
}: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>View country</DialogTitle>
                    <DialogDescription>
                        Read-only details for this master country.
                    </DialogDescription>
                </DialogHeader>

                {country && (
                    <dl className="grid gap-4 text-sm sm:grid-cols-2">
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Name</dt>
                            <dd className="font-medium">{country.name}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Sort name</dt>
                            <dd className="font-medium">{country.sortname}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Currency</dt>
                            <dd className="font-medium">{country.currency}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Phone code
                            </dt>
                            <dd className="font-medium">{country.phonecode}</dd>
                        </div>
                        <div className="grid gap-1 sm:col-span-2">
                            <dt className="text-muted-foreground">Time zone</dt>
                            <dd className="font-medium">
                                {country.timezone_name ?? '—'}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Status</dt>
                            <dd className="font-medium">
                                {country.status_label}
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
