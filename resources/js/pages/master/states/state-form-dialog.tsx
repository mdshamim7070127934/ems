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
import { store, update } from '@/routes/master/states';
import type { CountryOption, MasterState, ModuleStatus } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    state?: MasterState | null;
    countryOptions: CountryOption[];
};

const emptyForm = {
    country_id: '',
    name: '',
    status: 'active' as ModuleStatus,
};

export default function StateFormDialog({
    open,
    onOpenChange,
    state = null,
    countryOptions,
}: Props) {
    const isEditing = state !== null;
    const { data, setData, post, put, processing, errors, reset, clearErrors } =
        useForm(emptyForm);

    const selectableCountries = useMemo(() => {
        if (
            !state ||
            countryOptions.some((country) => country.id === state.country_id)
        ) {
            return countryOptions;
        }

        return [
            { id: state.country_id, name: state.country_name },
            ...countryOptions,
        ];
    }, [countryOptions, state]);

    useEffect(() => {
        if (!open) {
            return;
        }

        clearErrors();

        if (state) {
            setData({
                country_id: String(state.country_id),
                name: state.name,
                status: state.status,
            });

            return;
        }

        reset();
        setData(emptyForm);
    }, [open, state]);

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        };

        if (isEditing && state) {
            put(update.url(state.id), options);

            return;
        }

        post(store.url(), options);
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="flex max-h-[90vh] flex-col overflow-visible sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Edit state' : 'Add state'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEditing
                            ? 'Update the state name, country, and status.'
                            : 'Create a new master state.'}
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
                                setData('country_id', value)
                            }
                        />
                        <InputError message={errors.country_id} />
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
                            autoFocus
                            maxLength={100}
                            disabled={processing}
                            placeholder="State name"
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
