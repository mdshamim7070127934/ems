import { useForm } from '@inertiajs/react';
import { useEffect, useMemo, type FormEvent } from 'react';
import InputError from '@/components/input-error';
import SearchableSelect from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
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
import { store, update } from '@/routes/master/regions';
import type {
    CountryOption,
    MasterRegion,
    ModuleStatus,
    UserOption,
} from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    region?: MasterRegion | null;
    countryOptions: CountryOption[];
    userOptions: UserOption[];
};

const emptyForm = {
    region_name: '',
    country_id: '',
    owner_id: '',
    status: 'active' as ModuleStatus,
};

export default function RegionFormDialog({
    open,
    onOpenChange,
    region = null,
    countryOptions,
    userOptions,
}: Props) {
    const isEditing = region !== null;
    const { data, setData, post, put, processing, errors, reset, clearErrors } =
        useForm(emptyForm);

    const selectableCountries = useMemo(() => {
        if (
            !region ||
            countryOptions.some((country) => country.id === region.country_id)
        ) {
            return countryOptions;
        }

        return [
            { id: region.country_id, name: region.country_name },
            ...countryOptions,
        ];
    }, [countryOptions, region]);

    const selectableUsers = useMemo(() => {
        if (
            !region ||
            userOptions.some((user) => user.id === region.owner_id)
        ) {
            return userOptions;
        }

        return [
            { id: region.owner_id, name: region.owner_name },
            ...userOptions,
        ];
    }, [region, userOptions]);

    useEffect(() => {
        if (!open) {
            return;
        }

        clearErrors();

        if (region) {
            setData({
                region_name: region.region_name,
                country_id: String(region.country_id),
                owner_id: String(region.owner_id),
                status: region.status,
            });

            return;
        }

        reset();
        setData(emptyForm);
    }, [open, region]);

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        };

        if (isEditing && region) {
            put(update.url(region.id), options);

            return;
        }

        post(store.url(), options);
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="flex max-h-[90vh] flex-col overflow-visible sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Edit region' : 'Add region'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEditing
                            ? 'Update this master region.'
                            : 'Create a new master region.'}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="region_name">Region name</Label>
                        <Input
                            id="region_name"
                            name="region_name"
                            value={data.region_name}
                            onChange={(event) =>
                                setData('region_name', event.target.value)
                            }
                            required
                            autoFocus
                            maxLength={250}
                            disabled={processing}
                            placeholder="Region name"
                        />
                        <InputError message={errors.region_name} />
                    </div>

                    <div className="relative z-20 grid gap-2">
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
                                setData('country_id', value)
                            }
                        />
                        <InputError message={errors.country_id} />
                    </div>

                    <div className="relative z-10 grid gap-2">
                        <Label htmlFor="owner_id">Owner</Label>
                        <SearchableSelect
                            id="owner_id"
                            value={data.owner_id}
                            disabled={processing}
                            placeholder="Select owner"
                            searchPlaceholder="Type to search owner..."
                            emptyText="No users found."
                            options={selectableUsers.map((user) => ({
                                value: String(user.id),
                                label: user.name,
                            }))}
                            onValueChange={(value) =>
                                setData('owner_id', value)
                            }
                        />
                        <InputError message={errors.owner_id} />
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
                                <SelectItem value="active">Active</SelectItem>
                                <SelectItem value="inactive">
                                    Inactive
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.status} />
                    </div>

                    <DialogFooter>
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
