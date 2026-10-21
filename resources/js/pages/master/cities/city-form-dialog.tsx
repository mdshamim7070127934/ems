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
import { store, update } from '@/routes/master/cities';
import type {
    CountryOption,
    MasterCity,
    ModuleStatus,
    StateOption,
} from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    city?: MasterCity | null;
    countryOptions: CountryOption[];
    stateOptions: StateOption[];
};

const emptyForm = {
    country_id: '',
    state_id: '',
    name: '',
    status: 'active' as ModuleStatus,
};

export default function CityFormDialog({
    open,
    onOpenChange,
    city = null,
    countryOptions,
    stateOptions,
}: Props) {
    const isEditing = city !== null;
    const { data, setData, post, put, processing, errors, reset, clearErrors } =
        useForm(emptyForm);

    const selectableCountries = useMemo(() => {
        if (
            !city ||
            countryOptions.some((country) => country.id === city.country_id)
        ) {
            return countryOptions;
        }

        return [
            { id: city.country_id, name: city.country_name },
            ...countryOptions,
        ];
    }, [city, countryOptions]);

    const selectableStates = useMemo(() => {
        const filtered = stateOptions.filter(
            (state) => String(state.country_id) === data.country_id,
        );

        if (
            !city ||
            String(city.country_id) !== data.country_id ||
            filtered.some((state) => state.id === city.state_id)
        ) {
            return filtered;
        }

        return [
            {
                id: city.state_id,
                country_id: city.country_id,
                name: city.state_name,
            },
            ...filtered,
        ];
    }, [city, data.country_id, stateOptions]);

    useEffect(() => {
        if (!open) {
            return;
        }

        clearErrors();

        if (city) {
            setData({
                country_id: String(city.country_id),
                state_id: String(city.state_id),
                name: city.name,
                status: city.status,
            });

            return;
        }

        reset();
        setData(emptyForm);
    }, [city, open]);

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        };

        if (isEditing && city) {
            put(update.url(city.id), options);

            return;
        }

        post(store.url(), options);
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="flex max-h-[90vh] flex-col overflow-visible sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Edit city' : 'Add city'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEditing
                            ? 'Update this master city.'
                            : 'Create a new master city.'}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4">
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
                                setData({
                                    ...data,
                                    country_id: value,
                                    state_id: '',
                                })
                            }
                        />
                        <InputError message={errors.country_id} />
                    </div>

                    <div className="relative z-10 grid gap-2">
                        <Label htmlFor="state_id">State</Label>
                        <SearchableSelect
                            id="state_id"
                            value={data.state_id}
                            disabled={processing || data.country_id === ''}
                            placeholder={
                                data.country_id === ''
                                    ? 'Select country first'
                                    : 'Select state'
                            }
                            searchPlaceholder="Type to search state..."
                            emptyText={
                                data.country_id === ''
                                    ? 'Select a country first.'
                                    : 'No states found.'
                            }
                            options={selectableStates.map((state) => ({
                                value: String(state.id),
                                label: state.name,
                            }))}
                            onValueChange={(value) =>
                                setData('state_id', value)
                            }
                        />
                        <InputError message={errors.state_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            value={data.name}
                            onChange={(event) =>
                                setData('name', event.target.value)
                            }
                            required
                            maxLength={100}
                            disabled={processing}
                            placeholder="City name"
                        />
                        <InputError message={errors.name} />
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
