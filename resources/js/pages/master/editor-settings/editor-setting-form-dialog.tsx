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
import { cn } from '@/lib/utils';
import { update } from '@/routes/master/editor-settings';
import type { MasterEditorSetting, ModuleStatus } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    editorSetting?: MasterEditorSetting | null;
};

const emptyForm = {
    name: '',
    description: '',
    sort_order: 0,
    status: 'active' as ModuleStatus,
};

export default function EditorSettingFormDialog({
    open,
    onOpenChange,
    editorSetting = null,
}: Props) {
    const { data, setData, put, processing, errors, clearErrors } =
        useForm(emptyForm);

    useEffect(() => {
        if (!open || !editorSetting) {
            return;
        }

        clearErrors();
        setData({
            name: editorSetting.name,
            description: editorSetting.description ?? '',
            sort_order: editorSetting.sort_order,
            status: editorSetting.status,
        });
    }, [open, editorSetting]);

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        if (!editorSetting) {
            return;
        }

        put(update.url(editorSetting.id), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit editor setting</DialogTitle>
                    <DialogDescription>
                        Update this editor name and status. The code cannot be
                        changed because compose uses it as the driver.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="code">Code</Label>
                        <Input
                            id="code"
                            value={editorSetting?.code ?? ''}
                            disabled
                            readOnly
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(event) =>
                                setData('name', event.target.value)
                            }
                            required
                            maxLength={100}
                            disabled={processing}
                            autoFocus
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">Description</Label>
                        <textarea
                            id="description"
                            value={data.description}
                            onChange={(event) =>
                                setData('description', event.target.value)
                            }
                            disabled={processing}
                            placeholder="Optional description"
                            rows={3}
                            className={cn(
                                'border-input placeholder:text-muted-foreground flex min-h-20 w-full rounded-md border bg-transparent px-3 py-2 text-base shadow-xs outline-none md:text-sm',
                                'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                                'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50',
                            )}
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="sort_order">Sort order</Label>
                        <Input
                            id="sort_order"
                            type="number"
                            min={0}
                            value={data.sort_order}
                            onChange={(event) =>
                                setData(
                                    'sort_order',
                                    Number(event.target.value),
                                )
                            }
                            required
                            disabled={processing}
                        />
                        <InputError message={errors.sort_order} />
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
                            disabled={processing || editorSetting?.is_default}
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
                        <Button
                            type="submit"
                            disabled={processing || !editorSetting}
                        >
                            {processing && <Spinner />}
                            Update
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
