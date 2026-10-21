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
import type { MasterMenu } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    menu?: MasterMenu | null;
};

export default function MenuViewDialog({
    open,
    onOpenChange,
    menu = null,
}: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>View menu</DialogTitle>
                    <DialogDescription>
                        Read-only details for this master menu item.
                    </DialogDescription>
                </DialogHeader>

                {menu && (
                    <dl className="grid gap-4 text-sm sm:grid-cols-2">
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Menu name</dt>
                            <dd className="font-medium">{menu.menu_name}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Parent</dt>
                            <dd className="font-medium">
                                {menu.parent_name ?? 'Main menu'}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Route</dt>
                            <dd className="font-medium">{menu.menu_route}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Order</dt>
                            <dd className="font-medium">{menu.menu_order}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Screen name
                            </dt>
                            <dd className="font-medium">
                                {menu.screen_name ?? '—'}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Status</dt>
                            <dd className="font-medium">{menu.status_label}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Web icon</dt>
                            <dd className="font-medium">
                                {menu.web_icon ?? '—'}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Mobile icon
                            </dt>
                            <dd className="font-medium">
                                {menu.mobile_icon ?? '—'}
                            </dd>
                        </div>
                        <div className="grid gap-1 sm:col-span-2">
                            <dt className="text-muted-foreground">
                                Description
                            </dt>
                            <dd className="font-medium">
                                {menu.column_description ?? '—'}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Created date
                            </dt>
                            <dd className="font-medium">{menu.created_at}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Updated date
                            </dt>
                            <dd className="font-medium">{menu.updated_at}</dd>
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
