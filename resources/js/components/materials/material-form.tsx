import type { ReactNode } from 'react';
import { createContext, useContext, useId, useRef, useState } from 'react';
import { currencySymbol } from '@/components/materials/currency-symbol';
import { AffixInput, FormField } from '@/components/materials/form-field';
import type { ControlProps } from '@/components/materials/form-field';
import {
    MaterialColoursTable,
    useColourRows,
} from '@/components/materials/material-colours-table';
import { MaterialFormActions } from '@/components/materials/material-form-actions';
import { SameCostDialog } from '@/components/materials/same-cost-dialog';
import type { CostChoice } from '@/components/materials/same-cost-dialog';
import { StockSettings } from '@/components/materials/stock-settings';
import { SwitchToOneStockDialog } from '@/components/materials/switch-to-one-stock-dialog';
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
    Colour,
    MaterialCategory,
    MaterialColourStock,
    MaterialFormData,
    Option,
    UnitOfMeasure,
} from '@/types/domain';

type FieldName = keyof MaterialFormData;
/** The fields rendered as text inputs (everything but the photo pair and the stock-per-colour fields, INV-21-h). */
type TextFieldName = Exclude<
    FieldName,
    'photo' | 'remove_photo' | 'stock_mode' | 'cost_mode' | 'colours'
>;
/** Server errors by key: the fields, and `colours` / `colours.{i}.…` for the Colours table. */
type FieldErrors = Partial<Record<string, string>>;

/** Phone fields and buttons are 44 px high, 36 px from the laptop breakpoint. */
const TALL = 'h-11 md:h-9';

type MaterialFormProps = {
    initial: MaterialFormData;
    categories: Option<MaterialCategory>[];
    units: Option<UnitOfMeasure>[];
    /** Server validation errors keyed by field name. */
    /** The workshop's colours in Settings › Colours order (a page prop). */
    colours: Colour[];
    /** The colours the material has saved, with whether each can go (edit); [] on Add. */
    storedColours?: MaterialColourStock[];
    errors: FieldErrors;
    processing: boolean;
    submitLabel: string;
    cancelHref?: string;
    /** The photo the material has now (edit), or null. */
    photoUrl?: string | null;
};

/** The text a confirmed switch to one stock puts in the one-stock fields (undefined: the form's own). */
type SingleFigures = {
    quantity?: string;
    quantityKey: number;
    unitCost?: string;
    unitCostKey: number;
    /** The quantity is the server's total of the colours: shown, not typed. */
    joined: boolean;
};

/** True unless the text is empty or only zeros (no maths: a quantity string with a non-zero digit). */
function hasStock(quantity: string): boolean {
    return /[1-9]/.test(quantity);
}

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
                    className={cn(TALL, inputClassName)}
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
    disabled,
    readOnly,
    defaultValue,
    ...field
}: SimpleFieldProps & {
    startAffix?: string;
    endAffix?: string;
    /** A field the chosen stock setting doesn't use: not submitted, its text kept. */
    disabled?: boolean;
    /** A figure the server works out (the total of a switch to one stock). */
    readOnly?: boolean;
    /** Replaces the form's initial text (a switch put a new figure here); key the field to apply it. */
    defaultValue?: string;
}) {
    const { initial } = useFormContext();

    return (
        <Field name={name} {...field}>
            {(control) => (
                <AffixInput
                    {...control}
                    name={name}
                    type="text"
                    inputMode="decimal"
                    defaultValue={defaultValue ?? initial[name]}
                    placeholder={placeholder}
                    autoComplete="off"
                    startAffix={startAffix}
                    endAffix={endAffix}
                    disabled={disabled}
                    readOnly={readOnly}
                    className={TALL}
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
    colours,
    storedColours,
    photoUrl = null,
}: MaterialFormProps) {
    const uid = useId();
    const { currency, locale } = useAppSettings();
    const containerRef = useRef<HTMLDivElement>(null);
    // Local state only so the suffixes can follow the selected unit; the
    // Radix Select also renders a hidden native <select name="unit">.
    const [unit, setUnit] = useState<UnitOfMeasure>(initial.unit);

    const [stockMode, setStockMode] = useState(initial.stock_mode);
    const [costMode, setCostMode] = useState(initial.cost_mode);
    const rows = useColourRows(initial.colours, colours);
    // The window of a switch back that is open, if any (INV-21-i).
    const [pending, setPending] = useState<'stock' | 'cost' | null>(null);
    // Figures a confirmed switch puts in the one-stock fields; a bumped key remounts the field so it takes the new text.
    const [single, setSingle] = useState<SingleFigures>({
        quantityKey: 0,
        unitCostKey: 0,
        joined: false,
    });
    // The cost every colour started at, shown under the table until the cost setting changes again.
    const [costStartsAt, setCostStartsAt] = useState<string | null>(null);

    useFocusFirstInvalid(containerRef, errors, processing);

    const unitSuffix = unit === '' ? undefined : unit;
    const perColour = stockMode === 'per_colour';
    const costPerColour = perColour && costMode === 'per_colour';
    const symbol = currencySymbol(currency, locale);
    // Edit passes the colours the material has saved (even none); Add passes none. A new material switches freely.
    const saved = storedColours !== undefined;
    const savedPerColour = saved && initial.stock_mode === 'per_colour';
    const savedCostPerColour =
        savedPerColour && initial.cost_mode === 'per_colour';
    const currentCost = single.unitCost ?? initial.unit_cost;

    // A switch on a saved material: Stock per colour asks for the colour holding today's stock; going back asks first.
    function changeStockMode(next: typeof stockMode) {
        if (next === 'single' && savedPerColour) {
            setPending('stock');

            return;
        }

        setStockMode(next);

        if (
            next === 'per_colour' &&
            saved &&
            initial.stock_mode === 'single' &&
            hasStock(initial.quantity_on_hand) &&
            rows.rows.length === 0
        ) {
            rows.addHolder(initial.quantity_on_hand);
        }
    }

    function changeCostMode(next: typeof costMode) {
        if (next === 'same' && savedCostPerColour) {
            setPending('cost');

            return;
        }

        setCostMode(next);

        if (next === 'per_colour' && saved && currentCost !== '') {
            rows.fillCosts(currentCost);
            setCostStartsAt(currentCost);
        } else {
            setCostStartsAt(null);
        }
    }

    function applySameCost(cost: string) {
        setCostMode('same');
        setCostStartsAt(null);
        setSingle((current) => ({
            ...current,
            unitCost: cost,
            unitCostKey: current.unitCostKey + 1,
        }));
        setPending(null);
    }

    function applyOneStock(total: string, cost: string | null) {
        setStockMode('single');
        setCostMode('same');
        setCostStartsAt(null);
        setSingle((current) => ({
            quantity: total,
            quantityKey: current.quantityKey + 1,
            unitCost: cost ?? current.unitCost,
            unitCostKey:
                cost === null ? current.unitCostKey : current.unitCostKey + 1,
            joined: true,
        }));
        setPending(null);
    }

    /** What each colour holds in the form: the window's list of costs to keep. */
    const costChoices: CostChoice[] = rows.rows.flatMap((row) => {
        const colour = colours.find(
            (candidate) => candidate.id === row.colour_id,
        );

        return colour && row.unit_cost !== ''
            ? [{ id: row.uid, colourName: colour.name, cost: row.unit_cost }]
            : [];
    });

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
                                                className="w-full data-[size=default]:h-11 md:data-[size=default]:h-9"
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
                                {/* Hidden, not removed, with Stock per colour: its text is kept (and saved) for when the owner switches back. */}
                                <div hidden={perColour}>
                                    <TextField
                                        name="color"
                                        label="Colour"
                                        optional
                                        placeholder="e.g. Indigo, Antique brass"
                                    />
                                </div>
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
                            <StockSettings
                                stockMode={stockMode}
                                costMode={costMode}
                                onStockModeChange={changeStockMode}
                                onCostModeChange={changeCostMode}
                            />
                            {/* One stock sends no cost mode of its own: it is always "same". */}
                            {!perColour && (
                                <input
                                    type="hidden"
                                    name="cost_mode"
                                    value="same"
                                />
                            )}
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
                                                className="w-full data-[size=default]:h-11 md:data-[size=default]:h-9"
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
                                {/* Fields the setting doesn't use are hidden and not submitted; what was typed stays. */}
                                <div hidden={costPerColour}>
                                    <DecimalField
                                        key={`cost-${single.unitCostKey}`}
                                        defaultValue={single.unitCost}
                                        name="unit_cost"
                                        label="Unit cost"
                                        required
                                        hint="Up to 4 decimals, e.g. 0.0040 for thread."
                                        placeholder="0.00"
                                        startAffix={symbol}
                                        endAffix={
                                            unitSuffix && `/ ${unitSuffix}`
                                        }
                                        disabled={costPerColour}
                                    />
                                </div>
                                <div hidden={perColour}>
                                    <DecimalField
                                        key={`quantity-${single.quantityKey}`}
                                        defaultValue={single.quantity}
                                        readOnly={single.joined}
                                        name="quantity_on_hand"
                                        label="Quantity in stock"
                                        required
                                        placeholder="0"
                                        endAffix={unitSuffix}
                                        disabled={perColour}
                                    />
                                </div>
                            </div>
                            {perColour && (
                                <MaterialColoursTable
                                    id={`${uid}-colours`}
                                    colours={colours}
                                    rows={rows.rows}
                                    stored={storedColours ?? []}
                                    onAdd={rows.add}
                                    onChoose={rows.choose}
                                    onRemove={rows.remove}
                                    onEdit={rows.edit}
                                    costPerColour={costPerColour}
                                    unit={unitSuffix}
                                    currencySymbol={symbol}
                                    errors={errors}
                                    holding={
                                        rows.rows.some((row) => row.holder)
                                            ? initial.quantity_on_hand
                                            : undefined
                                    }
                                    costStartsAt={costStartsAt ?? undefined}
                                />
                            )}
                            <DecimalField
                                name="reorder_level"
                                label="Reorder at"
                                optional
                                hint={
                                    perColour
                                        ? 'Applies to each colour. The material shows Low stock when any colour is at or under this level.'
                                        : 'Shown as Low stock at or below this level.'
                                }
                                placeholder="—"
                                endAffix={unitSuffix}
                                className="sm:max-w-[calc(50%-0.5rem)]"
                            />
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

                <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end [&>*]:h-11 md:[&>*]:h-9">
                    <MaterialFormActions
                        processing={processing}
                        submitLabel={submitLabel}
                        cancelHref={cancelHref}
                    />
                </div>

                {pending === 'cost' && (
                    <SameCostDialog
                        choices={costChoices}
                        unit={unitSuffix}
                        onKeep={() => setPending(null)}
                        onConfirm={applySameCost}
                    />
                )}
                {pending === 'stock' && (
                    <SwitchToOneStockDialog
                        unit={unitSuffix}
                        colours={rows.rows.flatMap((row) =>
                            row.colour_id === ''
                                ? []
                                : [
                                      {
                                          colourId: row.colour_id,
                                          quantity: row.quantity_on_hand,
                                      },
                                  ],
                        )}
                        choices={costPerColour ? costChoices : null}
                        onKeep={() => setPending(null)}
                        onConfirm={applyOneStock}
                    />
                )}
            </div>
        </FormContext>
    );
}
