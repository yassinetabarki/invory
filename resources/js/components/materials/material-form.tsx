import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import {
    createContext,
    useContext,
    useEffect,
    useId,
    useRef,
    useState,
} from 'react';
import { Button } from '@/components/ui/button';
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
import { Textarea } from '@/components/ui/textarea';
import { useAppSettings } from '@/hooks/use-app-settings';
import { cn } from '@/lib/utils';
import type {
    MaterialCategory,
    MaterialFormData,
    Option,
    UnitOfMeasure,
} from '@/types/domain';

type FieldName = keyof MaterialFormData;
type FieldErrors = Partial<Record<FieldName, string>>;

type MaterialFormProps = {
    initial: MaterialFormData;
    categories: Option<MaterialCategory>[];
    units: Option<UnitOfMeasure>[];
    /** Server validation errors keyed by field name. */
    errors: FieldErrors;
    processing: boolean;
    submitLabel: string;
    cancelHref?: string;
};

/** Shared by every field of one form instance. */
type FormContextValue = {
    uid: string;
    initial: MaterialFormData;
    errors: FieldErrors;
};

const FormContext = createContext<FormContextValue | null>(null);

function useFormContext(): FormContextValue {
    const context = useContext(FormContext);

    if (!context) {
        throw new Error(
            'Material form fields must be rendered by <MaterialForm>.',
        );
    }

    return context;
}

/** Attributes every control receives so label, hint and error are wired up. */
type ControlProps = {
    id: string;
    'aria-invalid': true | undefined;
    'aria-required': true | undefined;
    'aria-describedby': string | undefined;
};

type FieldProps = {
    name: FieldName;
    label: string;
    required?: boolean;
    hint?: string;
    /** Id of a visible suffix (unit) that should also be announced. */
    suffixId?: string;
    className?: string;
    children: (control: ControlProps) => ReactNode;
};

function Field({
    name,
    label,
    required = false,
    hint,
    suffixId,
    className,
    children,
}: FieldProps) {
    const { uid, errors } = useFormContext();
    const id = `${uid}-${name}`;
    const error = errors[name];
    const describedBy =
        [
            hint ? `${id}-hint` : null,
            suffixId ?? null,
            error ? `${id}-error` : null,
        ]
            .filter(Boolean)
            .join(' ') || undefined;

    return (
        <div className={cn('space-y-2', className)}>
            <Label htmlFor={id}>
                {label}
                {required && (
                    <span
                        aria-hidden="true"
                        className="text-destructive-foreground"
                    >
                        {' '}
                        *
                    </span>
                )}
            </Label>
            {children({
                id,
                'aria-invalid': error ? true : undefined,
                'aria-required': required ? true : undefined,
                'aria-describedby': describedBy,
            })}
            {hint && (
                <p id={`${id}-hint`} className="text-xs text-muted-foreground">
                    {hint}
                </p>
            )}
            {error && (
                <p
                    id={`${id}-error`}
                    className="text-sm text-destructive-foreground"
                >
                    {error}
                </p>
            )}
        </div>
    );
}

type SimpleFieldProps = {
    name: FieldName;
    label: string;
    required?: boolean;
    hint?: string;
};

function TextField({ name, label, required, hint }: SimpleFieldProps) {
    const { initial } = useFormContext();

    return (
        <Field name={name} label={label} required={required} hint={hint}>
            {(control) => (
                <Input
                    {...control}
                    name={name}
                    type="text"
                    defaultValue={initial[name]}
                    autoComplete="off"
                />
            )}
        </Field>
    );
}

/** Decimal text input (never `type="number"`) with a visible suffix. */
function DecimalField({
    name,
    label,
    required,
    hint,
    suffix,
}: SimpleFieldProps & { suffix: string }) {
    const { uid, initial } = useFormContext();
    const suffixId = `${uid}-${name}-suffix`;

    return (
        <Field
            name={name}
            label={label}
            required={required}
            hint={hint}
            suffixId={suffixId}
        >
            {(control) => (
                <div className="relative">
                    <Input
                        {...control}
                        name={name}
                        type="text"
                        inputMode="decimal"
                        defaultValue={initial[name]}
                        autoComplete="off"
                        className="pr-24"
                    />
                    <span
                        id={suffixId}
                        className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-muted-foreground"
                    >
                        {suffix}
                    </span>
                </div>
            )}
        </Field>
    );
}

/**
 * The fields of a material, as UNCONTROLLED named inputs so the browser form
 * submits them. It renders no `<form>` of its own: place it inside Inertia's
 * `<Form {...MaterialController.store.form()}>`. Key the form by material id
 * when the same page can show different materials, because `initial` only
 * seeds the inputs (`defaultValue`).
 */
export function MaterialForm({
    initial,
    categories,
    units,
    errors,
    processing,
    submitLabel,
    cancelHref,
}: MaterialFormProps) {
    const uid = useId();
    const { currency } = useAppSettings();
    const containerRef = useRef<HTMLDivElement>(null);
    // Local state only so the suffixes can follow the selected unit; the
    // Radix Select also renders a hidden native <select name="unit">.
    const [unit, setUnit] = useState<UnitOfMeasure | ''>(initial.unit);

    const errorSignature = Object.entries(errors)
        .filter(([, message]) => Boolean(message))
        .map(([name, message]) => `${name}:${message}`)
        .join('|');

    // Move focus to the first invalid field once a submission has failed.
    useEffect(() => {
        if (processing || errorSignature === '') {
            return;
        }

        containerRef.current
            ?.querySelector<HTMLElement>('[aria-invalid="true"]')
            ?.focus();
    }, [errorSignature, processing]);

    const unitCostSuffix = unit === '' ? currency : `${currency} / ${unit}`;
    const quantitySuffix = unit === '' ? 'unit' : unit;

    return (
        <FormContext value={{ uid, initial, errors }}>
            <div ref={containerRef} className="space-y-8">
                <fieldset className="min-w-0">
                    <legend className="mb-3 text-base font-semibold">
                        Identification
                    </legend>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <TextField name="sku" label="SKU" required />
                        <TextField name="name" label="Name" required />
                        <Field name="category" label="Category" required>
                            {(control) => (
                                <Select
                                    name="category"
                                    defaultValue={initial.category}
                                >
                                    <SelectTrigger
                                        {...control}
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Select a category" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {categories.map((category) => (
                                            <SelectItem
                                                key={category.value}
                                                value={category.value}
                                            >
                                                {category.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            )}
                        </Field>
                        <Field name="unit" label="Unit of measure" required>
                            {(control) => (
                                <Select
                                    name="unit"
                                    value={unit}
                                    onValueChange={(value) =>
                                        setUnit(value as UnitOfMeasure)
                                    }
                                >
                                    <SelectTrigger
                                        {...control}
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Select a unit" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {units.map((option) => (
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
                        </Field>
                    </div>
                </fieldset>

                <fieldset className="min-w-0">
                    <legend className="mb-3 text-base font-semibold">
                        Stock &amp; cost
                    </legend>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <DecimalField
                            name="unit_cost"
                            label="Unit cost"
                            required
                            suffix={unitCostSuffix}
                        />
                        <DecimalField
                            name="quantity_on_hand"
                            label="Quantity on hand"
                            required
                            suffix={quantitySuffix}
                        />
                        <DecimalField
                            name="reorder_level"
                            label="Reorder level"
                            hint="Shows as Low stock at or below this level"
                            suffix={quantitySuffix}
                        />
                    </div>
                </fieldset>

                <fieldset className="min-w-0">
                    <legend className="mb-3 text-base font-semibold">
                        Supplier &amp; details
                    </legend>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <TextField name="supplier_name" label="Supplier" />
                        <TextField
                            name="supplier_reference"
                            label="Supplier reference"
                        />
                        <TextField name="color" label="Colour" />
                        <TextField name="composition" label="Composition" />
                        <Field
                            name="notes"
                            label="Notes"
                            className="sm:col-span-2"
                        >
                            {(control) => (
                                <Textarea
                                    {...control}
                                    name="notes"
                                    rows={3}
                                    defaultValue={initial.notes}
                                />
                            )}
                        </Field>
                    </div>
                </fieldset>

                <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    {cancelHref && (
                        <Button asChild variant="outline">
                            <Link href={cancelHref}>Cancel</Link>
                        </Button>
                    )}
                    <Button type="submit" disabled={processing}>
                        {processing && <Spinner />}
                        {submitLabel}
                    </Button>
                </div>
            </div>
        </FormContext>
    );
}
