import { useMemo, useState } from 'react';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

const CATEGORIES: Array<{ id: string; label: string; emojis: string[] }> = [
    {
        id: 'smileys',
        label: 'Smileys',
        emojis: ['😀', '😁', '😂', '🤣', '😊', '😍', '😘', '😎', '🤔', '😅', '😢', '😭', '😡', '👍', '👎', '🙏', '👏', '🔥', '❤️', '✨'],
    },
    {
        id: 'gestures',
        label: 'Gestures',
        emojis: ['👋', '👌', '✌️', '🤞', '🤝', '💪', '🫶', '🙌', '👉', '👈', '👆', '👇', '💯', '✅', '❌', '⚠️'],
    },
    {
        id: 'objects',
        label: 'Objects',
        emojis: ['📎', '📌', '📁', '📂', '📅', '📝', '📧', '📨', '📦', '💡', '🔒', '🔑', '📞', '💻', '🖥️', '📱'],
    },
    {
        id: 'travel',
        label: 'Travel',
        emojis: ['✈️', '🚗', '🏠', '🏢', '🌍', '🗺️', '⏰', '☕', '🍕', '🎉', '🎁', '⭐'],
    },
];

type Props = {
    open: boolean;
    onClose: () => void;
    onSelect: (emoji: string) => void;
    className?: string;
};

export default function EmojiPicker({ open, onClose, onSelect, className }: Props) {
    const [query, setQuery] = useState('');
    const [category, setCategory] = useState(CATEGORIES[0].id);

    const emojis = useMemo(() => {
        const source = CATEGORIES.find((item) => item.id === category)?.emojis ?? CATEGORIES[0].emojis;

        if (query.trim() === '') {
            return source;
        }

        return CATEGORIES.flatMap((item) => item.emojis).filter((emoji) =>
            emoji.includes(query.trim()),
        );
    }, [category, query]);

    if (!open) {
        return null;
    }

    return (
        <div
            className={cn(
                'bg-popover text-popover-foreground absolute bottom-full left-0 z-30 mb-2 w-80 rounded-md border p-2 shadow-lg',
                className,
            )}
        >
            <Input
                value={query}
                onChange={(event) => setQuery(event.target.value)}
                placeholder="Search emoji"
                className="mb-2 h-8"
            />
            <div className="mb-2 flex flex-wrap gap-1">
                {CATEGORIES.map((item) => (
                    <button
                        key={item.id}
                        type="button"
                        onClick={() => setCategory(item.id)}
                        className={cn(
                            'rounded px-2 py-1 text-xs',
                            category === item.id
                                ? 'bg-[#e8f0fe] text-[#1967d2]'
                                : 'text-muted-foreground hover:bg-accent',
                        )}
                    >
                        {item.label}
                    </button>
                ))}
            </div>
            <div className="grid max-h-52 grid-cols-8 gap-1 overflow-auto">
                {emojis.map((emoji) => (
                    <button
                        key={emoji}
                        type="button"
                        className="hover:bg-accent rounded p-1 text-lg"
                        onClick={() => {
                            onSelect(emoji);
                            onClose();
                        }}
                    >
                        {emoji}
                    </button>
                ))}
            </div>
        </div>
    );
}
