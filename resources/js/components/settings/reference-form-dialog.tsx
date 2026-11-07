import { useForm } from '@inertiajs/react';
import { useEffect, useId, useRef } from 'react';
import type { FormEvent, RefObject } from 'react';
import { useFocusFirstInvalid } from '@/components/materials/use-focus-first-invalid';
import { SizesField } from '@/components/products/sizes-field';
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
import type {
    ReferenceFormData,
    ReferenceRow,
    SizeOption,
} from '@/types/domain';

type ReferenceFormDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** The category being edited; null when adding. */
    row: (ReferenceRow & { suggested_size_ids?: number[] }) | null;
    returnFocusTo?: RefObject<HTMLElement | null>;
    /** Lower-case name for sentences ("material category"). */
    singular: string;
    /** Longest code the server accepts (32 for categories). */
    codeMaxLength: number;
    urls: { store: string; update: (id: number) => string };
    /** Product categories only: the workshop's sizes, for "Suggested sizes" on Edit. */
    sizes?: SizeOption[];
};

type ReferenceFormValues = ReferenceFormData & { suggested_sizes: number[] };

/**
 * What the server accepts: `code` on create only (it is prohibited on update),
 * `position` only when one was typed (empty means "last"), and
 * `suggested_sizes` when the Suggested sizes field is shown (product categories, Edit).
 */
function toPayload(
    data: ReferenceFormValues,
    creating: boolean,
    withSizes: boolean,
) {
    const position = data.position.trim();

    return {
        ...(creating ? { code: data.code } : {}),
        name: data.name,
        ...(withSizes ? { suggested_sizes: data.suggested_sizes } : {}),
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
    sizes,
}: FormProps) {
    const uid = useId();
    const containerRef = useRef<HTMLFormElement>(null);
    const creating = row === null;
    const withSizes = sizes !== undefined && row !== null;
    const form = useForm<ReferenceFormValues>({
        code: '',
        name: row?.name ?? '',
        position: row ? String(row.position) : '',
        suggested_sizes: row?.suggested_size_ids ?? [],
    });
    const { errors, processing } = form;
    const sizesError = errors.suggested_sizes;
    const otherInvalid = errors.name ?? errors.position;

    useFocusFirstInvalid(containerRef, errors, processing);

    // The sizes' first button carries `data-invalid`, not `aria-invalid`; it takes the
    // focus when nothing above it is invalid.
    useEffect(() => {
        if (processing || !sizesError || otherInvalid) {
            return;
        }

        containerRef.current
            ?.querySelector<HTMLElement>('[data-invalid="true"]')
            ?.focus();
    }, [sizesError, otherInvalid, processing]);

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        form.transform((data) => toPayload(data, creating, withSizes));

        const options = {
            preserveScroll: true,
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
            className="flex min-h-0 flex-1 flex-col gap-4"
        >
            {/* The middle scrolls; the title and the buttons stay on screen. */}
            <div className="-mx-1 flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto px-1">
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
                {withSizes && (
                    <SizesField
                        id={`${uid}-sizes`}
                        anchorId={`${uid}-suggested-sizes`}
                        label="Suggested sizes"
                        sizes={sizes}
                        value={form.data.suggested_sizes}
                        onChange={(ids) => form.setData('suggested_sizes', ids)}
                        hint="Add product pre-selects these sizes when you choose this category."
                        error={sizesError ? [sizesError] : []}
                    />
                )}
            </div>
            <DialogFooter className="shrink-0">
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
                // At most the screen's height: the form's middle scrolls, the title and the buttons stay in view.
                className="flex max-h-[calc(100dvh-2rem)] flex-col"
                onCloseAutoFocus={(event) => {
                    if (returnFocusTo?.current?.isConnected) {
                        event.preventDefault();
                        returnFocusTo.current.focus();
                    }
                }}
            >
                <DialogHeader className="shrink-0">
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
