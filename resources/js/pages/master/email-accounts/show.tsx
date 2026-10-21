import { Head, Link, router } from '@inertiajs/react';
import { useMemo } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { disconnect, edit, index, reconnect } from '@/routes/master/email-accounts';
import type { EmailTypeOption, MasterEmailAccount } from '@/types';

type Props = {
    emailAccount: MasterEmailAccount;
    emailTypeOptions: EmailTypeOption[];
};

export default function EmailAccountShow({
    emailAccount,
    emailTypeOptions,
}: Props) {
    const selected = useMemo(
        () => new Set(emailAccount.email_type_ids),
        [emailAccount.email_type_ids],
    );

    return (
        <>
            <Head title={`View email account - ${emailAccount.name}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="View email account"
                        description="Read-only details for this sending account."
                    />
                    <div className="flex gap-2 sm:mt-1">
                        {emailAccount.uses_oauth && (
                            <>
                                <Button asChild>
                                    <a href={reconnect.url(emailAccount.id)}>
                                        {emailAccount.reauthorization_required
                                            ? 'Reconnect'
                                            : 'Reconnect account'}
                                    </a>
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() =>
                                        router.post(
                                            disconnect.url(emailAccount.id),
                                        )
                                    }
                                >
                                    Disconnect
                                </Button>
                            </>
                        )}
                        <Button asChild>
                            <Link href={edit.url(emailAccount.id)}>
                                Edit email account
                            </Link>
                        </Button>
                        <Button asChild variant="outline">
                            <Link href={index.url()}>Back to email accounts</Link>
                        </Button>
                    </div>
                </div>

                <dl className="grid max-w-3xl gap-4 text-sm sm:grid-cols-2">
                    <div className="grid gap-1">
                        <dt className="text-muted-foreground">Account name</dt>
                        <dd className="font-medium">{emailAccount.name}</dd>
                    </div>
                    <div className="grid gap-1">
                        <dt className="text-muted-foreground">Status</dt>
                        <dd>
                            <Badge
                                variant={
                                    emailAccount.status === 'active'
                                        ? 'default'
                                        : 'secondary'
                                }
                            >
                                {emailAccount.status_label}
                            </Badge>
                        </dd>
                    </div>
                    <div className="grid gap-1">
                        <dt className="text-muted-foreground">Provider</dt>
                        <dd className="font-medium">
                            {emailAccount.provider_label}
                        </dd>
                    </div>
                    <div className="grid gap-1">
                        <dt className="text-muted-foreground">Mailer</dt>
                        <dd className="font-medium">
                            {emailAccount.mailer_label}
                        </dd>
                    </div>
                    <div className="grid gap-1">
                        <dt className="text-muted-foreground">From name</dt>
                        <dd className="font-medium">{emailAccount.from_name}</dd>
                    </div>
                    <div className="grid gap-1">
                        <dt className="text-muted-foreground">From email</dt>
                        <dd className="font-medium">
                            {emailAccount.from_email}
                        </dd>
                    </div>
                    <div className="grid gap-1">
                        <dt className="text-muted-foreground">Connection</dt>
                        <dd>
                            <Badge
                                variant={
                                    emailAccount.reauthorization_required
                                        ? 'destructive'
                                        : emailAccount.connection_status ===
                                            'connected'
                                          ? 'default'
                                          : 'secondary'
                                }
                            >
                                {emailAccount.connection_status_label}
                            </Badge>
                        </dd>
                    </div>
                    <div className="grid gap-1">
                        <dt className="text-muted-foreground">Default</dt>
                        <dd className="font-medium">
                            {emailAccount.is_default ? 'Yes' : 'No'}
                        </dd>
                    </div>
                    {!emailAccount.uses_oauth && (
                        <>
                            <div className="grid gap-1">
                                <dt className="text-muted-foreground">
                                    Username
                                </dt>
                                <dd className="font-medium">
                                    {emailAccount.username || '—'}
                                </dd>
                            </div>
                            <div className="grid gap-1">
                                <dt className="text-muted-foreground">
                                    SMTP host
                                </dt>
                                <dd className="font-medium">
                                    {emailAccount.smtp_host || '—'}
                                </dd>
                            </div>
                            <div className="grid gap-1">
                                <dt className="text-muted-foreground">
                                    SMTP port
                                </dt>
                                <dd className="font-medium">
                                    {emailAccount.smtp_port ?? '—'}
                                </dd>
                            </div>
                            <div className="grid gap-1">
                                <dt className="text-muted-foreground">
                                    Encryption
                                </dt>
                                <dd className="font-medium">
                                    {emailAccount.smtp_encryption_label || '—'}
                                </dd>
                            </div>
                            <div className="grid gap-1">
                                <dt className="text-muted-foreground">
                                    Password saved
                                </dt>
                                <dd className="font-medium">
                                    {emailAccount.has_password ? 'Yes' : 'No'}
                                </dd>
                            </div>
                        </>
                    )}
                    {emailAccount.uses_oauth && (
                        <>
                            {emailAccount.oauth_error && (
                                <div className="grid gap-1 sm:col-span-2">
                                    <dt className="text-muted-foreground">
                                        Connection message
                                    </dt>
                                    <dd className="font-medium">
                                        {emailAccount.oauth_error}
                                    </dd>
                                </div>
                            )}
                            <div className="grid gap-1">
                                <dt className="text-muted-foreground">
                                    Token expires at
                                </dt>
                                <dd className="font-medium">
                                    {emailAccount.oauth_token_expires_at || '—'}
                                </dd>
                            </div>
                            <div className="grid gap-1">
                                <dt className="text-muted-foreground">
                                    Last token refresh
                                </dt>
                                <dd className="font-medium">
                                    {emailAccount.last_token_refresh_at || '—'}
                                </dd>
                            </div>
                        </>
                    )}
                    <div className="grid gap-1">
                        <dt className="text-muted-foreground">Created date</dt>
                        <dd className="font-medium">{emailAccount.created_at}</dd>
                    </div>
                    <div className="grid gap-1">
                        <dt className="text-muted-foreground">Updated date</dt>
                        <dd className="font-medium">{emailAccount.updated_at}</dd>
                    </div>
                </dl>

                <div className="grid max-w-3xl gap-3">
                    <div>
                        <Label>Email types</Label>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Types this account is configured to send.
                        </p>
                    </div>

                    {emailTypeOptions.length === 0 ? (
                        <p className="text-muted-foreground rounded-xl border p-4 text-sm">
                            No email types are assigned.
                        </p>
                    ) : (
                        <div className="grid gap-2 rounded-xl border p-4">
                            {emailTypeOptions.map((type) => (
                                <div
                                    key={type.id}
                                    className="flex items-center gap-2"
                                >
                                    <Checkbox
                                        id={`view-email-type-${type.id}`}
                                        checked={selected.has(type.id)}
                                        disabled
                                    />
                                    <Label
                                        htmlFor={`view-email-type-${type.id}`}
                                        className="font-normal"
                                    >
                                        {type.name}
                                        <span className="text-muted-foreground ml-2 text-xs">
                                            ({type.code})
                                        </span>
                                    </Label>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

EmailAccountShow.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Email accounts',
            href: index(),
        },
        {
            title: 'View email account',
            href: index(),
        },
    ],
};
