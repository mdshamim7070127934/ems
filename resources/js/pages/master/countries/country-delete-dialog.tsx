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
import { destroy } from '@/routes/master/countries';
import type { MasterCountry } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    country?: MasterCountry | null;
};

export default function CountryDeleteDialog({
    open,
    onOpenChange,
    country = null,
}: Props) {
    const { delete: submitDelete, processing } = useForm({});

    function confirmDelete(): void {
        if (!country) {
            return;
        }

        submitDelete(destroy.url(country.id), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete country</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete this record?
                    </DialogDescription>
                </DialogHeader>

                {country && (
                    <p className="text-sm">
                        <span className="font-medium">{country.name}</span> will
                        be permanently removed.
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
                        disabled={processing || !country}
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
