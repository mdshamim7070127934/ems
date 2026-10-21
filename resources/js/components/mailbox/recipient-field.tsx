import { forwardRef, useEffect, useImperativeHandle, useRef, useState } from 'react';
import { autocomplete } from '@/routes/mailbox';
import { initials, isValidEmail } from '@/lib/mailbox';
import { cn } from '@/lib/utils';
import type { MailboxRecipientSuggestion } from '@/types';

export type RecipientFieldHandle = {
    commitPending: () => string[];
};

type Props = {
    label?: string;
    placeholder?: string;
    value: string[];
    onChange: (emails: string[]) => void;
    error?: string;
};

const RecipientField = forwardRef<RecipientFieldHandle, Props>(function RecipientField(
    {
        label,
        placeholder = 'Recipients',
        value,
        onChange,
        error,
    },
    ref,
) {
    const [query, setQuery] = useState('');
    const [open, setOpen] = useState(false);
    const [suggestions, setSuggestions] = useState<MailboxRecipientSuggestion[]>([]);
    const wrapRef = useRef<HTMLDivElement>(null);

    useImperativeHandle(ref, () => ({
        commitPending(): string[] {
            const next = query.replace(',', '').trim().toLowerCase();

            if (!isValidEmail(next) || value.includes(next)) {
                return value;
            }

            const emails = [...value, next];
            onChange(emails);
            setQuery('');
            setOpen(false);

            return emails;
        },
    }));

    useEffect(() => {
        if (query.trim().length < 2) {
            setSuggestions([]);
            setOpen(false);

            return;
        }

        const timeout = window.setTimeout(() => {
            void (async () => {
                const response = await fetch(autocomplete.url({ query: { q: query.trim() } }), {
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    return;
                }

                const payload = (await response.json()) as {
                    data?: Array<MailboxRecipientSuggestion | string>;
                };
                const items = (payload.data ?? []).map((item) =>
                    typeof item === 'string'
                        ? { email: item, display_name: item.split('@')[0] ?? item }
                        : item,
                );

                setSuggestions(items.filter((item) => !value.includes(item.email)));
                setOpen(items.length > 0);
            })();
        }, 200);

        return () => window.clearTimeout(timeout);
    }, [query, value]);

    useEffect(() => {
        function onPointerDown(event: PointerEvent) {
            if (!wrapRef.current?.contains(event.target as Node)) {
                setOpen(false);
            }
        }

        document.addEventListener('pointerdown', onPointerDown);

        return () => document.removeEventListener('pointerdown', onPointerDown);
    }, []);

    function add(email: string) {
        const next = email.trim().toLowerCase();

        if (!isValidEmail(next) || value.includes(next)) {
            return;
        }

        onChange([...value, next]);
        setQuery('');
        setOpen(false);
    }

    function remove(email: string) {
        onChange(value.filter((item) => item !== email));
    }

    return (
        <div ref={wrapRef} className="relative space-y-1">
            {label && (
                <p className="text-muted-foreground text-xs">{label}</p>
            )}
            <div
                className={cn(
                    'border-input flex min-h-9 flex-wrap items-center gap-1 rounded-md border px-2 py-1',
                    error && 'border-destructive',
                )}
            >
                {value.map((email) => (
                    <span
                        key={email}
                        className="bg-muted inline-flex items-center gap-1 rounded-full border py-0.5 pr-1 pl-0.5 text-xs"
                    >
                        <span className="bg-muted-foreground/70 flex size-5 items-center justify-center rounded-full text-[10px] font-semibold text-white">
                            {initials(null, email)}
                        </span>
                        <span>{email}</span>
                        <button
                            type="button"
                            className="hover:text-destructive px-1"
                            onClick={() => remove(email)}
                            aria-label={`Remove ${email}`}
                        >
                            ×
                        </button>
                    </span>
                ))}
                <input
                    value={query}
                    onChange={(event) => setQuery(event.target.value)}
                    onKeyDown={(event) => {
                        if (['Enter', 'Tab', ','].includes(event.key)) {
                            event.preventDefault();
                            add(query.replace(',', ''));
                        }

                        if (event.key === 'Backspace' && query === '' && value.length > 0) {
                            remove(value[value.length - 1]);
                        }
                    }}
                    onBlur={() => {
                        if (isValidEmail(query)) {
                            add(query);
                        }
                    }}
                    placeholder={value.length === 0 ? placeholder : ''}
                    className="placeholder:text-muted-foreground min-w-32 flex-1 bg-transparent py-1 text-sm outline-none"
                />
            </div>
            {open && suggestions.length > 0 && (
                <ul className="bg-popover absolute z-20 mt-1 max-h-48 w-full overflow-auto rounded-md border py-1 shadow-md">
                    {suggestions.map((item) => (
                        <li key={item.email}>
                            <button
                                type="button"
                                className="hover:bg-accent flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm"
                                onMouseDown={(event) => event.preventDefault()}
                                onClick={() => add(item.email)}
                            >
                                <span className="bg-muted-foreground/70 flex size-6 items-center justify-center rounded-full text-[10px] font-semibold text-white">
                                    {initials(item.display_name, item.email)}
                                </span>
                                <span className="min-w-0">
                                    <span className="block truncate font-medium">
                                        {item.display_name}
                                    </span>
                                    <span className="text-muted-foreground block truncate text-xs">
                                        {item.email}
                                    </span>
                                </span>
                            </button>
                        </li>
                    ))}
                </ul>
            )}
            {error && <p className="text-destructive text-sm">{error}</p>}
        </div>
    );
});

export default RecipientField;
