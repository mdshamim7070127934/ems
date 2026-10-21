import { router } from '@inertiajs/react';
import { Ban, UserCheck } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { blockSender, unblockSender } from '@/routes/mailbox';

type Props = {
    email: string | null | undefined;
    accountEmail: string;
    accountId: number;
    blocked: boolean;
    variant?: 'menu' | 'button' | 'link';
};

export default function SenderBlockAction({
    email,
    accountEmail,
    accountId,
    blocked,
    variant = 'menu',
}: Props) {
    if (
        email === null ||
        email === undefined ||
        email === '' ||
        email.toLowerCase() === accountEmail.toLowerCase()
    ) {
        return null;
    }

    function submit(): void {
        if (blocked && !window.confirm(`Unblock ${email}?`)) {
            return;
        }

        router.post(
            (blocked ? unblockSender : blockSender).url(),
            {
                email,
                account_id: accountId,
            },
            { preserveState: false },
        );
    }

    const label = blocked
        ? `Unblock sender (${email})`
        : `Block sender (${email})`;
    const icon = blocked ? (
        <UserCheck className="text-muted-foreground" />
    ) : (
        <Ban className="text-destructive" />
    );

    if (variant === 'link') {
        return (
            <button
                type="button"
                className="font-medium underline"
                onClick={submit}
            >
                {label}
            </button>
        );
    }

    if (variant === 'button') {
        return (
            <Button size="sm" variant="ghost" onClick={submit}>
                {icon}
                {label}
            </Button>
        );
    }

    return (
        <DropdownMenuItem
            className="items-start whitespace-normal"
            onClick={(event) => {
                event.stopPropagation();
                submit();
            }}
        >
            {icon}
            <span className="text-left">{label}</span>
        </DropdownMenuItem>
    );
}
