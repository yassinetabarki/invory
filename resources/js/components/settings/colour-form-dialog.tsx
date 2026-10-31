import { useForm } from '@inertiajs/react';
import { useId, useRef, useState } from 'react';
import type { FormEvent, RefObject } from 'react';
import { FormField } from '@/components/materials/form-field';
import { useFocusFirstInvalid } from '@/components/materials/use-focus-first-invalid';
import { PositionField } from '@/components/settings/reference-fields';
import { ColourSwatch, ColourTag } from '@/components/shared/colour-tag';
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
import { cn } from '@/lib/utils';
import { store, update } from '@/routes/settings/colours';
import type { ColourFormData, ColourRow } from '@/types/domain';

const VALID_SWATCH = /^#[0-9A-F]{6}$/;

type ColourFormDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** The colour being edited; null when adding. */
    colour: ColourRow | null;
    returnFocusTo?: RefObject<HTMLElement | null>;
};

/** Capitals, and a "#" in front of six hex digits typed without one. */
function normaliseSwatch(text: string): string {
    const capitals = text.toUpperCase();

    return /^[0-9A-F]{6}$/.test(capitals) ? `#${capitals}` : capitals;
}

/**
 * What the server accepts: `code` on create only, and `position` only when one
 * was typed (empty means "last").
 */
function toPayload(data: ColourFormData, creating: boolean) {
    const position = data.position.trim();

    return {
        ...(creating ? { code: data.code } : {}),
        name: data.name,
        swatch: data.swatch,
        ...(position === '' ? {} : { position }),
    };
}

/** The sentence that tells how many products a new name or swatch reaches. */
function usageSentence(colour: ColourRow, locale: string): string {
    const count = formatCount(colour.usage_count, locale);
    const products =
        colour.usage_count === 1 ? 'product that uses' : 'products that use';

    return `A new name or swatch shows on the ${count} ${products} ${colour.name}.`;
}

type FormProps = Pick<ColourFormDialogProps, 'onOpenChange' | 'colour'>;

function ColourForm({ onOpenChange, colour }: FormProps) {
    const uid = useId();
    const { locale } = useAppSettings();
    const formRef = useRef<HTMLFormElement>(null);
    const creating = colour === null;
    const form = useForm<ColourFormData>({
        code: '',
        name: colour?.name ?? '',
        swatch: colour?.swatch ?? '',
        position: colour ? String(colour.position) : '',
    });
    const { data, errors, processing } = form;
    // The preview keeps the last text that was a colour code.
    const [shownSwatch, setShownSwatch] = useState(colour?.swatch ?? '');

    useFocusFirstInvalid(formRef, errors, processing);

    function changeSwatch(text: string) {
        const swatch = normaliseSwatch(text);

        form.setData('swatch', swatch);

        if (VALID_SWATCH.test(swatch)) {
            setShownSwatch(swatch);
        }
    }

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        form.transform((values) => toPayload(values, creating));

        form.submit(colour ? update(colour.id) : store(), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    }

    const swatchHint = [
        'Pick the colour, or type its code, such as #3E4C7E.',
        colour && colour.usage_count > 0 ? usageSentence(colour, locale) : null,
    ]
        .filter(Boolean)
        .join(' ');

    const previewed = { name: data.name, swatch: shownSwatch };

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
                    id={`${uid}-name`}
                    label="Name"
                    required
                    error={errors.name}
                >
                    {(control) => (
                        <Input
                            {...control}
                            value={data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                            autoComplete="off"
                        />
                    )}
                </FormField>

                {creating && (
                    <FormField
                        id={`${uid}-code`}
                        label="Code"
                        required
                        hint="2 to 4 capital letters or digits, such as IND. It can't be changed later."
                        error={errors.code}
                    >
                        {(control) => (
                            <Input
                                {...control}
                                value={data.code}
                                onChange={(event) =>
                                    form.setData('code', event.target.value)
                                }
                                autoComplete="off"
                                autoCapitalize="characters"
                                spellCheck={false}
                                className="font-mono uppercase sm:w-32"
                            />
                        )}
                    </FormField>
                )}

                <FormField
                    id={`${uid}-swatch`}
                    label="Swatch"
                    required
                    hint={swatchHint}
                    error={errors.swatch}
                >
                    {(control) => (
                        <div className="flex items-center gap-2">
                            <input
                                type="color"
                                aria-label="Pick swatch colour"
                                aria-describedby={control['aria-describedby']}
                                value={
                                    VALID_SWATCH.test(shownSwatch)
                                        ? shownSwatch.toLowerCase()
                                        : '#000000'
                                }
                                onChange={(event) =>
                                    changeSwatch(event.target.value)
                                }
                                className="size-9 shrink-0 cursor-pointer rounded-md border border-input bg-transparent p-0.5 outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            />
                            <Input
                                {...control}
                                value={data.swatch}
                                onChange={(event) =>
                                    changeSwatch(event.target.value)
                                }
                                autoComplete="off"
                                autoCapitalize="characters"
                                spellCheck={false}
                                placeholder="#3E4C7E"
                                className="font-mono sm:w-40"
                            />
                        </div>
                    )}
                </FormField>

                <div role="group" aria-labelledby={`${uid}-preview`}>
                    <p
                        id={`${uid}-preview`}
                        className="mb-2 text-sm font-medium"
                    >
                        Preview
                    </p>
                    <div className="grid gap-2 sm:grid-cols-2">
                        <PreviewStrip
                            label="On a light screen"
                            colour={previewed}
                            className="border bg-white text-zinc-900"
                            labelClassName="text-zinc-600"
                        />
                        <PreviewStrip
                            label="On a dark screen"
                            colour={previewed}
                            className="border border-zinc-700 bg-zinc-900 text-zinc-50"
                            labelClassName="text-zinc-400"
                        />
                    </div>
                </div>

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
                    disabled={processing}
                    onClick={() => onOpenChange(false)}
                >
                    Cancel
                </Button>
                <Button type="submit" disabled={processing}>
                    {processing && <Spinner />}
                    Save colour
                </Button>
            </DialogFooter>
        </form>
    );
}

type PreviewStripProps = {
    label: string;
    colour: { name: string; swatch: string };
    className: string;
    labelClassName: string;
};

/** The tag on a strip that stays light or dark whatever the app's mode is. */
function PreviewStrip({
    label,
    colour,
    className,
    labelClassName,
}: PreviewStripProps) {
    return (
        <div className={cn('space-y-1.5 rounded-md p-3', className)}>
            <p className={cn('text-xs', labelClassName)}>{label}</p>
            <p className="min-h-5 text-sm">
                {colour.name.trim() === '' ? (
                    // Never a lone swatch: the grey word stands in for the name.
                    <span className="inline-flex items-center gap-2">
                        <ColourSwatch swatch={colour.swatch} />
                        <span className={labelClassName}>Name</span>
                    </span>
                ) : (
                    <ColourTag colour={colour} />
                )}
            </p>
        </div>
    );
}

/**
 * Add a colour or edit one. The form lives inside the dialog content, so it
 * starts fresh every time the dialog opens; it stays open on server errors and
 * closes when the write succeeds.
 */
export function ColourFormDialog({
    open,
    onOpenChange,
    colour,
    returnFocusTo,
}: ColourFormDialogProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent
                // At most the screen's height: the form's middle scrolls, the title and the buttons stay in view.
                className="flex max-h-[calc(100dvh-2rem)] max-w-full flex-col sm:max-w-lg"
                // Adding has no description to point at.
                {...(colour ? {} : { 'aria-describedby': undefined })}
                onCloseAutoFocus={(event) => {
                    if (returnFocusTo?.current?.isConnected) {
                        event.preventDefault();
                        returnFocusTo.current.focus();
                    }
                }}
            >
                <DialogHeader className="shrink-0">
                    <DialogTitle>
                        {colour ? `Edit ${colour.name}` : 'Add colour'}
                    </DialogTitle>
                    {colour && (
                        <DialogDescription>
                            Code {colour.code} can't be changed.
                        </DialogDescription>
                    )}
                </DialogHeader>
                <ColourForm onOpenChange={onOpenChange} colour={colour} />
            </DialogContent>
        </Dialog>
    );
}
