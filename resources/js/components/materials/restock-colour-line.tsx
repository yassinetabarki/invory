import { useId } from 'react';
import type { Ref } from 'react';
import { currencySymbol } from '@/components/materials/currency-symbol';
import { AffixInput, FormField } from '@/components/materials/form-field';
import { ColourTag } from '@/components/shared/colour-tag';
import { Quantity } from '@/components/shared/quantity';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatQuantity } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Material, MaterialColourStock } from '@/types/domain';

type RestockColourLineProps = {
    material: Material;
    row: MaterialColourStock;
    /** What is typed in Quantity received (the sheet counts the filled lines). */
    quantity: string;
    onQuantityChange: (value: string) => void;
    quantityError?: string;
    priceError?: string;
    /** For focusing the field when the sheet opens from this colour's Restock. */
    quantityRef?: Ref<HTMLInputElement>;
    /** Left out of view by Find colour; the line stays mounted so its values stay. */
    hidden?: boolean;
};

/**
 * One colour of the receipt: its name, "Now 40 m", Quantity received and, with
 * cost per colour, its own Unit price paid. Fields are named
 * `lines[<colourId>][quantity|unit_price]`; an empty quantity is a colour that
 * was not received.
 */
export function RestockColourLine({
    material,
    row,
    quantity,
    onQuantityChange,
    quantityError,
    priceError,
    quantityRef,
    hidden = false,
}: RestockColourLineProps) {
    const uid = useId();
    const { currency, locale } = useAppSettings();
    const withCost = material.cost_mode === 'per_colour';
    const key = `lines[${row.colour.id}]`;

    return (
        <div
            role="group"
            aria-labelledby={`${uid}-name`}
            hidden={hidden}
            className="space-y-3 rounded-lg border p-4"
        >
            <div>
                <p id={`${uid}-name`} className="font-semibold">
                    <ColourTag colour={row.colour} />
                </p>
                <p className="mt-0.5 text-xs text-muted-foreground">
                    Now{' '}
                    <Quantity
                        value={row.quantity_on_hand}
                        unit={material.unit}
                    />
                </p>
            </div>
            <div className={cn('grid gap-3', withCost && 'grid-cols-2')}>
                <FormField
                    id={`${uid}-quantity`}
                    label="Quantity received"
                    error={quantityError}
                >
                    {(control) => (
                        <AffixInput
                            {...control}
                            ref={quantityRef}
                            name={`${key}[quantity]`}
                            type="text"
                            inputMode="decimal"
                            autoComplete="off"
                            placeholder="0"
                            endAffix={material.unit}
                            className="h-11"
                            value={quantity}
                            onChange={(event) =>
                                onQuantityChange(event.target.value)
                            }
                        />
                    )}
                </FormField>
                {withCost && (
                    <FormField
                        id={`${uid}-unit-price`}
                        label="Unit price paid"
                        error={priceError}
                    >
                        {(control) => (
                            <AffixInput
                                {...control}
                                name={`${key}[unit_price]`}
                                type="text"
                                inputMode="decimal"
                                autoComplete="off"
                                placeholder={formatQuantity(row.unit_cost, {
                                    locale,
                                })}
                                startAffix={currencySymbol(currency, locale)}
                                className="h-11"
                            />
                        )}
                    </FormField>
                )}
            </div>
        </div>
    );
}
