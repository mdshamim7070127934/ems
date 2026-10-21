import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo, type FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import SearchableMultiSelect from '@/components/searchable-multi-select';
import SearchableSelect from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { index, store, update } from '@/routes/master/users';
import type {
    BranchOption,
    MasterUser,
    ModuleStatus,
    RoleOption,
    UserOption,
} from '@/types';

type Props = {
    user?: MasterUser | null;
    roleOptions: RoleOption[];
    branchOptions: BranchOption[];
    reportingPersonOptions: UserOption[];
};

export default function UserForm({
    user = null,
    roleOptions,
    branchOptions,
    reportingPersonOptions,
}: Props) {
    const isEditing = user !== null;
    const { data, setData, post, put, processing, errors } = useForm({
        first_name: user?.first_name ?? '',
        last_name: user?.last_name ?? '',
        email: user?.email ?? '',
        password: '',
        role_id: user?.role_id ? String(user.role_id) : '',
        branch_id: user?.branch_id ? String(user.branch_id) : '',
        reporting_person_id: user?.reporting_person_id
            ? String(user.reporting_person_id)
            : 'none',
        extra_branch_ids: user?.extra_branch_ids ?? [],
        exclude_branch_ids: user?.exclude_branch_ids ?? [],
        status: (user?.status ?? 'active') as ModuleStatus,
    });

    const roleSelectOptions = useMemo(
        () =>
            roleOptions.map((role) => ({
                value: String(role.id),
                label: role.name,
            })),
        [roleOptions],
    );

    const branchSelectOptions = useMemo(
        () =>
            branchOptions.map((branch) => ({
                value: String(branch.id),
                label: branch.name,
            })),
        [branchOptions],
    );

    const reportingSelectOptions = useMemo(
        () => [
            { value: 'none', label: 'None' },
            ...reportingPersonOptions.map((person) => ({
                value: String(person.id),
                label: person.name,
            })),
        ],
        [reportingPersonOptions],
    );

    const extraBranchOptions = useMemo(
        () =>
            branchSelectOptions.filter(
                (option) =>
                    option.value !== data.branch_id &&
                    !data.exclude_branch_ids.includes(Number(option.value)),
            ),
        [branchSelectOptions, data.branch_id, data.exclude_branch_ids],
    );

    const excludeBranchOptions = useMemo(
        () =>
            branchSelectOptions.filter(
                (option) =>
                    option.value !== data.branch_id &&
                    !data.extra_branch_ids.includes(Number(option.value)),
            ),
        [branchSelectOptions, data.branch_id, data.extra_branch_ids],
    );

    function setPrimaryBranch(value: string): void {
        const branchId = Number(value);

        setData({
            ...data,
            branch_id: value,
            extra_branch_ids: data.extra_branch_ids.filter(
                (id) => id !== branchId,
            ),
            exclude_branch_ids: data.exclude_branch_ids.filter(
                (id) => id !== branchId,
            ),
        });
    }

    function setExtraBranches(values: string[]): void {
        const ids = values.map(Number);

        setData({
            ...data,
            extra_branch_ids: ids,
            exclude_branch_ids: data.exclude_branch_ids.filter(
                (id) => !ids.includes(id),
            ),
        });
    }

    function setExcludeBranches(values: string[]): void {
        const ids = values.map(Number);

        setData({
            ...data,
            exclude_branch_ids: ids,
            extra_branch_ids: data.extra_branch_ids.filter(
                (id) => !ids.includes(id),
            ),
        });
    }

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        if (isEditing && user) {
            put(update.url(user.id));

            return;
        }

        post(store.url());
    }

    return (
        <>
            <Head title={isEditing ? 'Edit user' : 'Add user'} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title={isEditing ? 'Edit user' : 'Add user'}
                        description={
                            isEditing
                                ? 'Update user details.'
                                : 'Create a new application user.'
                        }
                    />
                    <Button asChild variant="outline" className="sm:mt-1">
                        <Link href={index.url()}>Back to users</Link>
                    </Button>
                </div>

                <form
                    onSubmit={submit}
                    className="flex max-w-3xl flex-col gap-6"
                >
                    <div className="grid gap-6 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="first_name">First name</Label>
                            <Input
                                id="first_name"
                                value={data.first_name}
                                onChange={(event) =>
                                    setData('first_name', event.target.value)
                                }
                                required
                                autoFocus={!isEditing}
                                maxLength={100}
                                disabled={processing}
                                placeholder="e.g. John"
                            />
                            <InputError message={errors.first_name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="last_name">Last name</Label>
                            <Input
                                id="last_name"
                                value={data.last_name}
                                onChange={(event) =>
                                    setData('last_name', event.target.value)
                                }
                                required
                                maxLength={100}
                                disabled={processing}
                                placeholder="e.g. Doe"
                            />
                            <InputError message={errors.last_name} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(event) =>
                                setData('email', event.target.value)
                            }
                            required
                            maxLength={255}
                            disabled={processing}
                            placeholder="user@example.com"
                        />
                        <InputError message={errors.email} />
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
                                    ? 'Leave blank to keep current password'
                                    : 'Minimum 8 characters'
                            }
                            autoComplete="new-password"
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="grid gap-6 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="role_id">Role</Label>
                            <SearchableSelect
                                id="role_id"
                                value={data.role_id}
                                options={roleSelectOptions}
                                placeholder="Select role"
                                searchPlaceholder="Search roles..."
                                disabled={processing}
                                onValueChange={(value) =>
                                    setData('role_id', value)
                                }
                            />
                            <InputError message={errors.role_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="branch_id">Branch</Label>
                            <SearchableSelect
                                id="branch_id"
                                value={data.branch_id}
                                options={branchSelectOptions}
                                placeholder="Select branch"
                                searchPlaceholder="Search branches..."
                                disabled={processing}
                                onValueChange={setPrimaryBranch}
                            />
                            <InputError message={errors.branch_id} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="reporting_person_id">
                            Reporting person
                        </Label>
                        <SearchableSelect
                            id="reporting_person_id"
                            value={data.reporting_person_id}
                            options={reportingSelectOptions}
                            placeholder="None"
                            searchPlaceholder="Search users..."
                            disabled={processing}
                            onValueChange={(value) =>
                                setData('reporting_person_id', value)
                            }
                        />
                        <InputError message={errors.reporting_person_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="extra_branch_ids">
                            Assign extra branches
                        </Label>
                        <SearchableMultiSelect
                            id="extra_branch_ids"
                            value={data.extra_branch_ids.map(String)}
                            options={extraBranchOptions}
                            placeholder="Select extra branches"
                            searchPlaceholder="Search branches..."
                            disabled={processing}
                            onValueChange={setExtraBranches}
                        />
                        <p className="text-muted-foreground text-xs">
                            Search and select one or more additional branches.
                        </p>
                        <InputError message={errors.extra_branch_ids} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="exclude_branch_ids">
                            Exclude branches
                        </Label>
                        <SearchableMultiSelect
                            id="exclude_branch_ids"
                            value={data.exclude_branch_ids.map(String)}
                            options={excludeBranchOptions}
                            placeholder="Select excluded branches"
                            searchPlaceholder="Search branches..."
                            disabled={processing}
                            onValueChange={setExcludeBranches}
                        />
                        <p className="text-muted-foreground text-xs">
                            Search and select branches this user should not
                            access.
                        </p>
                        <InputError message={errors.exclude_branch_ids} />
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="status"
                            checked={data.status === 'active'}
                            onCheckedChange={(checked) =>
                                setData(
                                    'status',
                                    checked === true ? 'active' : 'inactive',
                                )
                            }
                            disabled={processing}
                        />
                        <Label htmlFor="status" className="font-normal">
                            Active
                        </Label>
                    </div>
                    <InputError message={errors.status} />

                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            {isEditing ? 'Update user' : 'Create user'}
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

UserForm.layout = {
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
            title: 'User',
            href: index(),
        },
    ],
};
