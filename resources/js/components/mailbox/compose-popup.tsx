import { Maximize2, Minus, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import ComposeForm from '@/components/mailbox/compose-form';
import { cn } from '@/lib/utils';
import type { MailboxAccount, MailboxDraft } from '@/types';

type PopupState = 'open' | 'minimized' | 'maximized';

type Props = {
    account: MailboxAccount;
    draft: MailboxDraft;
    title?: string;
    onClose: () => void;
};

export default function ComposePopup({
    account,
    draft,
    title = 'New message',
    onClose,
}: Props) {
    const [state, setState] = useState<PopupState>('open');
    const heading = useMemo(
        () => (draft.subject && draft.subject !== '' ? draft.subject : title),
        [draft.subject, title],
    );

    if (state === 'minimized') {
        return (
            <div className="fixed right-4 bottom-0 z-50 flex w-[380px] max-w-[90vw] items-center gap-2 rounded-t-lg border bg-white px-3 py-2 shadow-lg dark:bg-zinc-900">
                <button
                    type="button"
                    className="min-w-0 flex-1 truncate text-left text-sm font-medium"
                    onClick={() => setState('open')}
                >
                    {heading}
                </button>
                <button
                    type="button"
                    className="hover:bg-accent rounded p-1"
                    onClick={() => setState('open')}
                    title="Expand"
                >
                    <Maximize2 className="size-4" />
                </button>
                <button
                    type="button"
                    className="hover:bg-accent rounded p-1"
                    onClick={onClose}
                    title="Close"
                >
                    <X className="size-4" />
                </button>
            </div>
        );
    }

    return (
        <div
            className={cn(
                'fixed z-50 flex flex-col overflow-hidden rounded-t-lg border bg-white shadow-2xl dark:bg-zinc-900',
                state === 'maximized'
                    ? 'inset-4 h-auto max-h-none w-auto'
                    : 'right-4 bottom-0 h-[82vh] w-[50vw] min-w-[360px] max-w-[920px]',
            )}
        >
            <div className="flex items-center justify-between border-b bg-[#f0f0f0] px-3 py-2 dark:bg-zinc-800">
                <p className="truncate text-sm font-semibold">{heading}</p>
                <div className="flex items-center gap-0.5">
                    <button
                        type="button"
                        className="hover:bg-black/10 rounded p-1"
                        onClick={() => setState('minimized')}
                        title="Minimize"
                    >
                        <Minus className="size-4" />
                    </button>
                    <button
                        type="button"
                        className="hover:bg-black/10 rounded p-1"
                        onClick={() =>
                            setState((current) =>
                                current === 'maximized' ? 'open' : 'maximized',
                            )
                        }
                        title="Expand"
                    >
                        <Maximize2 className="size-4" />
                    </button>
                    <button
                        type="button"
                        className="hover:bg-black/10 rounded p-1"
                        onClick={onClose}
                        title="Close"
                    >
                        <X className="size-4" />
                    </button>
                </div>
            </div>
            <div className="flex min-h-0 flex-1 flex-col overflow-hidden p-3">
                <ComposeForm
                    account={account}
                    draft={draft}
                    variant="popup"
                    onDiscard={onClose}
                />
            </div>
        </div>
    );
}
