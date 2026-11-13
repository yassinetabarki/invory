import { Form, usePage } from '@inertiajs/react';
import { useId, useRef, useState } from 'react';
import type { RefObject } from 'react';
import MaterialRestockController from '@/actions/App/Http/Controllers/MaterialRestockController';
import { currencySymbol } from '@/components/materials/currency-symbol';
import { AffixInput, FormField } from '@/components/materials/form-field';
import { RestockColourLine } from '@/components/materials/restock-colour-line';
import { useFocusFirstInvalid } from '@/components/materials/use-focus-first-invalid';
import { Money } from '@/components/shared/money';
import { Quantity } from '@/components/shared/quantity';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount, formatQuantity } from '@/lib/format';
import type { Material } from '@/types/domain';

/** More colours than this and Find colour appears above the lines. */
const FIND_FROM = 8;

type RestockSheetProps = {
    /** A per-colour material (its `colours` are the receipt's lines). */
    material: Material;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** The colour whose Quantity received gets focus when the sheet opens. */
    initialColourId?: number | null;
};

type Errors = Partial<Record<string, string>>;

/** The server's "today" (`YYYY-MM-DD`, app timezone): default and maximum date. */
function useToday(): string {
    const { app } = usePage<{ app?: { today?: string } | null }>().props;

    if (app?.today) {
        return app.today;
    }

    const now = new Date();

    return [
        now.getFullYear(),
        String(now.getMonth() + 1).padStart(2, '0'),
        String(now.getDate()).padStart(2, '0'),
    ].join('-');
}

/** "In stock now: 290 m in 2 colours · cost per colour" (or "· unit cost TND 8.500"). */
function SheetSummary({ material }: { material: Material }) {
    const { locale } = useAppSettings();
    const count = material.colours.length;

    return (
        <>
            In stock now:{' '}
            <strong className="font-semibold text-foreground">
                <Quantity
                    value={material.quantity_on_hand}
                    unit={material.unit}
                />
            </strong>{' '}
            in {formatCount(count, locale)} {count === 1 ? 'colour' : 'colours'}{' '}
            ·{' '}
            {material.cost_mode === 'per_colour' ? (
                'cost per colour'
            ) : (
                <>
                    unit cost <Money value={material.unit_cost} precise />
                </>
            )}
        </>
    );
}

/** Submits `update_unit_cost` as `1` or `0` through a hidden input, so an unchecked box is still sent. */
function UpdateCostsField({ label, error }: { label: string; error?: string }) {
    const id = useId();
    const [checked, setChecked] = useState(true);

    return (
        <div className="space-y-1">
            <div className="flex min-h-11 items-center gap-2">
                <Checkbox
                    id={id}
                    checked={checked}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={error ? `${id}-error` : undefined}
                    onCheckedChange={(next) => setChecked(next === true)}
                />
                <Label htmlFor={id} className="font-medium">
                    {label}
                </Label>
                <input
                    type="hidden"
                    name="update_unit_cost"
                    value={checked ? '1' : '0'}
                />
            </div>
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

type FieldsProps = {
    material: Material;
    errors: Errors;
    processing: boolean;
    initialColourId?: number | null;
    focusRef: RefObject<HTMLInputElement | null>;
    onCancel: () => void;
};

function RestockFields({
    material,
    errors,
    processing,
    initialColourId,
    focusRef,
    onCancel,
}: FieldsProps) {
    const uid = useId();
    const today = useToday();
    const { currency, locale } = useAppSettings();
    const containerRef = useRef<HTMLDivElement>(null);
    const [quantities, setQuantities] = useState<Record<number, string>>({});
    const [find, setFind] = useState('');
    const withCost = material.cost_mode === 'per_colour';
    const query = find.trim().toLowerCase();
    const received = Object.values(quantities).filter(
        (value) => value.trim() !== '',
    ).length;

    useFocusFirstInvalid(containerRef, errors, processing);

    return (
        <>
            <div
                ref={containerRef}
                className="flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto px-4 pb-4"
            >
                <div className="grid gap-3 sm:grid-cols-2">
                    <FormField
                        id={`${uid}-received-on`}
                        label="Date"
                        required
                        error={errors.received_on}
                    >
                        {(control) => (
                            <Input
                                {...control}
                                name="received_on"
                                type="date"
                                defaultValue={today}
                                max={today}
                                className="h-11"
                            />
                        )}
                    </FormField>
                    <FormField
                        id={`${uid}-reference`}
                        label="Reference"
                        optional
                        error={errors.reference}
                    >
                        {(control) => (
                            <Input
                                {...control}
                                name="reference"
                                type="text"
                                autoComplete="off"
                                maxLength={64}
                                placeholder="PO or delivery note"
                                className="h-11"
                            />
                        )}
                    </FormField>
                </div>

                {!withCost && (
                    <FormField
                        id={`${uid}-unit-price`}
                        label="Unit price paid"
                        required
                        error={errors.unit_price}
                    >
                        {(control) => (
                            <AffixInput
                                {...control}
                                name="unit_price"
                                type="text"
                                inputMode="decimal"
                                autoComplete="off"
                                placeholder={formatQuantity(
                                    material.unit_cost,
                                    { locale },
                                )}
                                startAffix={currencySymbol(currency, locale)}
                                className="h-11"
                            />
                        )}
                    </FormField>
                )}

                <p className="text-sm text-muted-foreground">
                    Type a quantity for each colour you received. Leave the
                    others empty.
                </p>
                {errors.lines && (
                    <p
                        role="alert"
                        className="text-sm text-destructive-foreground"
                    >
                        {errors.lines}
                    </p>
                )}

                {material.colours.length > FIND_FROM && (
                    <FormField id={`${uid}-find`} label="Find colour">
                        {(control) => (
                            <Input
                                {...control}
                                type="search"
                                autoComplete="off"
                                className="h-11"
                                value={find}
                                onChange={(event) =>
                                    setFind(event.target.value)
                                }
                            />
                        )}
                    </FormField>
                )}

                <div className="space-y-3">
                    {material.colours.map((row) => {
                        const id = row.colour.id;
                        const quantityError = errors[`lines.${id}.quantity`];
                        const priceError = errors[`lines.${id}.unit_price`];
                        const matches =
                            query === '' ||
                            row.colour.name.toLowerCase().includes(query);

                        return (
                            <RestockColourLine
                                key={id}
                                material={material}
                                row={row}
                                quantity={quantities[id] ?? ''}
                                onQuantityChange={(value) =>
                                    setQuantities((current) => ({
                                        ...current,
                                        [id]: value,
                                    }))
                                }
                                quantityError={quantityError}
                                priceError={priceError}
                                quantityRef={
                                    id === initialColourId
                                        ? focusRef
                                        : undefined
                                }
                                hidden={
                                    !matches && !quantityError && !priceError
                                }
                            />
                        );
                    })}
                </div>

                <UpdateCostsField
                    label={
                        withCost
                            ? 'Use these prices as the new unit costs'
                            : 'Use this price as the new unit cost'
                    }
                    error={errors.update_unit_cost}
                />
            </div>

            <div className="space-y-3 border-t bg-background p-4">
                <p className="text-xs text-muted-foreground">
                    Logged in the material&apos;s history. The new stock is
                    calculated when you save.
                </p>
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <p aria-live="polite" className="text-sm font-medium">
                        {formatCount(received, locale)}{' '}
                        {received === 1 ? 'colour' : 'colours'} in this receipt
                    </p>
                    <div className="flex gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            className="h-11 md:h-9"
                            onClick={onCancel}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            className="h-11 md:h-9"
                            disabled={processing}
                        >
                            {processing && <Spinner />}
                            Add to stock
                        </Button>
                    </div>
                </div>
            </div>
        </>
    );
}

/**
 * The Restock side sheet of a per-colour material (INV-21): Date, Reference,
 * one price (same cost) and one line per colour, with Quantity received and,
 * with cost per colour, its own Unit price paid. Posts `lines[<colourId>]` for
 * every colour (empty = not received) to the restock route; the server writes
 * the entries and the toast. Open it from anywhere with the material and,
 * optionally, the colour whose quantity field should get focus. Closing
 * returns focus to the element that had it when the sheet opened.
 */
export function RestockSheet({
    material,
    open,
    onOpenChange,
    initialColourId = null,
}: RestockSheetProps) {
    const focusRef = useRef<HTMLInputElement>(null);
    // Radix returns focus to a trigger only; this sheet has none, so keep the opener.
    const openerRef = useRef<Element | null>(null);

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                className="w-full gap-0 sm:max-w-lg"
                onOpenAutoFocus={(event) => {
                    openerRef.current = document.activeElement;

                    if (focusRef.current) {
                        event.preventDefault();
                        focusRef.current.focus();
                    }
                }}
                onCloseAutoFocus={(event) => {
                    event.preventDefault();

                    if (openerRef.current instanceof HTMLElement) {
                        openerRef.current.focus();
                    }
                }}
            >
                <SheetHeader className="pr-12">
                    <SheetTitle>Restock {material.name}</SheetTitle>
                    <SheetDescription>
                        <SheetSummary material={material} />
                    </SheetDescription>
                </SheetHeader>
                <Form
                    {...MaterialRestockController.store.form(material.id)}
                    options={{ preserveScroll: true, preserveState: true }}
                    onSuccess={() => onOpenChange(false)}
                    className="flex min-h-0 flex-1 flex-col"
                >
                    {({ errors, processing }) => (
                        <RestockFields
                            material={material}
                            errors={errors}
                            processing={processing}
                            initialColourId={initialColourId}
                            focusRef={focusRef}
                            onCancel={() => onOpenChange(false)}
                        />
                    )}
                </Form>
            </SheetContent>
        </Sheet>
    );
}
