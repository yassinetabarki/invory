import { useForm } from '@inertiajs/react';
import { useId, useRef } from 'react';
import type { FormEvent, RefObject } from 'react';
import { FormField } from '@/components/materials/form-field';
import { useFocusFirstInvalid } from '@/components/materials/use-focus-first-invalid';
import { PositionField } from '@/components/settings/reference-fields';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import { store, update } from '@/routes/settings/sizes';
import type { SizeRow } from '@/types/domain';

/** `position` stays the typed text; empty means "last" and the server reads it as nothing. */
type SizeFormData = { label: string; position: string };

type SizeFormDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** The size being edited; null when adding. */
    size: SizeRow | null;
    returnFocusTo?: RefObject<HTMLElement | null>;
};

const ONE_SIZE_LINE =
    'The size for products without sizes. A product that has it has no other size.';

/** How a size is named inside a sentence: a name of digits only reads "size 36". */
function sizeInSentence(label: string): string {
    return /^\d+$/.test(label) ? `size ${label}` : label;
}

/** The sentence that tells how many products a new name reaches. */
function usageSentence(size: SizeRow, locale: string): string {
    const count = formatCount(size.usage_count, locale);
    const one = size.usage_count === 1;

    return `A new name shows on the ${count} ${
        one ? 'product that uses' : 'products that use'
    } ${sizeInSentence(size.label)}, and in ${
        one ? 'its' : 'their'
    } variant SKUs. Barcodes don't change.`;
}

type FormProps = Pick<SizeFormDialogProps, 'onOpenChange' | 'size'>;

function SizeForm({ onOpenChange, size }: FormProps) {
    const uid = useId();
    const { locale } = useAppSettings();
    const formRef = useRef<HTMLFormElement>(null);
    const form = useForm<SizeFormData>({
        label: size?.label ?? '',
        position: size ? String(size.position) : '',
    });
    const { data, errors, processing } = form;

    useFocusFirstInvalid(formRef, errors, processing);

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        form.submit(size ? update(size.id) : store(), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    }

    return (
        <form
            ref={formRef}
            noValidate
            onSubmit={submit}
            className="flex min-h-0 flex-1 flex-col gap-4"
        >
            {/* The middle scrolls; the title and the buttons stay on screen. */}
            <div className="-mx-1 flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto px-1">
                <FormField
                    id={`${uid}-label`}
                    label="Name"
                    required
                    hint={
                        size && size.usage_count > 0
                            ? usageSentence(size, locale)
                            : undefined
                    }
                    error={errors.label}
                >
                    {(control) => (
                        <Input
                            {...control}
                            value={data.label}
                            onChange={(event) =>
                                form.setData('label', event.target.value)
                            }
                            maxLength={12}
                            autoComplete="off"
                            className="sm:w-48"
                        />
                    )}
                </FormField>

                <PositionField
                    id={`${uid}-position`}
                    value={data.position}
                    onChange={(value) => form.setData('position', value)}
                    error={errors.position}
                />
            </div>

            <DialogFooter className="shrink-0">
                <Button
                    type="button"
                    variant="outline"
                    className="h-11 sm:h-9"
                    disabled={processing}
                    onClick={() => onOpenChange(false)}
                >
                    Cancel
                </Button>
                <Button
                    type="submit"
                    className="h-11 sm:h-9"
                    disabled={processing}
                >
                    {processing && <Spinner />}
                    Save size
                </Button>
            </DialogFooter>
        </form>
    );
}

/**
 * Add a size or edit one. The form lives inside the dialog content, so it starts
 * fresh every time the dialog opens; it stays open on server errors and closes
 * when the write succeeds.
 */
export function SizeFormDialog({
    open,
    onOpenChange,
    size,
    returnFocusTo,
}: SizeFormDialogProps) {
    const described = size?.one_size === true;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent
                // At most the screen's height: the form's middle scrolls, the title and the buttons stay in view.
                className="flex max-h-[calc(100dvh-2rem)] max-w-full flex-col sm:max-w-md"
                // Only the One size row has a line under its title.
                {...(described ? {} : { 'aria-describedby': undefined })}
                onCloseAutoFocus={(event) => {
                    if (returnFocusTo?.current?.isConnected) {
                        event.preventDefault();
                        returnFocusTo.current.focus();
                    }
                }}
            >
                <DialogHeader className="shrink-0">
                    <DialogTitle>
                        {size ? `Edit ${size.label}` : 'Add size'}
                    </DialogTitle>
                    {described && (
                        <DialogDescription>{ONE_SIZE_LINE}</DialogDescription>
                    )}
                </DialogHeader>
                <SizeForm onOpenChange={onOpenChange} size={size} />
            </DialogContent>
        </Dialog>
    );
}
