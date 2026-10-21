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
import type { MasterBranch } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    branch?: MasterBranch | null;
};

export default function BranchViewDialog({
    open,
    onOpenChange,
    branch = null,
}: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="flex max-h-[90vh] flex-col overflow-hidden sm:max-w-xl">
                <DialogHeader className="shrink-0">
                    <DialogTitle>View branch</DialogTitle>
                    <DialogDescription>
                        Read-only details for this master branch.
                    </DialogDescription>
                </DialogHeader>

                {branch && (
                    <dl className="grid min-h-0 flex-1 content-start gap-4 overflow-y-auto pr-1 text-sm sm:grid-cols-2">
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Branch name
                            </dt>
                            <dd className="font-medium">
                                {branch.branch_name}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Branch code
                            </dt>
                            <dd className="font-medium">
                                {branch.branch_code}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Country</dt>
                            <dd className="font-medium">
                                {branch.country_name}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Region</dt>
                            <dd className="font-medium">
                                {branch.region_name}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Group</dt>
                            <dd className="font-medium">{branch.group_name}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Branch manager
                            </dt>
                            <dd className="font-medium">
                                {branch.manager_name}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Owner</dt>
                            <dd className="font-medium">
                                {branch.owner_name ?? '—'}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Geofencing
                            </dt>
                            <dd className="font-medium">
                                {branch.geofencing ? 'Yes' : 'No'}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Geofencing range
                            </dt>
                            <dd className="font-medium">
                                {branch.geofencing_range ?? '—'}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Branch country code
                            </dt>
                            <dd className="font-medium">
                                {branch.branch_country_code ?? '—'}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Branch FB country code
                            </dt>
                            <dd className="font-medium">
                                {branch.branch_fbcountry_code}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Man power</dt>
                            <dd className="font-medium">
                                {branch.man_power ?? '—'}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Retail man power
                            </dt>
                            <dd className="font-medium">
                                {branch.retail_man_power ?? '—'}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Status</dt>
                            <dd className="font-medium">
                                {branch.status_label}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Created by
                            </dt>
                            <dd className="font-medium">
                                {branch.created_by_name}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Last modified by
                            </dt>
                            <dd className="font-medium">
                                {branch.last_modified_by_name}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Created date
                            </dt>
                            <dd className="font-medium">{branch.created_on}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Updated date
                            </dt>
                            <dd className="font-medium">{branch.updated_on}</dd>
                        </div>
                    </dl>
                )}

                <DialogFooter className="shrink-0 border-t pt-4">
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
