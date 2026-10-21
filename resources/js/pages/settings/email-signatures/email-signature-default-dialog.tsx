import { router } from '@inertiajs/react';
import { useState } from 'react';
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
import { makeDefault } from '@/routes/email-signatures';
import type { EmailSignature } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    signature?: EmailSignature | null;
};

export default function EmailSignatureDefaultDialog({
    open,
    onOpenChange,
    signature = null,
}: Props) {
    const [processing, setProcessing] = useState(false);
    const unsetting = signature?.is_default === true;
    const canToggle =
        signature !== null && (unsetting || signature.status === 'active');

    function confirmDefault(): void {
        if (!canToggle || !signature || processing) {
            return;
        }

        router.post(
            makeDefault.url(signature.id),
            { unset: unsetting },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onSuccess: () => onOpenChange(false),
            },
        );
    }

    return (
        <Dialog
            open={open}
            onOpenChange={(nextOpen) => {
                if (processing && !nextOpen) {
                    return;
                }

                onOpenChange(nextOpen);
            }}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {unsetting
                            ? 'Remove default signature'
                            : 'Set default signature'}
                    </DialogTitle>
                    <DialogDescription>
                        {unsetting
                            ? 'This signature will no longer be inserted automatically when you compose a new message.'
                            : 'This signature will be inserted automatically when you compose a new message.'}
                    </DialogDescription>
                </DialogHeader>

                {signature && (
                    <p className="text-sm">
                        {unsetting ? 'Unset' : 'Use'}{' '}
                        <span className="font-medium">
                            {signature.signature_name}
                        </span>{' '}
                        as your default signature?
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
                        disabled={processing || !canToggle}
                        onClick={confirmDefault}
                    >
                        {processing && <Spinner />}
                        {unsetting ? 'Remove default' : 'Set as default'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
