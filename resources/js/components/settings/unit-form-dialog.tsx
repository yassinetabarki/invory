import { useForm } from '@inertiajs/react';
import { useId, useRef } from 'react';
import type { FormEvent, RefObject } from 'react';
import { FormField } from '@/components/materials/form-field';
import { useFocusFirstInvalid } from '@/components/materials/use-focus-first-invalid';
import { DecimalInput } from '@/components/products/decimal-input';
import { trimDecimal } from '@/components/products/decimals';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import { BASE_UNIT_CODE, describeFactor } from '@/lib/reference';
import type {
    Option,
    UnitDimension,
    UnitFormData,
    UnitRow,
} from '@/types/domain';

const UNIT_CODE_MAX_LENGTH = 16;

type UnitFormDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** The unit being edited; null when adding. */
    row: UnitRow | null;
    returnFocusTo?: RefObject<HTMLElement | null>;
    dimensions: Option<UnitDimension>[];
    urls: { store: string; update: (id: number) => string };
};

/** Why a unit's dimension and factor can't be edited, or null when they can. */
function lockReason(row: UnitRow | null, locale: string): string | null {
    if (row === null) {
        return null;
    }

    if (row.is_system) {
        return 'System units keep their dimension and factor.';
    }

    if (row.usage_count > 0) {
        const count = formatCount(row.usage_count, locale);
        const noun = row.usage_count === 1 ? 'material' : 'materials';

        return `Used by ${count} ${noun}, so its dimension and factor can't change.`;
    }

    return null;
}

/**
 * What the server accepts. `code` on create only. A system unit, or a custom
 * unit that materials use, takes only `name` and `position`: sending
 * `dimension` or `factor_to_base` is an error even when unchanged. `package`
 * units have no factor, so it is left out. `position` only when typed.
 */
function toPayload(
    data: UnitFormData,
    row: UnitRow | null,
    locked: boolean,
): Record<string, string> {
    const position = data.position.trim();
    const payload: Record<string, string> = {};

    if (row === null) {
        payload.code = data.code;
    }

    payload.name = data.name;

    if (!locked) {
        payload.dimension = data.dimension;

        if (data.dimension !== 'package') {
            payload.factor_to_base = data.factor_to_base.trim();
        }
    }

    if (position !== '') {
        payload.position = position;
    }

    return payload;
}

type FormProps = Pick<
    UnitFormDialogProps,
    'onOpenChange' | 'row' | 'dimensions' | 'urls'
>;

function UnitForm({ onOpenChange, row, dimensions, urls }: FormProps) {
    const uid = useId();
    const { locale } = useAppSettings();
    const formRef = useRef<HTMLFormElement>(null);
    const creating = row === null;
    const reason = lockReason(row, locale);
    const locked = reason !== null;
    const form = useForm<UnitFormData>({
        code: '',
        name: row?.name ?? '',
        dimension: row?.dimension ?? '',
        // The server's scale-12 string, trimmed as text ("0.9144"); never a number.
        factor_to_base: trimDecimal(row?.factor_to_base ?? ''),
        position: row ? String(row.position) : '',
    });
    const { data, errors, processing } = form;

    useFocusFirstInvalid(formRef, errors, processing);

    const isPackage = data.dimension === 'package';
    const baseCode =
        data.dimension === '' || data.dimension === 'package'
            ? undefined
            : BASE_UNIT_CODE[data.dimension];

    function chooseDimension(value: string) {
        const dimension = dimensions.find((option) => option.value === value);

        if (!dimension) {
            return;
        }

        // A package unit has no factor: drop what was typed.
        form.setData((current) => ({
            ...current,
            dimension: dimension.value,
            factor_to_base:
                dimension.value === 'package' ? '' : current.factor_to_base,
        }));
    }

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        form.transform((values) => toPayload(values, row, locked));

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
            ref={formRef}
            noValidate
            onSubmit={submit}
            className="flex flex-col gap-4"
        >
            {creating && (
                <CodeField
                    id={`${uid}-code`}
                    value={data.code}
                    onChange={(value) => form.setData('code', value)}
                    error={errors.code}
                    maxLength={UNIT_CODE_MAX_LENGTH}
                />
            )}
            <NameField
                id={`${uid}-name`}
                value={data.name}
                onChange={(value) => form.setData('name', value)}
                error={errors.name}
            />

            {row !== null && reason !== null ? (
                <div className="space-y-2 rounded-lg border bg-muted/40 p-3 text-sm">
                    <dl className="grid grid-cols-2 gap-2">
                        <div>
                            <dt className="text-xs text-muted-foreground">
                                Dimension
                            </dt>
                            <dd className="font-medium">
                                {row.dimension_label}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-xs text-muted-foreground">
                                Factor
                            </dt>
                            <dd className="font-medium tabular-nums">
                                {describeFactor(row)}
                            </dd>
                        </div>
                    </dl>
                    <p className="text-xs text-muted-foreground">{reason}</p>
                </div>
            ) : (
                <>
                    <FormField
                        id={`${uid}-dimension`}
                        label="Dimension"
                        required
                        error={errors.dimension}
                    >
                        {(control) => (
                            <Select
                                value={data.dimension}
                                onValueChange={chooseDimension}
                            >
                                <SelectTrigger {...control} className="w-full">
                                    <SelectValue placeholder="Choose a dimension…" />
                                </SelectTrigger>
                                <SelectContent>
                                    {dimensions.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        )}
                    </FormField>

                    {isPackage ? (
                        <p className="text-sm text-muted-foreground">
                            A package unit (a roll, a cone) holds a different
                            amount for each material, so it has no factor.
                        </p>
                    ) : (
                        <FormField
                            id={`${uid}-factor`}
                            label="Factor to base unit"
                            required
                            hint={
                                baseCode === undefined
                                    ? 'Choose the dimension first: the factor is expressed in its base unit.'
                                    : `One ${data.code || row?.code || 'unit'} equals this many ${baseCode}. Up to 12 decimals.`
                            }
                            error={errors.factor_to_base}
                        >
                            {(control) => (
                                <DecimalInput
                                    {...control}
                                    value={data.factor_to_base}
                                    onChange={(event) =>
                                        form.setData(
                                            'factor_to_base',
                                            event.target.value,
                                        )
                                    }
                                    addonEnd={baseCode}
                                    placeholder="0.0254"
                                />
                            )}
                        </FormField>
                    )}
                </>
            )}

            <PositionField
                id={`${uid}-position`}
                value={data.position}
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
                    Save unit
                </Button>
            </DialogFooter>
        </form>
    );
}

/**
 * Create or edit a unit. The form lives inside the dialog content, so it starts
 * fresh every time the dialog opens; it stays open on server errors and closes
 * when the write succeeds.
 */
export function UnitFormDialog({
    open,
    onOpenChange,
    row,
    returnFocusTo,
    dimensions,
    urls,
}: UnitFormDialogProps) {
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
                        {row ? `Edit ${row.name}` : 'Add unit'}
                    </DialogTitle>
                    <DialogDescription>
                        {row
                            ? `Code ${row.code} can't be changed.`
                            : 'Pick a short code now: it stays the same when the name changes.'}
                    </DialogDescription>
                </DialogHeader>
                <UnitForm
                    onOpenChange={onOpenChange}
                    row={row}
                    dimensions={dimensions}
                    urls={urls}
                />
            </DialogContent>
        </Dialog>
    );
}
