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
import type { MasterDropdownOptionValue } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    optionValue?: MasterDropdownOptionValue | null;
};

export default function OptionValueViewDialog({
    open,
    onOpenChange,
    optionValue = null,
}: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>View option value</DialogTitle>
                    <DialogDescription>
                        Read-only details for this dropdown option value.
                    </DialogDescription>
                </DialogHeader>

                {optionValue && (
                    <dl className="grid gap-4 text-sm sm:grid-cols-2">
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Option value
                            </dt>
                            <dd className="font-medium">
                                {optionValue.option_value}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Dropdown</dt>
                            <dd className="font-medium">
                                {optionValue.dropdown_name}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Module</dt>
                            <dd className="font-medium">
                                {optionValue.module_name}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Parent</dt>
                            <dd className="font-medium">
                                {optionValue.parent_value ?? 'None'}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Position</dt>
                            <dd className="font-medium">
                                {optionValue.position ?? '—'}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Master type
                            </dt>
                            <dd className="font-medium">
                                {optionValue.master_type ?? '—'}
                            </dd>
                        </div>
                        <div className="grid gap-1 sm:col-span-2">
                            <dt className="text-muted-foreground">
                                User access
                            </dt>
                            <dd className="font-medium">
                                {optionValue.user_access_id ?? '—'}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Status</dt>
                            <dd className="font-medium">
                                {optionValue.status_label}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Created date
                            </dt>
                            <dd className="font-medium">
                                {optionValue.created_on}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Updated date
                            </dt>
                            <dd className="font-medium">
                                {optionValue.updated_on}
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
