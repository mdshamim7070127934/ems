import { Check, ChevronsUpDown, Search, X } from 'lucide-react';
import {
    createContext,
    useContext,
    useEffect,
    useMemo,
    useRef,
    useState,
} from 'react';
import { createPortal } from 'react-dom';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

export const SearchableSelectPortalContext =
    createContext<HTMLElement | null>(null);

export type SearchableSelectOption = {
    value: string;
    label: string;
};

type Props = {
    id?: string;
    value: string;
    options: SearchableSelectOption[];
    placeholder?: string;
    searchPlaceholder?: string;
    emptyText?: string;
    disabled?: boolean;
    onValueChange: (value: string) => void;
};

type MenuPosition = {
    top: number;
    left: number;
    width: number;
};

export default function SearchableSelect({
    id,
    value,
    options,
    placeholder = 'Select an option',
    searchPlaceholder = 'Search...',
    emptyText = 'No results found.',
    disabled = false,
    onValueChange,
}: Props) {
    const portalRoot = useContext(SearchableSelectPortalContext);
    const rootRef = useRef<HTMLDivElement>(null);
    const menuRef = useRef<HTMLDivElement>(null);
    const searchRef = useRef<HTMLInputElement>(null);
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const [position, setPosition] = useState<MenuPosition | null>(null);

    const selected = options.find((option) => option.value === value);

    const filtered = useMemo(() => {
        const term = search.trim().toLowerCase();

        if (term === '') {
            return options;
        }

        return options.filter((option) =>
            option.label.toLowerCase().includes(term),
        );
    }, [options, search]);

    useEffect(() => {
        if (!open) {
            setSearch('');
            setPosition(null);

            return;
        }

        function updatePosition(): void {
            const trigger = rootRef.current;
            const container = portalRoot;

            if (trigger === null || container === null) {
                return;
            }

            const triggerRect = trigger.getBoundingClientRect();
            const containerRect = container.getBoundingClientRect();
            const menuHeight = menuRef.current?.offsetHeight ?? 240;
            const gap = 4;
            const spaceBelow = containerRect.bottom - triggerRect.bottom;
            const spaceAbove = triggerRect.top - containerRect.top;
            const opensUpward =
                spaceBelow < menuHeight + gap && spaceAbove > spaceBelow;

            setPosition({
                top: opensUpward
                    ? triggerRect.top - containerRect.top - menuHeight - gap
                    : triggerRect.bottom - containerRect.top + gap,
                left: triggerRect.left - containerRect.left,
                width: triggerRect.width,
            });
        }

        updatePosition();

        const timeout = window.setTimeout(() => {
            searchRef.current?.focus();
            updatePosition();
        }, 0);

        function handlePointerDown(event: MouseEvent): void {
            const target = event.target as Node;

            if (
                rootRef.current?.contains(target) ||
                menuRef.current?.contains(target)
            ) {
                return;
            }

            setOpen(false);
        }

        function handleKeyDown(event: KeyboardEvent): void {
            if (event.key === 'Escape') {
                event.stopPropagation();
                setOpen(false);
            }
        }

        document.addEventListener('mousedown', handlePointerDown);
        document.addEventListener('keydown', handleKeyDown);
        window.addEventListener('resize', updatePosition);
        document.addEventListener('scroll', updatePosition, true);

        return () => {
            window.clearTimeout(timeout);
            document.removeEventListener('mousedown', handlePointerDown);
            document.removeEventListener('keydown', handleKeyDown);
            window.removeEventListener('resize', updatePosition);
            document.removeEventListener('scroll', updatePosition, true);
        };
    }, [open, portalRoot]);

    function selectOption(nextValue: string): void {
        onValueChange(nextValue);
        setOpen(false);
    }

    const menu = (
        <div
            ref={menuRef}
            className={cn(
                'bg-popover text-popover-foreground z-50 rounded-md border shadow-md',
                portalRoot === null && 'absolute top-full mt-1 w-full',
            )}
            style={
                portalRoot === null
                    ? undefined
                    : {
                          position: 'absolute',
                          top: position?.top ?? 0,
                          left: position?.left ?? 0,
                          width: position?.width ?? 'auto',
                          visibility: position === null ? 'hidden' : 'visible',
                      }
            }
        >
            <div className="flex items-center gap-2 border-b px-2 py-1.5">
                <Search className="text-muted-foreground size-4 shrink-0" />
                <Input
                    ref={searchRef}
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder={searchPlaceholder}
                    aria-label={searchPlaceholder}
                    className="h-8 border-0 shadow-none focus-visible:ring-0"
                    onKeyDown={(event) => {
                        event.stopPropagation();

                        if (event.key === 'Enter') {
                            event.preventDefault();

                            if (filtered[0] !== undefined) {
                                selectOption(filtered[0].value);
                            }
                        }
                    }}
                />
                {search !== '' && (
                    <button
                        type="button"
                        className="text-muted-foreground hover:text-foreground rounded-sm p-1"
                        onClick={() => setSearch('')}
                        aria-label="Clear search"
                    >
                        <X className="size-4" />
                    </button>
                )}
            </div>

            <ul role="listbox" className="max-h-60 overflow-y-auto p-1">
                {filtered.length === 0 ? (
                    <li className="text-muted-foreground px-2 py-6 text-center text-sm">
                        {emptyText}
                    </li>
                ) : (
                    filtered.map((option) => {
                        const isSelected = option.value === value;

                        return (
                            <li key={option.value}>
                                <button
                                    type="button"
                                    role="option"
                                    aria-selected={isSelected}
                                    className={cn(
                                        'hover:bg-accent hover:text-accent-foreground flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm',
                                        isSelected && 'bg-accent',
                                    )}
                                    onClick={() => selectOption(option.value)}
                                >
                                    <Check
                                        className={cn(
                                            'size-4 shrink-0',
                                            isSelected
                                                ? 'opacity-100'
                                                : 'opacity-0',
                                        )}
                                    />
                                    <span className="truncate">
                                        {option.label}
                                    </span>
                                </button>
                            </li>
                        );
                    })
                )}
            </ul>
        </div>
    );

    return (
        <div ref={rootRef} className="relative">
            <Button
                id={id}
                type="button"
                variant="outline"
                disabled={disabled}
                aria-expanded={open}
                aria-haspopup="listbox"
                className="w-full justify-between font-normal"
                onClick={() => setOpen((current) => !current)}
            >
                <span
                    className={cn(
                        'truncate',
                        selected === undefined && 'text-muted-foreground',
                    )}
                >
                    {selected?.label ?? placeholder}
                </span>
                <ChevronsUpDown className="size-4 opacity-50" />
            </Button>

            {open &&
                (portalRoot === null
                    ? menu
                    : createPortal(menu, portalRoot))}
        </div>
    );
}
