import { useForm } from '@inertiajs/react';
import { useEffect, useMemo, type FormEvent } from 'react';
import InputError from '@/components/input-error';
import SearchableSelect from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { store, update } from '@/routes/master/branches';
import type {
    CountryOption,
    GroupOption,
    MasterBranch,
    ModuleStatus,
    RegionOption,
    UserOption,
} from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    branch?: MasterBranch | null;
    countryOptions: CountryOption[];
    regionOptions: RegionOption[];
    groupOptions: GroupOption[];
    userOptions: UserOption[];
};

const emptyForm = {
    branch_name: '',
    branch_code: '',
    country_id: '',
    region_id: '',
    group_id: '',
    branch_manager: '',
    owner_id: 'none',
    branch_country_code: '',
    branch_fbcountry_code: '',
    geofencing: false,
    geofencing_range: '',
    man_power: '',
    retail_man_power: '',
    status: 'active' as ModuleStatus,
};

export default function BranchFormDialog({
    open,
    onOpenChange,
    branch = null,
    countryOptions,
    regionOptions,
    groupOptions,
    userOptions,
}: Props) {
    const isEditing = branch !== null;
    const { data, setData, post, put, processing, errors, reset, clearErrors } =
        useForm(emptyForm);

    const selectableCountries = useMemo(() => {
        if (
            !branch ||
            countryOptions.some((country) => country.id === branch.country_id)
        ) {
            return countryOptions;
        }

        return [
            { id: branch.country_id, name: branch.country_name },
            ...countryOptions,
        ];
    }, [branch, countryOptions]);

    const selectableRegions = useMemo(() => {
        const filtered = regionOptions.filter(
            (region) => String(region.country_id) === data.country_id,
        );

        if (
            !branch ||
            String(branch.country_id) !== data.country_id ||
            filtered.some((region) => region.id === branch.region_id)
        ) {
            return filtered;
        }

        return [
            {
                id: branch.region_id,
                country_id: branch.country_id,
                name: branch.region_name,
            },
            ...filtered,
        ];
    }, [branch, data.country_id, regionOptions]);

    const selectableGroups = useMemo(() => {
        if (
            !branch ||
            groupOptions.some((group) => group.id === branch.group_id)
        ) {
            return groupOptions;
        }

        return [
            { id: branch.group_id, name: branch.group_name },
            ...groupOptions,
        ];
    }, [branch, groupOptions]);

    const selectableUsers = useMemo(() => {
        const missing: UserOption[] = [];

        if (
            branch &&
            !userOptions.some((user) => user.id === branch.branch_manager)
        ) {
            missing.push({
                id: branch.branch_manager,
                name: branch.manager_name,
            });
        }

        if (
            branch?.owner_id &&
            !userOptions.some((user) => user.id === branch.owner_id)
        ) {
            missing.push({
                id: branch.owner_id,
                name: branch.owner_name ?? '',
            });
        }

        return [...missing, ...userOptions];
    }, [branch, userOptions]);

    useEffect(() => {
        if (!open) {
            return;
        }

        clearErrors();

        if (branch) {
            setData({
                branch_name: branch.branch_name,
                branch_code: branch.branch_code,
                country_id: String(branch.country_id),
                region_id: String(branch.region_id),
                group_id: String(branch.group_id),
                branch_manager: String(branch.branch_manager),
                owner_id:
                    branch.owner_id === null ? 'none' : String(branch.owner_id),
                branch_country_code: branch.branch_country_code ?? '',
                branch_fbcountry_code: branch.branch_fbcountry_code,
                geofencing: branch.geofencing,
                geofencing_range: branch.geofencing_range ?? '',
                man_power:
                    branch.man_power === null ? '' : String(branch.man_power),
                retail_man_power:
                    branch.retail_man_power === null
                        ? ''
                        : String(branch.retail_man_power),
                status: branch.status,
            });

            return;
        }

        reset();
        setData(emptyForm);
    }, [branch, open]);

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        };

        if (isEditing && branch) {
            put(update.url(branch.id), options);

            return;
        }

        post(store.url(), options);
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="flex max-h-[90vh] flex-col overflow-hidden sm:max-w-2xl">
                <DialogHeader className="shrink-0">
                    <DialogTitle>
                        {isEditing ? 'Edit branch' : 'Add branch'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEditing
                            ? 'Update this master branch.'
                            : 'Create a new master branch.'}
                    </DialogDescription>
                </DialogHeader>

                <form
                    onSubmit={submit}
                    className="flex min-h-0 flex-1 flex-col gap-4"
                >
                    <div className="grid min-h-0 flex-1 content-start gap-4 overflow-y-auto pr-1 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="branch_name">Branch name</Label>
                            <Input
                                id="branch_name"
                                value={data.branch_name}
                                onChange={(event) =>
                                    setData('branch_name', event.target.value)
                                }
                                required
                                autoFocus
                                maxLength={100}
                                disabled={processing}
                                placeholder="Branch name"
                            />
                            <InputError message={errors.branch_name} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="branch_code">Branch code</Label>
                            <Input
                                id="branch_code"
                                value={data.branch_code}
                                onChange={(event) =>
                                    setData('branch_code', event.target.value)
                                }
                                required
                                maxLength={50}
                                disabled={processing}
                                placeholder="BR001"
                            />
                            <InputError message={errors.branch_code} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="country_id">Country</Label>
                            <SearchableSelect
                                id="country_id"
                                value={data.country_id}
                                disabled={processing}
                                placeholder="Select country"
                                searchPlaceholder="Type to search country..."
                                emptyText="No countries found."
                                options={selectableCountries.map((country) => ({
                                    value: String(country.id),
                                    label: country.name,
                                }))}
                                onValueChange={(value) =>
                                    setData({
                                        ...data,
                                        country_id: value,
                                        region_id: '',
                                    })
                                }
                            />
                            <InputError message={errors.country_id} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="region_id">Region</Label>
                            <SearchableSelect
                                id="region_id"
                                value={data.region_id}
                                disabled={processing || data.country_id === ''}
                                placeholder={
                                    data.country_id === ''
                                        ? 'Select country first'
                                        : 'Select region'
                                }
                                searchPlaceholder="Type to search region..."
                                emptyText={
                                    data.country_id === ''
                                        ? 'Select a country first.'
                                        : 'No regions found.'
                                }
                                options={selectableRegions.map((region) => ({
                                    value: String(region.id),
                                    label: region.name,
                                }))}
                                onValueChange={(value) =>
                                    setData('region_id', value)
                                }
                            />
                            <InputError message={errors.region_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="group_id">Group</Label>
                            <SearchableSelect
                                id="group_id"
                                value={data.group_id}
                                disabled={processing}
                                placeholder="Select group"
                                searchPlaceholder="Type to search group..."
                                emptyText="No groups found."
                                options={selectableGroups.map((group) => ({
                                    value: String(group.id),
                                    label: group.name,
                                }))}
                                onValueChange={(value) =>
                                    setData('group_id', value)
                                }
                            />
                            <InputError message={errors.group_id} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="status">Status</Label>
                            <Select
                                value={data.status}
                                onValueChange={(value) => {
                                    if (
                                        value === 'active' ||
                                        value === 'inactive'
                                    ) {
                                        setData('status', value);
                                    }
                                }}
                                disabled={processing}
                            >
                                <SelectTrigger id="status" className="w-full">
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="active">
                                        Active
                                    </SelectItem>
                                    <SelectItem value="inactive">
                                        Inactive
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={errors.status} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="branch_manager">
                                Branch manager
                            </Label>
                            <SearchableSelect
                                id="branch_manager"
                                value={data.branch_manager}
                                disabled={processing}
                                placeholder="Select manager"
                                searchPlaceholder="Type to search manager..."
                                emptyText="No users found."
                                options={selectableUsers.map((user) => ({
                                    value: String(user.id),
                                    label: user.name,
                                }))}
                                onValueChange={(value) =>
                                    setData('branch_manager', value)
                                }
                            />
                            <InputError message={errors.branch_manager} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="owner_id">Owner</Label>
                            <SearchableSelect
                                id="owner_id"
                                value={data.owner_id}
                                disabled={processing}
                                placeholder="Select owner"
                                searchPlaceholder="Type to search owner..."
                                emptyText="No users found."
                                options={[
                                    { value: 'none', label: 'None' },
                                    ...selectableUsers.map((user) => ({
                                        value: String(user.id),
                                        label: user.name,
                                    })),
                                ]}
                                onValueChange={(value) =>
                                    setData('owner_id', value)
                                }
                            />
                            <InputError message={errors.owner_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="branch_country_code">
                                Branch country code
                            </Label>
                            <Input
                                id="branch_country_code"
                                value={data.branch_country_code}
                                onChange={(event) =>
                                    setData(
                                        'branch_country_code',
                                        event.target.value,
                                    )
                                }
                                maxLength={255}
                                disabled={processing}
                                placeholder="Optional"
                            />
                            <InputError message={errors.branch_country_code} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="branch_fbcountry_code">
                                Branch FB country code
                            </Label>
                            <Input
                                id="branch_fbcountry_code"
                                value={data.branch_fbcountry_code}
                                onChange={(event) =>
                                    setData(
                                        'branch_fbcountry_code',
                                        event.target.value,
                                    )
                                }
                                required
                                maxLength={100}
                                disabled={processing}
                                placeholder="IN"
                            />
                            <InputError
                                message={errors.branch_fbcountry_code}
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="man_power">Man power</Label>
                            <Input
                                id="man_power"
                                type="number"
                                min={0}
                                value={data.man_power}
                                onChange={(event) =>
                                    setData('man_power', event.target.value)
                                }
                                disabled={processing}
                                placeholder="Optional"
                            />
                            <InputError message={errors.man_power} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="retail_man_power">
                                Retail man power
                            </Label>
                            <Input
                                id="retail_man_power"
                                type="number"
                                min={0}
                                value={data.retail_man_power}
                                onChange={(event) =>
                                    setData(
                                        'retail_man_power',
                                        event.target.value,
                                    )
                                }
                                disabled={processing}
                                placeholder="Optional"
                            />
                            <InputError message={errors.retail_man_power} />
                        </div>

                        <div className="grid content-start gap-2">
                            <Label htmlFor="geofencing">Geofencing</Label>
                            <div className="flex min-h-9 items-center gap-2">
                                <Checkbox
                                    id="geofencing"
                                    checked={data.geofencing}
                                    onCheckedChange={(checked) =>
                                        setData('geofencing', checked === true)
                                    }
                                    disabled={processing}
                                />
                                <Label
                                    htmlFor="geofencing"
                                    className="font-normal"
                                >
                                    Enable geofencing
                                </Label>
                            </div>
                            <InputError message={errors.geofencing} />
                        </div>
                        {data.geofencing && (
                            <div className="grid gap-2">
                                <Label htmlFor="geofencing_range">
                                    Geofencing range
                                </Label>
                                <Input
                                    id="geofencing_range"
                                    value={data.geofencing_range}
                                    onChange={(event) =>
                                        setData(
                                            'geofencing_range',
                                            event.target.value,
                                        )
                                    }
                                    required
                                    maxLength={50}
                                    disabled={processing}
                                    placeholder="Range"
                                />
                                <InputError message={errors.geofencing_range} />
                            </div>
                        )}
                    </div>

                    <DialogFooter className="shrink-0 border-t pt-4">
                        <DialogClose asChild>
                            <Button
                                type="button"
                                variant="secondary"
                                disabled={processing}
                            >
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            {isEditing ? 'Update' : 'Save'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
