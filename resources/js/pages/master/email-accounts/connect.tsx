import { Head, Link } from '@inertiajs/react';
import { Mail } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import { create, index } from '@/routes/master/email-accounts';
import { redirect as oauthRedirect } from '@/routes/master/email-accounts/oauth';

export default function EmailAccountConnect() {
    return (
        <>
            <Head title="Connect email account" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Connect account"
                        description="Connect Gmail or Outlook with OAuth, or enter SMTP details for other providers. Gmail and Outlook passwords are never requested or stored."
                    />
                    <Button asChild variant="outline" className="sm:mt-1">
                        <Link href={index.url()}>Back to email accounts</Link>
                    </Button>
                </div>

                <div className="grid max-w-5xl gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>Gmail</CardTitle>
                            <CardDescription>
                                Authorize this application with Google. No
                                Gmail password is required.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button asChild className="w-full">
                                <a href={oauthRedirect.url('gmail')}>
                                    <GoogleIcon />
                                    Connect with Google
                                </a>
                            </Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Outlook</CardTitle>
                            <CardDescription>
                                Authorize this application with Microsoft. No
                                Outlook password is required.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button asChild className="w-full">
                                <a href={oauthRedirect.url('outlook')}>
                                    <MicrosoftIcon />
                                    Connect with Microsoft
                                </a>
                            </Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Other / SMTP</CardTitle>
                            <CardDescription>
                                Enter SMTP server details for a custom mail
                                server.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button asChild variant="outline" className="w-full">
                                <Link href={create.url()}>
                                    <Mail />
                                    Enter SMTP details
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <p className="text-muted-foreground flex max-w-5xl items-start gap-2 text-sm">
                    <Mail className="mt-0.5 size-4 shrink-0" />
                    Gmail and Outlook use OAuth access tokens stored encrypted
                    on the server. Client secrets stay in the application
                    environment and are never sent to the browser.
                </p>
            </div>
        </>
    );
}

function GoogleIcon() {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path
                fill="#4285F4"
                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
            />
            <path
                fill="#34A853"
                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
            />
            <path
                fill="#FBBC05"
                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
            />
            <path
                fill="#EA4335"
                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
            />
        </svg>
    );
}

function MicrosoftIcon() {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path fill="#F25022" d="M1 1h10v10H1z" />
            <path fill="#7FBA00" d="M13 1h10v10H13z" />
            <path fill="#00A4EF" d="M1 13h10v10H1z" />
            <path fill="#FFB900" d="M13 13h10v10H13z" />
        </svg>
    );
}

EmailAccountConnect.layout = {
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
            title: 'Connect account',
            href: index(),
        },
    ],
};
