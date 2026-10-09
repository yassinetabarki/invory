import { useForm } from '@inertiajs/react';
import { useId, useRef } from 'react';
import type { FormEvent, RefObject } from 'react';
import { useFocusFirstInvalid } from '@/components/materials/use-focus-first-invalid';
import {
    CodeField,
    NameField,
    PositionField,
} from '@/components/settings/reference-fields';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { invalidatedBy } from '@/lib/cache-tags';
import type { ReferenceFormData, ReferenceRow } from '@/types/domain';

type ReferenceFormDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** The category being edited; null when adding. */
    row: ReferenceRow | null;
    returnFocusTo?: RefObject<HTMLElement | null>;
    /** Lower-case name for sentences ("material category"). */
    singular: string;
    /** Longest code the server accepts (32 for categories). */
    codeMaxLength: number;
    urls: { store: string; update: (id: number) => string };
};

/**
 * What the server accepts: `code` on create only (it is prohibited on update),
 * and `position` only when one was typed (empty means "last").
 */
function toPayload(data: ReferenceFormData, creating: boolean) {
    const position = data.position.trim();

    return {
        ...(creating ? { code: data.code } : {}),
        name: data.name,
        ...(position === '' ? {} : { position }),
    };
}

type FormProps = Omit<ReferenceFormDialogProps, 'open' | 'returnFocusTo'>;

function ReferenceForm({
    onOpenChange,
    row,
    singular,
    codeMaxLength,
    urls,
}: FormProps) {
    const uid = useId();
    const containerRef = useRef<HTMLFormElement>(null);
    const creating = row === null;
    const form = useForm<ReferenceFormData>({
        code: '',
        name: row?.name ?? '',
        position: row ? String(row.position) : '',
    });
    const { errors, processing } = form;

    useFocusFirstInvalid(containerRef, errors, processing);

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        form.transform((data) => toPayload(data, creating));

        const options = {
            preserveScroll: true,
            invalidateCacheTags: invalidatedBy('reference'),
            onSuccess: () => onOpenChange(false),
        };

        if (row) {
            form.put(urls.update(row.id), options);
        } else {
            form.post(urls.store, options);
        }
    }

    return (
        <form
            ref={containerRef}
            noValidate
            onSubmit={submit}
            className="flex flex-col gap-4"
        >
            {creating && (
                <CodeField
                    id={`${uid}-code`}
                    value={form.data.code}
                    onChange={(value) => form.setData('code', value)}
                    error={errors.code}
                    maxLength={codeMaxLength}
                />
            )}
            <NameField
                id={`${uid}-name`}
                value={form.data.name}
                onChange={(value) => form.setData('name', value)}
                error={errors.name}
            />
            <PositionField
                id={`${uid}-position`}
                value={form.data.position}
                onChange={(value) => form.setData('position', value)}
                error={errors.position}
            />
            <DialogFooter>
                <Button
                    type="button"
                    variant="outline"
                    disabled={processing}
                    onClick={() => onOpenChange(false)}
                >
                    Cancel
                </Button>
                <Button type="submit" disabled={processing}>
                    {processing && <Spinner />}
                    Save {singular}
                </Button>
            </DialogFooter>
        </form>
    );
}

/**
 * Create or edit a category. The form lives inside the dialog content, so it
 * starts fresh every time the dialog opens. The dialog stays open while the
 * server reports errors and closes when the write succeeds.
 */
export function ReferenceFormDialog({
    open,
    onOpenChange,
    returnFocusTo,
    ...form
}: ReferenceFormDialogProps) {
    const { row, singular } = form;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent
                onCloseAutoFocus={(event) => {
                    if (returnFocusTo?.current?.isConnected) {
                        event.preventDefault();
                        returnFocusTo.current.focus();
                    }
                }}
            >
                <DialogHeader>
                    <DialogTitle>
                        {row ? `Edit ${row.name}` : `Add ${singular}`}
                    </DialogTitle>
                    <DialogDescription>
                        {row
                            ? `Code ${row.code} can't be changed.`
                            : 'Pick a short code now: it stays the same when the name changes.'}
                    </DialogDescription>
                </DialogHeader>
                <ReferenceForm onOpenChange={onOpenChange} {...form} />
            </DialogContent>
        </Dialog>
    );
}
