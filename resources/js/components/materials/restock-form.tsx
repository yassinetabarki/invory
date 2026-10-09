import { Form, usePage } from '@inertiajs/react';
import { useEffect, useId, useRef, useState } from 'react';
import MaterialRestockController from '@/actions/App/Http/Controllers/MaterialRestockController';
import { currencySymbol } from '@/components/materials/currency-symbol';
import { AffixInput, FormField } from '@/components/materials/form-field';
import { useFocusFirstInvalid } from '@/components/materials/use-focus-first-invalid';
import { Money } from '@/components/shared/money';
import { Quantity } from '@/components/shared/quantity';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useAppSettings } from '@/hooks/use-app-settings';
import { invalidatedBy } from '@/lib/cache-tags';
import { formatQuantity } from '@/lib/format';
import type { Material } from '@/types/domain';

type RestockFormProps = {
    material: Material;
    onCancel: () => void;
    /** Called when the server accepted the restock (the container closes). */
    onSuccess: () => void;
    /** Focus the first field on mount (inline panels; a dialog does it itself). */
    autoFocus?: boolean;
};

/** Today in the browser's timezone, only a fallback for `app.today`. */
function localDate(): string {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${month}-${day}`;
}

/** The server's "today" (`YYYY-MM-DD`, app timezone): default and maximum date. */
function useToday(): string {
    const app: Partial<{ today: string }> | null | undefined =
        usePage().props.app;

    return app?.today || localDate();
}

/** "In stock now: 900 pc · unit cost €0.35", shown under the title. */
export function RestockSummary({ material }: { material: Material }) {
    return (
        <>
            In stock now:{' '}
            <strong className="font-semibold text-foreground">
                <Quantity
                    value={material.quantity_on_hand}
                    unit={material.unit}
                />
            </strong>{' '}
            · unit cost <Money value={material.unit_cost} precise />
        </>
    );
}

/**
 * "Use this price as the new unit cost". Always submits `update_unit_cost` as
 * `1` or `0` through a hidden input, so an unchecked box is still sent.
 */
function UpdateUnitCostField({ error }: { error?: string }) {
    const id = useId();
    const [checked, setChecked] = useState(true);

    return (
        <div className="space-y-1">
            <div className="flex items-center gap-2">
                <Checkbox
                    id={id}
                    checked={checked}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={error ? `${id}-error` : undefined}
                    onCheckedChange={(next) => setChecked(next === true)}
                />
                <Label htmlFor={id} className="font-medium">
                    Use this price as the new unit cost
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

type RestockFieldsProps = Pick<
    RestockFormProps,
    'material' | 'onCancel' | 'autoFocus'
> & {
    errors: Partial<Record<string, string>>;
    processing: boolean;
};

function RestockFields({
    material,
    onCancel,
    autoFocus = false,
    errors,
    processing,
}: RestockFieldsProps) {
    const uid = useId();
    const today = useToday();
    const { currency, locale } = useAppSettings();
    const containerRef = useRef<HTMLDivElement>(null);
    const quantityRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (autoFocus) {
            quantityRef.current?.focus();
        }
    }, [autoFocus]);

    useFocusFirstInvalid(containerRef, errors, processing);

    // The placeholder is the current unit cost as plain digits ("0.35").
    const currentCost = formatQuantity(material.unit_cost, { locale });

    return (
        <div ref={containerRef} className="flex flex-col gap-4">
            <div className="grid grid-cols-[repeat(auto-fit,minmax(9rem,1fr))] gap-3">
                <FormField
                    id={`${uid}-quantity`}
                    label="Quantity received"
                    required
                    error={errors.quantity}
                >
                    {(control) => (
                        <AffixInput
                            {...control}
                            ref={quantityRef}
                            name="quantity"
                            type="text"
                            inputMode="decimal"
                            autoComplete="off"
                            placeholder="0"
                            endAffix={material.unit}
                        />
                    )}
                </FormField>
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
                            placeholder={currentCost}
                            startAffix={currencySymbol(currency, locale)}
                        />
                    )}
                </FormField>
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
                        />
                    )}
                </FormField>
            </div>

            <UpdateUnitCostField error={errors.update_unit_cost} />

            <div className="flex flex-wrap items-center justify-between gap-3">
                <p className="text-xs text-muted-foreground">
                    Logged in the material&apos;s history. The new stock is
                    calculated when you save.
                </p>
                <div className="flex gap-2">
                    <Button type="button" variant="outline" onClick={onCancel}>
                        Cancel
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing && <Spinner />}
                        Add to stock
                    </Button>
                </div>
            </div>
        </div>
    );
}

/**
 * The restock quick form: quantity received, unit price paid, date, reference
 * and "use this price as the new unit cost". Posts to the restock route with
 * Inertia's `<Form>` (scroll and state are kept, so the list stays where it is)
 * and calls `onSuccess` so its container (inline panel or dialog) can close.
 * The new stock is calculated by the server, never here.
 */
export function RestockForm({
    material,
    onCancel,
    onSuccess,
    autoFocus,
}: RestockFormProps) {
    return (
        <Form
            {...MaterialRestockController.store.form(material.id)}
            options={{ preserveScroll: true, preserveState: true }}
            invalidateCacheTags={invalidatedBy('material')}
            onSuccess={onSuccess}
        >
            {({ errors, processing }) => (
                <RestockFields
                    material={material}
                    onCancel={onCancel}
                    autoFocus={autoFocus}
                    errors={errors}
                    processing={processing}
                />
            )}
        </Form>
    );
}
