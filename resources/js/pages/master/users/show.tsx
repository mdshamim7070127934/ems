import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { edit, index } from '@/routes/master/users';
import type { MasterUser } from '@/types';

type Props = {
    user: MasterUser;
};

export default function UserShow({ user }: Props) {
    return (
        <>
            <Head title={`View user - ${user.name}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="View user"
                        description="Read-only details for this application user."
                    />
                    <div className="flex gap-2 sm:mt-1">
                        <Button asChild>
                            <Link href={edit.url(user.id)}>Edit user</Link>
                        </Button>
                        <Button asChild variant="outline">
                            <Link href={index.url()}>Back to users</Link>
                        </Button>
                    </div>
                </div>

                <dl className="grid max-w-3xl gap-4 text-sm sm:grid-cols-2">
                    <div className="grid gap-1">
                        <dt className="text-muted-foreground">First name</dt>
                        <dd className="font-medium">{user.first_name ?? '—'}</dd>
                    </div>
                    <div className="grid gap-1">
                        <dt className="text-muted-foreground">Last name</dt>
                        <dd className="font-medium">{user.last_name ?? '—'}</dd>
                    </div>
                    <div className="grid gap-1">
                        <dt className="text-muted-foreground">Email</dt>
                        <dd className="font-medium">{user.email}</dd>
                    </div>
                    <div className="grid gap-1">
                        <dt className="text-muted-foreground">Username</dt>
                        <dd className="font-medium">{user.username ?? '—'}</dd>
                    </div>
                    <div className="grid gap-1">
                        <dt className="text-muted-foreground">Role</dt>
                        <dd className="font-medium">{user.role_name ?? '—'}</dd>
                    </div>
                    <div className="grid gap-1">
                        <dt className="text-muted-foreground">Branch</dt>
                        <dd className="font-medium">
                            {user.branch_name ?? '—'}
                        </dd>
                    </div>
                    <div className="grid gap-1">
                        <dt className="text-muted-foreground">
                            Reporting person
                        </dt>
                        <dd className="font-medium">
                            {user.reporting_person_name ?? '—'}
                        </dd>
                    </div>
                    <div className="grid gap-1">
                        <dt className="text-muted-foreground">Status</dt>
                        <dd>
                            <Badge
                                variant={
                                    user.status === 'active'
                                        ? 'default'
                                        : 'secondary'
                                }
                            >
                                {user.status_label}
                            </Badge>
                        </dd>
                    </div>
                    <div className="grid gap-1 sm:col-span-2">
                        <dt className="text-muted-foreground">
                            Extra branches
                        </dt>
                        <dd className="font-medium">
                            {user.extra_branch_names || '—'}
                        </dd>
                    </div>
                    <div className="grid gap-1 sm:col-span-2">
                        <dt className="text-muted-foreground">
                            Excluded branches
                        </dt>
                        <dd className="font-medium">
                            {user.exclude_branch_names || '—'}
                        </dd>
                    </div>
                    <div className="grid gap-1">
                        <dt className="text-muted-foreground">Last login</dt>
                        <dd className="font-medium">{user.last_login ?? '—'}</dd>
                    </div>
                    <div className="grid gap-1">
                        <dt className="text-muted-foreground">Created date</dt>
                        <dd className="font-medium">{user.created_at}</dd>
                    </div>
                    <div className="grid gap-1">
                        <dt className="text-muted-foreground">Updated date</dt>
                        <dd className="font-medium">{user.updated_at}</dd>
                    </div>
                </dl>
            </div>
        </>
    );
}

UserShow.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Users',
            href: index(),
        },
        {
            title: 'View user',
            href: index(),
        },
    ],
};
