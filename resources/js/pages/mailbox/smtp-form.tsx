import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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
import { connect, index as mailboxIndex } from '@/routes/mailbox';
import { smtp as connectSmtp } from '@/routes/mailbox/connect';
import { store as connectSmtpStore } from '@/routes/mailbox/connect/smtp';

type SmtpEncryptionValue = 'tls' | 'ssl' | 'none';

const ENCRYPTION_OPTIONS: { value: SmtpEncryptionValue; label: string }[] = [
    { value: 'tls', label: 'TLS' },
    { value: 'ssl', label: 'SSL' },
    { value: 'none', label: 'None' },
];

export default function MailboxSmtpForm() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        display_name: '',
        smtp_host: '',
        smtp_port: 587,
        smtp_username: '',
        smtp_password: '',
        smtp_encryption: 'tls' as SmtpEncryptionValue,
        imap_host: '',
        imap_port: 993,
        imap_username: '',
        imap_password: '',
        imap_encryption: 'ssl' as SmtpEncryptionValue,
    });

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        post(connectSmtpStore.url());
    }

    return (
        <>
            <Head title="Connect email account" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Connect email account"
                        description="Add an SMTP/IMAP mailbox. IMAP host is optional — leave it blank to use the SMTP host."
                    />
                    <Button asChild variant="outline" className="sm:mt-1">
                        <Link href={connect.url()}>Back</Link>
                    </Button>
                </div>

                <form
                    className="border-sidebar-border/70 flex max-w-3xl flex-col gap-6 rounded-xl border p-4"
                    onSubmit={submit}
                >
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(event) =>
                                    setData('email', event.target.value)
                                }
                                placeholder="you@example.com"
                                autoComplete="email"
                                required
                            />
                            <InputError message={errors.email} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="display_name">Display name</Label>
                            <Input
                                id="display_name"
                                value={data.display_name}
                                onChange={(event) =>
                                    setData('display_name', event.target.value)
                                }
                                placeholder="Optional"
                            />
                            <InputError message={errors.display_name} />
                        </div>
                    </div>

                    <div className="grid gap-4">
                        <h2 className="text-sm font-semibold">SMTP</h2>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="smtp_host">SMTP host</Label>
                                <Input
                                    id="smtp_host"
                                    value={data.smtp_host}
                                    onChange={(event) =>
                                        setData('smtp_host', event.target.value)
                                    }
                                    placeholder="smtp.example.com"
                                    required
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
                                            Number(event.target.value),
                                        )
                                    }
                                    required
                                />
                                <InputError message={errors.smtp_port} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="smtp_encryption">
                                    SMTP encryption
                                </Label>
                                <EncryptionSelect
                                    id="smtp_encryption"
                                    value={data.smtp_encryption}
                                    disabled={processing}
                                    onChange={(value) =>
                                        setData('smtp_encryption', value)
                                    }
                                />
                                <InputError message={errors.smtp_encryption} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="smtp_username">
                                    SMTP username
                                </Label>
                                <Input
                                    id="smtp_username"
                                    value={data.smtp_username}
                                    onChange={(event) =>
                                        setData(
                                            'smtp_username',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Usually the email address"
                                    autoComplete="username"
                                />
                                <InputError message={errors.smtp_username} />
                            </div>
                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="smtp_password">
                                    SMTP password
                                </Label>
                                <Input
                                    id="smtp_password"
                                    type="password"
                                    value={data.smtp_password}
                                    onChange={(event) =>
                                        setData(
                                            'smtp_password',
                                            event.target.value,
                                        )
                                    }
                                    autoComplete="new-password"
                                />
                                <InputError message={errors.smtp_password} />
                            </div>
                        </div>
                    </div>

                    <div className="grid gap-4">
                        <h2 className="text-sm font-semibold">IMAP</h2>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="imap_host">IMAP host</Label>
                                <Input
                                    id="imap_host"
                                    value={data.imap_host}
                                    onChange={(event) =>
                                        setData('imap_host', event.target.value)
                                    }
                                    placeholder="Leave blank to use SMTP host"
                                />
                                <InputError message={errors.imap_host} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="imap_port">IMAP port</Label>
                                <Input
                                    id="imap_port"
                                    type="number"
                                    min={1}
                                    max={65535}
                                    value={data.imap_port}
                                    onChange={(event) =>
                                        setData(
                                            'imap_port',
                                            Number(event.target.value),
                                        )
                                    }
                                    required
                                />
                                <InputError message={errors.imap_port} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="imap_encryption">
                                    IMAP encryption
                                </Label>
                                <EncryptionSelect
                                    id="imap_encryption"
                                    value={data.imap_encryption}
                                    disabled={processing}
                                    onChange={(value) =>
                                        setData('imap_encryption', value)
                                    }
                                />
                                <InputError message={errors.imap_encryption} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="imap_username">
                                    IMAP username
                                </Label>
                                <Input
                                    id="imap_username"
                                    value={data.imap_username}
                                    onChange={(event) =>
                                        setData(
                                            'imap_username',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Leave blank to use SMTP username"
                                    autoComplete="off"
                                />
                                <InputError message={errors.imap_username} />
                            </div>
                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="imap_password">
                                    IMAP password
                                </Label>
                                <Input
                                    id="imap_password"
                                    type="password"
                                    value={data.imap_password}
                                    onChange={(event) =>
                                        setData(
                                            'imap_password',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Leave blank to use SMTP password"
                                    autoComplete="new-password"
                                />
                                <InputError message={errors.imap_password} />
                            </div>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            Add account
                        </Button>
                        <Button asChild type="button" variant="secondary">
                            <Link href={mailboxIndex.url()}>Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

function EncryptionSelect({
    id,
    value,
    disabled,
    onChange,
}: {
    id: string;
    value: SmtpEncryptionValue;
    disabled: boolean;
    onChange: (value: SmtpEncryptionValue) => void;
}) {
    return (
        <Select
            value={value}
            onValueChange={(next) => {
                if (next === 'tls' || next === 'ssl' || next === 'none') {
                    onChange(next);
                }
            }}
            disabled={disabled}
        >
            <SelectTrigger id={id} className="w-full">
                <SelectValue placeholder="Encryption" />
            </SelectTrigger>
            <SelectContent>
                {ENCRYPTION_OPTIONS.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

MailboxSmtpForm.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Mail', href: mailboxIndex() },
        { title: 'Connect', href: connect() },
        { title: 'SMTP / IMAP', href: connectSmtp() },
    ],
};
