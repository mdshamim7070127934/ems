import { useForm } from '@inertiajs/react';
import { useEffect, type FormEvent } from 'react';
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
import { store, update } from '@/routes/master/countries';
import type { CountryStatus, MasterCountry, TimezoneOption } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    country?: MasterCountry | null;
    timezoneOptions: TimezoneOption[];
};

const emptyForm = {
    sortname: '',
    name: '',
    currency: '',
    phonecode: '',
    timezone_id: 'none',
    status: 'inactive' as CountryStatus,
};

export default function CountryFormDialog({
    open,
    onOpenChange,
    country = null,
    timezoneOptions,
}: Props) {
    const isEditing = country !== null;
    const { data, setData, post, put, processing, errors, reset, clearErrors } =
        useForm(emptyForm);

    const selectableTimezones = timezoneOptions.some(
        (timezone) => timezone.id === country?.timezone_id,
    )
        ? timezoneOptions
        : country?.timezone_id
          ? [
                {
                    id: country.timezone_id,
                    name: country.timezone_name ?? '',
                    display_name: country.timezone_display_name ?? '',
                    utc_offset: '',
                },
                ...timezoneOptions,
            ]
          : timezoneOptions;

    useEffect(() => {
        if (!open) {
            return;
        }

        clearErrors();

        if (country) {
            setData({
                sortname: country.sortname,
                name: country.name,
                currency: country.currency,
                phonecode: String(country.phonecode),
                timezone_id:
                    country.timezone_id === null
                        ? 'none'
                        : String(country.timezone_id),
                status: country.status,
            });

            return;
        }

        reset();
        setData(emptyForm);
    }, [country, open]);

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        };

        if (isEditing && country) {
            put(update.url(country.id), options);

            return;
        }

        post(store.url(), options);
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="flex max-h-[90vh] flex-col overflow-visible sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Edit country' : 'Add country'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEditing
                            ? 'Update this master country.'
                            : 'Create a new master country.'}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4">
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
                            autoFocus
                            maxLength={150}
                            disabled={processing}
                            placeholder="Country name"
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2 sm:grid-cols-2 sm:gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="sortname">Sort name</Label>
                            <Input
                                id="sortname"
                                name="sortname"
                                value={data.sortname}
                                onChange={(event) =>
                                    setData('sortname', event.target.value)
                                }
                                required
                                maxLength={3}
                                disabled={processing}
                                placeholder="IN"
                            />
                            <InputError message={errors.sortname} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="phonecode">Phone code</Label>
                            <Input
                                id="phonecode"
                                name="phonecode"
                                type="number"
                                value={data.phonecode}
                                onChange={(event) =>
                                    setData('phonecode', event.target.value)
                                }
                                required
                                disabled={processing}
                                placeholder="91"
                            />
                            <InputError message={errors.phonecode} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="currency">Currency</Label>
                        <Input
                            id="currency"
                            name="currency"
                            value={data.currency}
                            onChange={(event) =>
                                setData('currency', event.target.value)
                            }
                            required
                            maxLength={100}
                            disabled={processing}
                            placeholder="INR"
                        />
                        <InputError message={errors.currency} />
                    </div>

                    <div className="relative z-20 grid gap-2">
                        <Label htmlFor="timezone_id">Timezone</Label>
                        <SearchableSelect
                            id="timezone_id"
                            value={data.timezone_id}
                            disabled={processing}
                            placeholder="Search timezone"
                            searchPlaceholder="Type to search timezone..."
                            emptyText="No timezones found."
                            options={[
                                { value: 'none', label: 'None' },
                                ...selectableTimezones.map((timezone) => ({
                                    value: String(timezone.id),
                                    label:
                                        timezone.utc_offset !== ''
                                            ? `${timezone.utc_offset} ${timezone.name}`
                                            : timezone.name,
                                })),
                            ]}
                            onValueChange={(value) =>
                                setData('timezone_id', value)
                            }
                        />
                        <InputError message={errors.timezone_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="status">Status</Label>
                        <Select
                            value={data.status}
                            onValueChange={(value) => {
                                if (
                                    value === 'active' ||
                                    value === 'inactive' ||
                                    value === 'delete'
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
                                <SelectItem value="delete">Delete</SelectItem>
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
