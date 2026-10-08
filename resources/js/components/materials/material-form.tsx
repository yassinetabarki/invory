import type { ReactNode } from 'react';
import { createContext, useContext, useId, useRef, useState } from 'react';
import { currencySymbol } from '@/components/materials/currency-symbol';
import { AffixInput, FormField } from '@/components/materials/form-field';
import type { ControlProps } from '@/components/materials/form-field';
import { MaterialFormActions } from '@/components/materials/material-form-actions';
import { useFocusFirstInvalid } from '@/components/materials/use-focus-first-invalid';
import { PhotoField } from '@/components/shared/photo-field';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
/** The fields rendered as text inputs (everything but the photo pair). */
type TextFieldName = Exclude<FieldName, 'photo' | 'remove_photo'>;
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
    /** The photo the material has now (edit), or null. */
    photoUrl?: string | null;
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

type FieldProps = {
    name: FieldName;
    label: string;
    required?: boolean;
    optional?: boolean;
    hint?: string;
    className?: string;
    children: (control: ControlProps) => ReactNode;
};

/** A `FormField` whose id and error come from the form's context. */
function Field({ name, ...props }: FieldProps) {
    const { uid, errors } = useFormContext();

    return <FormField id={`${uid}-${name}`} error={errors[name]} {...props} />;
}

type SimpleFieldProps = {
    name: TextFieldName;
    label: string;
    required?: boolean;
    optional?: boolean;
    hint?: string;
    placeholder?: string;
    /** Classes of the whole field (grid placement). */
    className?: string;
    /** Classes of the input itself. */
    inputClassName?: string;
};

function TextField({
    name,
    placeholder,
    inputClassName,
    ...field
}: SimpleFieldProps) {
    const { initial } = useFormContext();

    return (
        <Field name={name} {...field}>
            {(control) => (
                <Input
                    {...control}
                    name={name}
                    type="text"
                    defaultValue={initial[name]}
                    placeholder={placeholder}
                    autoComplete="off"
                    className={inputClassName}
                />
            )}
        </Field>
    );
}

/** Decimal text input (never `type="number"`) with a visible prefix / suffix. */
function DecimalField({
    name,
    placeholder,
    startAffix,
    endAffix,
    ...field
}: SimpleFieldProps & { startAffix?: string; endAffix?: string }) {
    const { initial } = useFormContext();

    return (
        <Field name={name} {...field}>
            {(control) => (
                <AffixInput
                    {...control}
                    name={name}
                    type="text"
                    inputMode="decimal"
                    defaultValue={initial[name]}
                    placeholder={placeholder}
                    autoComplete="off"
                    startAffix={startAffix}
                    endAffix={endAffix}
                />
            )}
        </Field>
    );
}

/** A titled card, named for assistive technology by its heading. */
function Section({
    title,
    description,
    className,
    children,
}: {
    title: ReactNode;
    description?: string;
    className?: string;
    children: ReactNode;
}) {
    const titleId = useId();

    return (
        <section
            aria-labelledby={titleId}
            className={cn(
                'min-w-0 space-y-4 rounded-xl border bg-card p-5 text-card-foreground shadow-sm',
                className,
            )}
        >
            <div className="space-y-0.5">
                <h2 id={titleId} className="text-base font-semibold">
                    {title}
                </h2>
                {description && (
                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
            {children}
        </section>
    );
}

/**
 * The fields of a material, as UNCONTROLLED named inputs so the browser form
 * submits them (a chosen photo makes the request multipart). It renders no
 * `<form>` of its own: place it inside Inertia's
 * `<Form {...MaterialController.store.form()}>`. Key the form by material id
 * when the same page can show different materials, because `initial` only
 * seeds the inputs (`defaultValue`). The page renders the header actions with
 * `<MaterialFormActions>`; the ones at the bottom belong to this component.
 */
export function MaterialForm({
    initial,
    categories,
    units,
    errors,
    processing,
    submitLabel,
    cancelHref,
    photoUrl = null,
}: MaterialFormProps) {
    const uid = useId();
    const { currency, locale } = useAppSettings();
    const containerRef = useRef<HTMLDivElement>(null);
    // Local state only so the suffixes can follow the selected unit; the
    // Radix Select also renders a hidden native <select name="unit">.
    const [unit, setUnit] = useState<UnitOfMeasure | ''>(initial.unit);

    useFocusFirstInvalid(containerRef, errors, processing);

    const unitSuffix = unit === '' ? undefined : unit;

    return (
        <FormContext value={{ uid, initial, errors }}>
            <div ref={containerRef} className="flex flex-col gap-6">
                <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_20rem]">
                    <div className="flex min-w-0 flex-col gap-5">
                        <Section title="Identity">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <TextField
                                    name="sku"
                                    label="SKU"
                                    required
                                    inputClassName="font-mono"
                                />
                                <TextField
                                    name="name"
                                    label="Name"
                                    required
                                    placeholder="e.g. Denim 12oz Indigo"
                                />
                                <Field
                                    name="category"
                                    label="Category"
                                    required
                                >
                                    {(control) => (
                                        <Select
                                            name="category"
                                            defaultValue={initial.category}
                                        >
                                            <SelectTrigger
                                                {...control}
                                                className="w-full"
                                            >
                                                <SelectValue placeholder="Choose a category…" />
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
                                <TextField
                                    name="color"
                                    label="Colour"
                                    optional
                                    placeholder="e.g. Indigo, Antique brass"
                                />
                                <TextField
                                    name="composition"
                                    label="Composition"
                                    optional
                                    placeholder="e.g. 100% cotton, Zinc alloy"
                                    className="sm:col-span-2"
                                />
                            </div>
                        </Section>

                        <Section
                            title="Stock and cost"
                            description="Quantities in compositions always use this unit."
                        >
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field
                                    name="unit"
                                    label="Unit of measure"
                                    required
                                >
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
                                                <SelectValue placeholder="Choose a unit…" />
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
                                <DecimalField
                                    name="unit_cost"
                                    label="Unit cost"
                                    required
                                    hint="Up to 4 decimals, e.g. 0.0040 for thread."
                                    placeholder="0.00"
                                    startAffix={currencySymbol(
                                        currency,
                                        locale,
                                    )}
                                    endAffix={unitSuffix && `/ ${unitSuffix}`}
                                />
                                <DecimalField
                                    name="quantity_on_hand"
                                    label="Quantity in stock"
                                    required
                                    placeholder="0"
                                    endAffix={unitSuffix}
                                />
                                <DecimalField
                                    name="reorder_level"
                                    label="Reorder at"
                                    optional
                                    hint="Shown as Low stock at or below this level."
                                    placeholder="—"
                                    endAffix={unitSuffix}
                                />
                            </div>
                        </Section>

                        <Section title="Supplier and notes">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <TextField
                                    name="supplier_name"
                                    label="Supplier"
                                    optional
                                    placeholder="Supplier name"
                                />
                                <TextField
                                    name="supplier_reference"
                                    label="Supplier reference"
                                    optional
                                    placeholder="Their article code"
                                />
                                <Field
                                    name="notes"
                                    label="Notes"
                                    optional
                                    className="sm:col-span-2"
                                >
                                    {(control) => (
                                        <Textarea
                                            {...control}
                                            name="notes"
                                            rows={3}
                                            defaultValue={initial.notes}
                                            placeholder="Handling, lead time, minimum order…"
                                        />
                                    )}
                                </Field>
                            </div>
                        </Section>
                    </div>

                    <Section
                        title={
                            <>
                                Photo{' '}
                                <span className="text-sm font-normal text-muted-foreground">
                                    (optional)
                                </span>
                            </>
                        }
                        description="Shown in the materials table and on the detail page."
                    >
                        {/* The section heading names the card: keep the field's own label for screen readers only. */}
                        <div className="[&_[data-slot=photo-field]>[data-slot=label]]:sr-only">
                            <PhotoField
                                label="Photo"
                                currentUrl={photoUrl}
                                aspect="square"
                                error={errors.photo}
                            />
                        </div>
                    </Section>
                </div>

                <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <MaterialFormActions
                        processing={processing}
                        submitLabel={submitLabel}
                        cancelHref={cancelHref}
                    />
                </div>
            </div>
        </FormContext>
    );
}
