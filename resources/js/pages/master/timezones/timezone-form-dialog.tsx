import { useForm } from '@inertiajs/react';
import { useEffect, type FormEvent } from 'react';
import InputError from '@/components/input-error';
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
import { store, update } from '@/routes/master/timezones';
import type {
    MasterTimezone,
    ModuleStatus,
    TimezoneIdentifierOption,
} from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    timezone?: MasterTimezone | null;
    identifierOptions: TimezoneIdentifierOption[];
};

const emptyForm = {
    name: '',
    display_name: '',
    utc_offset: '',
    status: 'active' as ModuleStatus,
};

export default function TimezoneFormDialog({
    open,
    onOpenChange,
    timezone = null,
    identifierOptions,
}: Props) {
    const isEditing = timezone !== null;
    const { data, setData, post, put, processing, errors, reset, clearErrors } =
        useForm(emptyForm);

    useEffect(() => {
        if (!open) {
            return;
        }

        clearErrors();

        if (timezone) {
            setData({
                name: timezone.name,
                display_name: timezone.display_name,
                utc_offset: timezone.utc_offset,
                status: timezone.status,
            });

            return;
        }

        reset();
        setData(emptyForm);
    }, [open, timezone]);

    function applyIdentifier(name: string): void {
        const option = identifierOptions.find(
            (identifier) => identifier.name === name,
        );

        setData({
            ...data,
            name,
            display_name: option?.display_name ?? name,
            utc_offset: option?.utc_offset ?? '',
        });
    }

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        };

        if (isEditing && timezone) {
            put(update.url(timezone.id), options);

            return;
        }

        post(store.url(), options);
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Edit timezone' : 'Add timezone'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEditing
                            ? 'Update this master timezone.'
                            : 'Create a new master timezone from the IANA timezone database.'}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Timezone name</Label>
                        <Select
                            value={data.name}
                            onValueChange={applyIdentifier}
                            disabled={processing}
                        >
                            <SelectTrigger id="name" className="w-full">
                                <SelectValue placeholder="Select timezone" />
                            </SelectTrigger>
                            <SelectContent>
                                {identifierOptions.map((identifier) => (
                                    <SelectItem
                                        key={identifier.name}
                                        value={identifier.name}
                                    >
                                        {identifier.utc_offset}{' '}
                                        {identifier.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="display_name">Display name</Label>
                        <Input
                            id="display_name"
                            name="display_name"
                            value={data.display_name}
                            onChange={(event) =>
                                setData('display_name', event.target.value)
                            }
                            required
                            maxLength={150}
                            disabled={processing}
                            placeholder="Asia/Kolkata"
                        />
                        <InputError message={errors.display_name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="utc_offset">UTC offset</Label>
                        <Input
                            id="utc_offset"
                            name="utc_offset"
                            value={data.utc_offset}
                            readOnly
                            disabled
                            placeholder="+05:30"
                        />
                        <InputError message={errors.utc_offset} />
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
