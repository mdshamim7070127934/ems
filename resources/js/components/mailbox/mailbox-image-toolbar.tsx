import { useEffect, useState } from 'react';
import {
    detectEmailImageSize,
    toolbarPositionForImage,
    type EmailImageAction,
    type EmailImageSize,
} from '@/lib/email-image';
import { cn } from '@/lib/utils';

type Props = {
    image: HTMLElement | null;
    onAction: (action: EmailImageAction) => void;
};

const sizeActions: Array<{ id: EmailImageSize; label: string }> = [
    { id: 'small', label: 'Small' },
    { id: 'bestfit', label: 'Best fit' },
    { id: 'original', label: 'Original size' },
];

export default function MailboxImageToolbar({ image, onAction }: Props) {
    const [position, setPosition] = useState({ left: 0, top: 0 });
    const [active, setActive] = useState<EmailImageSize>('bestfit');

    useEffect(() => {
        if (!image) {
            return;
        }

        const update = (): void => {
            setPosition(toolbarPositionForImage(image));
            setActive(detectEmailImageSize(image));
        };

        update();
        window.addEventListener('scroll', update, true);
        window.addEventListener('resize', update);

        return () => {
            window.removeEventListener('scroll', update, true);
            window.removeEventListener('resize', update);
        };
    }, [image]);

    if (!image) {
        return null;
    }

    return (
        <div
            className="fixed z-[1200] inline-flex flex-wrap items-center rounded border border-[#dadce0] bg-white px-2.5 py-1.5 text-[13px] shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
            style={{ left: position.left, top: position.top }}
            onMouseDown={(event) => event.preventDefault()}
        >
            {sizeActions.map((action, index) => (
                <span key={action.id} className="inline-flex items-center">
                    {index > 0 && (
                        <span className="mx-0.5 select-none text-[#dadce0]">|</span>
                    )}
                    <button
                        type="button"
                        className={cn(
                            'cursor-pointer rounded-sm px-1.5 py-0.5 text-[#1a73e8] hover:bg-[#e8f0fe] dark:hover:bg-zinc-800',
                            active === action.id && 'font-bold',
                        )}
                        onMouseDown={(event) => {
                            event.preventDefault();
                            onAction(action.id);
                            setActive(action.id);
                        }}
                    >
                        {action.label}
                    </button>
                </span>
            ))}
            <span className="mx-0.5 select-none text-[#dadce0]">|</span>
            <button
                type="button"
                className="cursor-pointer rounded-sm px-1.5 py-0.5 text-[#1a73e8] hover:bg-[#e8f0fe] dark:hover:bg-zinc-800"
                onMouseDown={(event) => {
                    event.preventDefault();
                    onAction('alt');
                }}
            >
                Edit alt text
            </button>
            <span className="mx-0.5 select-none text-[#dadce0]">|</span>
            <button
                type="button"
                className="cursor-pointer rounded-sm px-1.5 py-0.5 text-[#1a73e8] hover:bg-[#e8f0fe] dark:hover:bg-zinc-800"
                onMouseDown={(event) => {
                    event.preventDefault();
                    onAction('remove');
                }}
            >
                Remove
            </button>
        </div>
    );
}
