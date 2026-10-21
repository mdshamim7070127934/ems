import { Head, Link, router, useForm } from '@inertiajs/react';
import { useMemo, type FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import {
    disconnect,
    index,
    reconnect,
    store,
    update,
} from '@/routes/master/email-accounts';
import type {
    EmailEnumOption,
    EmailTypeOption,
    MasterEmailAccount,
    ModuleStatus,
} from '@/types';

type Props = {
    emailAccount?: MasterEmailAccount | null;
    emailTypeOptions: EmailTypeOption[];
    providerOptions: EmailEnumOption[];
    mailerOptions: EmailEnumOption[];
    encryptionOptions: EmailEnumOption[];
};

export default function EmailAccountForm({
    emailAccount = null,
    emailTypeOptions,
    providerOptions,
    mailerOptions,
    encryptionOptions,
}: Props) {
    const isEditing = emailAccount !== null;
    const usesOAuth = emailAccount?.uses_oauth === true;
    const { data, setData, post, put, processing, errors } = useForm({
        name: emailAccount?.name ?? '',
        provider: emailAccount?.provider ?? 'smtp',
        mailer: emailAccount?.mailer ?? 'smtp',
        from_name: emailAccount?.from_name ?? '',
        from_email: emailAccount?.from_email ?? '',
        username: emailAccount?.username ?? '',
        password: '',
        smtp_host: emailAccount?.smtp_host ?? '',
        smtp_port: emailAccount?.smtp_port ? String(emailAccount.smtp_port) : '587',
        smtp_encryption: emailAccount?.smtp_encryption ?? 'tls',
        status: (emailAccount?.status ?? 'active') as ModuleStatus,
        is_default: emailAccount?.is_default ?? false,
        email_type_ids: emailAccount?.email_type_ids ?? [],
    });

    const selected = useMemo(
        () => new Set(data.email_type_ids),
        [data.email_type_ids],
    );

    function toggleType(typeId: number, checked: boolean): void {
        if (checked) {
            setData('email_type_ids', [...data.email_type_ids, typeId]);

            return;
        }

        setData(
            'email_type_ids',
            data.email_type_ids.filter((id) => id !== typeId),
        );
    }

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        if (isEditing && emailAccount) {
            put(update.url(emailAccount.id));

            return;
        }

        post(store.url());
    }

    return (
        <>
            <Head
                title={isEditing ? 'Edit email account' : 'Add SMTP account'}
            />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title={
                            isEditing
                                ? 'Edit email account'
                                : 'Add SMTP account'
                        }
                        description={
                            usesOAuth
                                ? 'Update account details. Gmail and Outlook stay connected through OAuth — passwords are not used.'
                                : 'Enter SMTP server details for a custom mail provider.'
                        }
                    />
                    <Button asChild variant="outline" className="sm:mt-1">
                        <Link href={index.url()}>Back to email accounts</Link>
                    </Button>
                </div>

                <form
                    onSubmit={submit}
                    className="flex max-w-3xl flex-col gap-6"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="name">Account name</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(event) =>
                                setData('name', event.target.value)
                            }
                            required
                            autoFocus={!isEditing}
                            maxLength={150}
                            disabled={processing}
                            placeholder="e.g. Company mail"
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-6 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="provider">Provider</Label>
                            <Select
                                value={data.provider}
                                onValueChange={(value) =>
                                    setData('provider', value)
                                }
                                disabled={processing || usesOAuth}
                            >
                                <SelectTrigger id="provider" className="w-full">
                                    <SelectValue placeholder="Select provider" />
                                </SelectTrigger>
                                <SelectContent>
                                    {providerOptions.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.provider} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="mailer">Mailer</Label>
                            <Select
                                value={data.mailer}
                                onValueChange={(value) =>
                                    setData('mailer', value)
                                }
                                disabled={processing || usesOAuth}
                            >
                                <SelectTrigger id="mailer" className="w-full">
                                    <SelectValue placeholder="Select mailer" />
                                </SelectTrigger>
                                <SelectContent>
                                    {mailerOptions.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.mailer} />
                        </div>
                    </div>

                    <div className="grid gap-6 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="from_name">From name</Label>
                            <Input
                                id="from_name"
                                value={data.from_name}
                                onChange={(event) =>
                                    setData('from_name', event.target.value)
                                }
                                required
                                maxLength={150}
                                disabled={processing}
                            />
                            <InputError message={errors.from_name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="from_email">From email</Label>
                            <Input
                                id="from_email"
                                type="email"
                                value={data.from_email}
                                onChange={(event) =>
                                    setData('from_email', event.target.value)
                                }
                                required={!usesOAuth}
                                maxLength={255}
                                disabled={processing || usesOAuth}
                            />
                            <InputError message={errors.from_email} />
                        </div>
                    </div>

                    {usesOAuth && emailAccount ? (
                        <div className="grid gap-4 rounded-xl border p-4">
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p className="font-medium">
                                        OAuth connection
                                    </p>
                                    <p className="text-muted-foreground mt-1 text-sm">
                                        Tokens are stored encrypted and are not
                                        shown here.
                                    </p>
                                </div>
                                <Badge
                                    variant={
                                        emailAccount.reauthorization_required
                                            ? 'destructive'
                                            : 'default'
                                    }
                                >
                                    {emailAccount.connection_status_label}
                                </Badge>
                            </div>

                            {emailAccount.oauth_error && (
                                <p className="text-sm text-destructive">
                                    {emailAccount.oauth_error}
                                </p>
                            )}

                            <dl className="grid gap-2 text-sm sm:grid-cols-2">
                                <div>
                                    <dt className="text-muted-foreground">
                                        Token expires
                                    </dt>
                                    <dd className="font-medium">
                                        {emailAccount.oauth_token_expires_at ||
                                            '—'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Last refresh
                                    </dt>
                                    <dd className="font-medium">
                                        {emailAccount.last_token_refresh_at ||
                                            '—'}
                                    </dd>
                                </div>
                            </dl>

                            <div className="flex flex-wrap gap-2">
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
                                    disabled={processing}
                                >
                                    Disconnect
                                </Button>
                            </div>
                        </div>
                    ) : (
                        <div className="grid gap-6 rounded-xl border p-4">
                            <p className="font-medium">SMTP settings</p>

                            <div className="grid gap-2">
                                <Label htmlFor="username">Username</Label>
                                <Input
                                    id="username"
                                    value={data.username}
                                    onChange={(event) =>
                                        setData('username', event.target.value)
                                    }
                                    required
                                    maxLength={255}
                                    disabled={processing}
                                />
                                <InputError message={errors.username} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(event) =>
                                        setData('password', event.target.value)
                                    }
                                    required={!isEditing}
                                    disabled={processing}
                                    placeholder={
                                        isEditing
                                            ? 'Leave blank to keep the current password'
                                            : 'SMTP password'
                                    }
                                    autoComplete="new-password"
                                />
                                {isEditing &&
                                    emailAccount?.has_password &&
                                    data.password === '' && (
                                        <p className="text-muted-foreground text-xs">
                                            A password is already saved for this
                                            account.
                                        </p>
                                    )}
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-6 sm:grid-cols-3">
                                <div className="grid gap-2 sm:col-span-1">
                                    <Label htmlFor="smtp_host">SMTP host</Label>
                                    <Input
                                        id="smtp_host"
                                        value={data.smtp_host}
                                        onChange={(event) =>
                                            setData(
                                                'smtp_host',
                                                event.target.value,
                                            )
                                        }
                                        required
                                        disabled={processing}
                                        placeholder="smtp.example.com"
                                    />
                                    <InputError message={errors.smtp_host} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="smtp_port">SMTP port</Label>
                                    <Input
                                        id="smtp_port"
                                        type="number"
                                        min={1}
                                        max={65535}
                                        value={data.smtp_port}
                                        onChange={(event) =>
                                            setData(
                                                'smtp_port',
                                                event.target.value,
                                            )
                                        }
                                        required
                                        disabled={processing}
                                    />
                                    <InputError message={errors.smtp_port} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="smtp_encryption">
                                        Encryption
                                    </Label>
                                    <Select
                                        value={data.smtp_encryption}
                                        onValueChange={(value) =>
                                            setData('smtp_encryption', value)
                                        }
                                        disabled={processing}
                                    >
                                        <SelectTrigger
                                            id="smtp_encryption"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="Select encryption" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {encryptionOptions.map((option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={errors.smtp_encryption}
                                    />
                                </div>
                            </div>
                        </div>
                    )}

                    <div className="grid gap-3">
                        <div>
                            <Label>Email types</Label>
                            <p className="text-muted-foreground mt-1 text-sm">
                                Select which email types this account can send.
                            </p>
                            <InputError message={errors.email_type_ids} />
                        </div>

                        {emailTypeOptions.length === 0 ? (
                            <p className="text-muted-foreground rounded-xl border p-4 text-sm">
                                No active email types are available. Create
                                email types first, then assign them here.
                            </p>
                        ) : (
                            <div className="grid gap-2 rounded-xl border p-4">
                                {emailTypeOptions.map((type) => (
                                    <div
                                        key={type.id}
                                        className="flex items-center gap-2"
                                    >
                                        <Checkbox
                                            id={`email-type-${type.id}`}
                                            checked={selected.has(type.id)}
                                            onCheckedChange={(checked) =>
                                                toggleType(
                                                    type.id,
                                                    checked === true,
                                                )
                                            }
                                            disabled={
                                                processing || type.disabled
                                            }
                                        />
                                        <Label
                                            htmlFor={`email-type-${type.id}`}
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

                    <div className="flex flex-col gap-3">
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="is_default"
                                checked={data.is_default}
                                onCheckedChange={(checked) =>
                                    setData('is_default', checked === true)
                                }
                                disabled={
                                    processing || data.status !== 'active'
                                }
                            />
                            <Label htmlFor="is_default" className="font-normal">
                                Default sending account
                            </Label>
                        </div>
                        <InputError message={errors.is_default} />

                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="status"
                                checked={data.status === 'active'}
                                onCheckedChange={(checked) => {
                                    const status =
                                        checked === true
                                            ? 'active'
                                            : 'inactive';

                                    setData({
                                        ...data,
                                        status,
                                        is_default:
                                            status === 'active'
                                                ? data.is_default
                                                : false,
                                    });
                                }}
                                disabled={processing}
                            />
                            <Label htmlFor="status" className="font-normal">
                                Active
                            </Label>
                        </div>
                        <InputError message={errors.status} />
                    </div>

                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            {isEditing
                                ? 'Update email account'
                                : 'Create email account'}
                        </Button>
                        <Button asChild type="button" variant="secondary">
                            <Link href={index.url()}>Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

EmailAccountForm.layout = {
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
            title: 'Email account',
            href: index(),
        },
    ],
};
