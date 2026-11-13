import { Plus } from 'lucide-react';
import { useId, useRef, useState } from 'react';
import { AffixInput } from '@/components/materials/form-field';
import { ColourPicker } from '@/components/products/colour-picker';
import { Callout } from '@/components/shared/callout';
import { ColourTag } from '@/components/shared/colour-tag';
import { Money } from '@/components/shared/money';
import { Quantity } from '@/components/shared/quantity';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import type {
    Colour,
    Decimal,
    MaterialColourStock,
    MaterialFormData,
} from '@/types/domain';

/** One colour of the material as the form holds it (the inputs are controlled). */
export type ColourRowState = {
    uid: number;
    /** '' on the row that waits for the colour holding today's stock. */
    colour_id: number | '';
    quantity_on_hand: string;
    unit_cost: string;
    /** The row that takes the stock the material has now when it switches to per colour: always the first. */
    holder?: boolean;
};

/** The rows of the Colours table: add (list order), remove, edit a field. */
export function useColourRows(
    initial: MaterialFormData['colours'],
    colours: Colour[],
) {
    const nextUid = useRef(initial.length);
    const [rows, setRows] = useState<ColourRowState[]>(() =>
        initial.flatMap((row, uid) =>
            row.colour_id === ''
                ? []
                : [{ ...row, uid, colour_id: row.colour_id }],
        ),
    );

    function add(colourId: number) {
        const position = new Map(colours.map((c, index) => [c.id, index]));
        const rank = (id: number | '') =>
            id === '' ? 0 : (position.get(id) ?? 0);

        setRows((current) =>
            [
                ...current,
                {
                    uid: nextUid.current++,
                    colour_id: colourId,
                    quantity_on_hand: '0',
                    unit_cost: '',
                },
            ].sort(
                (a, b) =>
                    Number(b.holder === true) - Number(a.holder === true) ||
                    rank(a.colour_id) - rank(b.colour_id),
            ),
        );
    }

    /** The first row: no colour yet, holding the stock the material has now. */
    function addHolder(quantity: string) {
        setRows((current) => [
            {
                uid: nextUid.current++,
                colour_id: '',
                quantity_on_hand: quantity,
                unit_cost: '',
                holder: true,
            },
            ...current,
        ]);
    }

    function choose(uid: number, colourId: number) {
        setRows((current) =>
            current.map((row) =>
                row.uid === uid ? { ...row, colour_id: colourId } : row,
            ),
        );
    }

    /** Every colour starts at one cost (a saved material switching to cost per colour). */
    function fillCosts(cost: Decimal) {
        setRows((current) =>
            current.map((row) => ({ ...row, unit_cost: cost })),
        );
    }

    function remove(uid: number) {
        setRows((current) => current.filter((row) => row.uid !== uid));
    }

    function edit(
        uid: number,
        field: 'quantity_on_hand' | 'unit_cost',
        value: string,
    ) {
        setRows((current) =>
            current.map((row) =>
                row.uid === uid ? { ...row, [field]: value } : row,
            ),
        );
    }

    return { rows, add, addHolder, choose, fillCosts, remove, edit };
}

type Errors = Partial<Record<string, string>>;

type MaterialColoursTableProps = {
    /** Base of the ids of this table's fields. */
    id: string;
    /** The workshop's whole list, in list order. */
    colours: Colour[];
    rows: ColourRowState[];
    /** The colours as saved, with whether each can go and why not. */
    stored: MaterialColourStock[];
    onAdd: (colourId: number) => void;
    /** The first row's colour, chosen from its own picker. */
    onChoose?: (uid: number, colourId: number) => void;
    onRemove: (uid: number) => void;
    onEdit: (
        uid: number,
        field: 'quantity_on_hand' | 'unit_cost',
        value: string,
    ) => void;
    costPerColour: boolean;
    /** The material's unit code ("m"), or undefined until one is chosen. */
    unit?: string;
    currencySymbol: string;
    errors: Errors;
    /** The quantity in stock now, while a saved material waits for the colour that holds it. */
    holding?: Decimal;
    /** The cost every colour started at, while a saved material's cost has just gone per colour. */
    costStartsAt?: Decimal;
};

/**
 * The "Colours" of a per-colour material: a block per colour (swatch and name,
 * Quantity in stock, Unit cost with cost per colour, Remove), then **Add
 * colour**, which opens Add product's searchable list with only the colours the
 * material doesn't have. Renders no `<form>`: the inputs are named
 * `colours[i][...]` for the form around it.
 */
export function MaterialColoursTable({
    id,
    colours,
    rows,
    stored,
    onAdd,
    onChoose,
    onRemove,
    onEdit,
    costPerColour,
    unit,
    currencySymbol,
    errors,
    holding,
    costStartsAt,
}: MaterialColoursTableProps) {
    const addRef = useRef<HTMLButtonElement>(null);
    const [open, setOpen] = useState(false);
    // Colours chosen while the list is open: they stay in it (so they can be un-chosen) until it closes.
    const [added, setAdded] = useState<number[]>([]);
    const headingId = useId();
    const errorId = `${id}-error`;
    const taken = new Set(rows.map((row) => row.colour_id));
    const offered = colours.filter(
        (colour) => !taken.has(colour.id) || added.includes(colour.id),
    );

    function toggle(colourId: number) {
        const row = rows.find((candidate) => candidate.colour_id === colourId);

        if (row && added.includes(colourId)) {
            setAdded(added.filter((candidate) => candidate !== colourId));
            onRemove(row.uid);
        } else if (!row) {
            setAdded([...added, colourId]);
            onAdd(colourId);
        }
    }

    function handleOpenChange(next: boolean) {
        setOpen(next);

        if (!next) {
            setAdded([]);
        }
    }

    const columns = costPerColour
        ? 'md:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)_minmax(0,1fr)_auto]'
        : 'md:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)_auto]';

    return (
        <div
            role="group"
            aria-labelledby={headingId}
            className="min-w-0 space-y-3"
        >
            <div className="space-y-0.5">
                <h3 id={headingId} className="text-sm font-medium">
                    Colours
                </h3>
                <p className="text-xs text-muted-foreground">
                    From Settings › Colours. A product&apos;s colour takes its
                    material from the same colour.
                </p>
            </div>

            {holding !== undefined && (
                <Callout tone="info">
                    You have <Quantity value={holding} unit={unit} /> in stock
                    now. Choose the colour that holds it. Colours you add start
                    at 0.
                </Callout>
            )}

            {rows.length === 0 ? (
                <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                    No colours yet. Add the colours you buy this material in.
                </p>
            ) : (
                <div className="space-y-3">
                    <div
                        aria-hidden="true"
                        className={cn(
                            'hidden gap-3 text-xs font-medium text-muted-foreground md:grid',
                            columns,
                        )}
                    >
                        <span>Colour</span>
                        <span>Quantity in stock</span>
                        {costPerColour && <span>Unit cost</span>}
                        <span className="w-20" />
                    </div>
                    <ul className="space-y-3">
                        {rows.map((row, index) => {
                            const colour = colours.find(
                                (candidate) => candidate.id === row.colour_id,
                            );
                            const saved = stored.find(
                                (candidate) =>
                                    candidate.colour.id === row.colour_id,
                            );

                            return (
                                <ColourRow
                                    key={row.uid}
                                    id={`${id}-${row.uid}`}
                                    index={index}
                                    row={row}
                                    colour={colour}
                                    // A colour added in this session can always go.
                                    blocked={saved?.removable === false}
                                    reason={saved?.remove_reason ?? null}
                                    columns={columns}
                                    costPerColour={costPerColour}
                                    unit={unit}
                                    currencySymbol={currencySymbol}
                                    errors={errors}
                                    pickable={colours.filter(
                                        (candidate) =>
                                            candidate.id === row.colour_id ||
                                            !taken.has(candidate.id),
                                    )}
                                    onChoose={(colourId) =>
                                        onChoose?.(row.uid, colourId)
                                    }
                                    onEdit={onEdit}
                                    onRemove={() => {
                                        onRemove(row.uid);
                                        // The button with the focus is gone: keep the focus in the table.
                                        addRef.current?.focus();
                                    }}
                                />
                            );
                        })}
                    </ul>
                </div>
            )}

            <ColourPicker
                colours={offered}
                chosen={added}
                onToggle={toggle}
                open={open}
                onOpenChange={handleOpenChange}
                trigger={
                    <Button
                        ref={addRef}
                        type="button"
                        variant="outline"
                        aria-invalid={errors.colours ? true : undefined}
                        aria-describedby={errors.colours ? errorId : undefined}
                        className="h-11 w-full md:h-9 md:w-auto"
                    >
                        <Plus aria-hidden="true" />
                        Add colour
                    </Button>
                }
            />

            {errors.colours && (
                <p id={errorId} className="text-sm text-destructive-foreground">
                    {errors.colours}
                </p>
            )}

            {costPerColour && costStartsAt !== undefined && (
                <p className="text-xs text-muted-foreground">
                    Each colour starts at the current cost,{' '}
                    <Money value={costStartsAt} precise />
                    {unit && ` / ${unit}`}. Change the ones that differ.
                </p>
            )}
        </div>
    );
}

type ColourRowProps = {
    id: string;
    index: number;
    row: ColourRowState;
    colour: Colour | undefined;
    /** The colour is saved and can't go (it has stock or history). */
    blocked: boolean;
    /** The server's sentence saying why, if it sent one. */
    reason: string | null;
    columns: string;
    costPerColour: boolean;
    unit?: string;
    currencySymbol: string;
    errors: Errors;
    /** The colours this row's picker offers (the first row only). */
    pickable: Colour[];
    onChoose: (colourId: number) => void;
    onEdit: MaterialColoursTableProps['onEdit'];
    onRemove: () => void;
};

function ColourRow({
    id,
    index,
    row,
    colour,
    blocked,
    reason,
    columns,
    costPerColour,
    unit,
    currencySymbol,
    errors,
    pickable,
    onChoose,
    onEdit,
    onRemove,
}: ColourRowProps) {
    const key = `colours.${index}`;
    const name = colour?.name ?? '';
    const nameId = `${id}-name`;
    const reasonId = `${id}-reason`;
    const colourError = errors[`${key}.colour_id`];
    const colourErrorId = `${id}-colour-error`;
    const [pickerOpen, setPickerOpen] = useState(false);

    return (
        <li
            className={cn(
                'grid grid-cols-[minmax(0,1fr)_auto] items-start gap-3 rounded-lg border p-3',
                'md:rounded-none md:border-0 md:p-0',
                columns,
            )}
        >
            <input
                type="hidden"
                name={`colours[${index}][colour_id]`}
                value={row.colour_id}
            />
            <div className="min-w-0 space-y-1 md:flex md:min-h-9 md:flex-col md:justify-center">
                {row.holder ? (
                    <ColourPicker
                        colours={pickable}
                        chosen={row.colour_id === '' ? [] : [row.colour_id]}
                        onToggle={(colourId) => {
                            onChoose(colourId);
                            setPickerOpen(false);
                        }}
                        open={pickerOpen}
                        onOpenChange={setPickerOpen}
                        trigger={
                            <Button
                                type="button"
                                variant="outline"
                                aria-invalid={colourError ? true : undefined}
                                aria-describedby={
                                    colourError ? colourErrorId : undefined
                                }
                                className="h-11 w-full justify-start md:h-9"
                            >
                                <span id={nameId}>
                                    {colour ? (
                                        <ColourTag colour={colour} />
                                    ) : (
                                        'Choose a colour'
                                    )}
                                </span>
                            </Button>
                        }
                    />
                ) : (
                    <span id={nameId} className="block text-sm font-medium">
                        {colour && <ColourTag colour={colour} />}
                    </span>
                )}
                {reason && (
                    <p id={reasonId} className="text-xs text-muted-foreground">
                        {reason}
                    </p>
                )}
                {colourError && (
                    <p
                        id={colourErrorId}
                        className="text-sm text-destructive-foreground"
                    >
                        {colourError}
                    </p>
                )}
            </div>

            <RowField
                id={`${id}-quantity`}
                nameId={nameId}
                label="Quantity in stock"
                name={`colours[${index}][quantity_on_hand]`}
                value={row.quantity_on_hand}
                error={errors[`${key}.quantity_on_hand`]}
                extraDescription={colourError ? colourErrorId : undefined}
                endAffix={unit}
                placeholder="0"
                className="col-span-2 md:col-span-1"
                onChange={(value) => onEdit(row.uid, 'quantity_on_hand', value)}
            />
            {costPerColour && (
                <RowField
                    id={`${id}-cost`}
                    nameId={nameId}
                    label="Unit cost"
                    name={`colours[${index}][unit_cost]`}
                    value={row.unit_cost}
                    error={errors[`${key}.unit_cost`]}
                    startAffix={currencySymbol}
                    endAffix={unit && `/ ${unit}`}
                    placeholder="0.00"
                    className="col-span-2 md:col-span-1"
                    onChange={(value) => onEdit(row.uid, 'unit_cost', value)}
                />
            )}

            {/* On a phone the button sits beside the name; the DOM order keeps the tab order. */}
            <div className="col-start-2 row-start-1 md:col-auto md:row-auto">
                {/* The first row of a switch holds the stock the material has now: it has no Remove. */}
                {!row.holder && (
                    <Button
                        type="button"
                        variant="outline"
                        aria-label={`Remove ${name}`}
                        // Greyed, not disabled: it stays reachable by keyboard and says why.
                        aria-disabled={blocked ? true : undefined}
                        aria-describedby={
                            blocked && reason ? reasonId : undefined
                        }
                        className={cn(
                            'h-11 md:h-9',
                            blocked &&
                                'cursor-not-allowed opacity-50 hover:bg-transparent hover:text-current',
                        )}
                        onClick={() => {
                            if (!blocked) {
                                onRemove();
                            }
                        }}
                    >
                        Remove
                    </Button>
                )}
            </div>
        </li>
    );
}

type RowFieldProps = {
    id: string;
    /** The colour's name, read after the label ("Quantity in stock Black"). */
    nameId: string;
    label: string;
    name: string;
    value: string;
    error?: string;
    extraDescription?: string;
    startAffix?: string;
    endAffix?: string;
    placeholder: string;
    className?: string;
    onChange: (value: string) => void;
};

/** A decimal field of one colour: labelled on a phone, headed by the table's header on a laptop. */
function RowField({
    id,
    nameId,
    label,
    name,
    value,
    error,
    extraDescription,
    startAffix,
    endAffix,
    placeholder,
    className,
    onChange,
}: RowFieldProps) {
    const labelId = `${id}-label`;
    const errorId = `${id}-error`;

    return (
        <div className={cn('min-w-0 space-y-1', className)}>
            <Label id={labelId} htmlFor={id} className="md:sr-only">
                {label}
            </Label>
            <AffixInput
                id={id}
                name={name}
                type="text"
                inputMode="decimal"
                autoComplete="off"
                value={value}
                placeholder={placeholder}
                startAffix={startAffix}
                endAffix={endAffix}
                aria-labelledby={`${labelId} ${nameId}`}
                aria-required={true}
                aria-invalid={error ? true : undefined}
                aria-describedby={
                    [error ? errorId : null, extraDescription]
                        .filter(Boolean)
                        .join(' ') || undefined
                }
                className="h-11 md:h-9"
                onChange={(event) => onChange(event.target.value)}
            />
            {error && (
                <p id={errorId} className="text-sm text-destructive-foreground">
                    {error}
                </p>
            )}
        </div>
    );
}
