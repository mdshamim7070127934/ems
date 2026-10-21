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
import { destroy } from '@/routes/master/editor-settings';
import type { MasterEditorSetting } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    editorSetting?: MasterEditorSetting | null;
};

export default function EditorSettingDeleteDialog({
    open,
    onOpenChange,
    editorSetting = null,
}: Props) {
    const { delete: submitDelete, processing } = useForm({});
    const canDelete = editorSetting !== null && !editorSetting.is_default;

    function confirmDelete(): void {
        if (!canDelete || !editorSetting) {
            return;
        }

        submitDelete(destroy.url(editorSetting.id), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete editor setting</DialogTitle>
                    <DialogDescription>
                        {editorSetting?.is_default
                            ? 'The default editor cannot be deleted. Set another editor as default first.'
                            : 'Are you sure you want to delete this editor? Compose will not be able to use it until it is seeded again.'}
                    </DialogDescription>
                </DialogHeader>

                {editorSetting && (
                    <p className="text-sm">
                        <span className="font-medium">{editorSetting.name}</span>{' '}
                        will be permanently removed.
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
                        disabled={processing || !canDelete}
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
