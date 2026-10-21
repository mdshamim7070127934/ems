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
import type { MasterEditorSetting } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    editorSetting?: MasterEditorSetting | null;
};

export default function EditorSettingViewDialog({
    open,
    onOpenChange,
    editorSetting = null,
}: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>View editor setting</DialogTitle>
                    <DialogDescription>
                        Read-only details for this mailbox editor.
                    </DialogDescription>
                </DialogHeader>

                {editorSetting && (
                    <dl className="grid gap-4 text-sm">
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Code</dt>
                            <dd className="font-mono text-xs">
                                {editorSetting.code}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Name</dt>
                            <dd className="font-medium">{editorSetting.name}</dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Description
                            </dt>
                            <dd className="font-medium">
                                {editorSetting.description || '—'}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Default</dt>
                            <dd className="font-medium">
                                {editorSetting.is_default ? 'Yes' : 'No'}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Sort order
                            </dt>
                            <dd className="font-medium">
                                {editorSetting.sort_order}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">Status</dt>
                            <dd className="font-medium">
                                {editorSetting.status_label}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Created date
                            </dt>
                            <dd className="font-medium">
                                {editorSetting.created_at}
                            </dd>
                        </div>
                        <div className="grid gap-1">
                            <dt className="text-muted-foreground">
                                Updated date
                            </dt>
                            <dd className="font-medium">
                                {editorSetting.updated_at}
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
