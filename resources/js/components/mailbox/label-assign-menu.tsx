import { Folder, Plus, Tag } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useMailboxCompose } from '@/components/mailbox/mailbox-shell';
import type { MailboxLabel } from '@/types';

type Props = {
    labels: MailboxLabel[];
    canAssign?: boolean;
    onAssign: (labelId: number) => void;
    onCreate: () => void;
};

export default function LabelAssignMenu({
    labels,
    canAssign = true,
    onAssign,
    onCreate,
}: Props) {
    return (
        <>
            <DropdownMenuLabel>Label</DropdownMenuLabel>
            {labels.length === 0 && (
                <p className="text-muted-foreground px-2 py-1.5 text-xs italic">
                    No labels available. Create one first.
                </p>
            )}
            {labels.map((label) => (
                <DropdownMenuItem
                    key={label.id}
                    disabled={!canAssign}
                    onClick={() => onAssign(label.id)}
                >
                    <span
                        className="size-3.5 rounded-sm"
                        style={{ backgroundColor: label.color ?? '#5f6368' }}
                    />
                    {label.name}
                </DropdownMenuItem>
            ))}
            <DropdownMenuSeparator />
            <DropdownMenuLabel>Move</DropdownMenuLabel>
            {labels.length === 0 && (
                <p className="text-muted-foreground px-2 py-1.5 text-xs">
                    No labels. Create one below.
                </p>
            )}
            {labels.map((label) => (
                <DropdownMenuItem
                    key={`move-${label.id}`}
                    disabled={!canAssign}
                    onClick={() => onAssign(label.id)}
                >
                    <Folder />
                    <span
                        className="size-3.5 rounded-sm"
                        style={{ backgroundColor: label.color ?? '#5f6368' }}
                    />
                    Move to {label.name}
                </DropdownMenuItem>
            ))}
            <DropdownMenuSeparator />
            <DropdownMenuItem onClick={onCreate}>
                <Plus />
                Create new label
            </DropdownMenuItem>
        </>
    );
}

export function LabelToolbarButton({
    labels,
    canAssign = true,
    onAssign,
    size = 'sm',
    className,
}: {
    labels: MailboxLabel[];
    canAssign?: boolean;
    onAssign: (labelId: number) => void;
    size?: 'sm' | 'default';
    className?: string;
}) {
    const { openCreateLabel } = useMailboxCompose();

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="outline" size={size} className={className}>
                    <Tag />
                    Label
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="min-w-[220px]">
                <LabelAssignMenu
                    labels={labels}
                    canAssign={canAssign}
                    onAssign={onAssign}
                    onCreate={openCreateLabel}
                />
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
