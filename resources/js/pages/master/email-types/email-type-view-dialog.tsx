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
import type { MasterEmailType } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    emailType?: MasterEmailType | null;
};

export default function EmailTypeViewDialog({
    open,
    onOpenChange,
    emailType = null,
}: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>View email type</DialogTitle>
                    <DialogDescription>
                        Read-only details for this email type.
                    </DialogDescription>
                </DialogHeader>

                {emailType && (
                    <dl className="grid gap-4 text-sm">
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Code</dt>
                            <dd className="font-mono text-xs">
                                {emailType.code}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Name</dt>
                            <dd className="font-medium">{emailType.name}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Description
                            </dt>
                            <dd className="font-medium">
                                {emailType.description || '—'}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Sort order
                            </dt>
                            <dd className="font-medium">
                                {emailType.sort_order}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Status</dt>
                            <dd className="font-medium">
                                {emailType.status_label}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Created date
                            </dt>
                            <dd className="font-medium">
                                {emailType.created_at}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Updated date
                            </dt>
                            <dd className="font-medium">
                                {emailType.updated_at}
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
