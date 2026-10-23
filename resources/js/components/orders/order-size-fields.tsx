import type { ReactNode } from 'react';
import { formatUnits } from '@/components/orders/stage-figure';
import { FormField } from '@/components/products/form-field';
import { Input } from '@/components/ui/input';
import { useAppSettings } from '@/hooks/use-app-settings';
import type { Size } from '@/types/domain';

type OrderSizeFieldsProps = {
    /** Prefix that makes the element ids unique on the page. */
    idPrefix: string;
    /** The group's name for screen readers (not shown unless `showLegend`). */
    legend: string;
    /** Shows the legend as the question above the fields. */
    showLegend?: boolean;
    /** The sizes, in the order they show. */
    sizes: readonly Size[];
    /** What was typed, by size id; a missing or empty size counts 0. */
    values: Readonly<Record<number, string | undefined>>;
    onChange: (sizeId: number, value: string) => void;
    /** The server's message for each size, by size id: it shows under that size. */
    errors?: Readonly<Record<number, string | undefined>>;
    /** An optional hint under a size, by size id. */
    hints?: Readonly<Record<number, ReactNode>>;
    /** The server's message for the line as a whole: it shows under "Total". */
    totalError?: string;
    /** Id of a message about the whole group that shows elsewhere: every field is marked invalid and points at it. */
    messageId?: string;
    /** The "Total N units" line; on by default. */
    showTotal?: boolean;
};

/** The whole number typed; an empty or non-numeric field counts 0. */
function typedUnits(value: string | undefined): number {
    const text = (value ?? '').trim();

    return /^\d+$/.test(text) ? Number(text) : 0;
}

/**
 * One small field per size, each labelled with its size, wrapping onto several
 * rows on a phone (11 shoe sizes fit without sideways scrolling), and under
 * them "Total N units": the whole numbers typed, added up as you type. Nothing
 * is validated here; the server's messages show under their size or the total.
 */
export function OrderSizeFields({
    idPrefix,
    legend,
    showLegend = false,
    sizes,
    values,
    onChange,
    errors = {},
    hints = {},
    totalError,
    messageId,
    showTotal = true,
}: OrderSizeFieldsProps) {
    const { locale } = useAppSettings();
    const totalErrorId = `${idPrefix}-total-error`;
    // The message about the whole group: shown under the fields, or elsewhere (`messageId`).
    const wholeId = totalError ? totalErrorId : messageId;
    const total = sizes.reduce(
        (sum, size) => sum + typedUnits(values[size.id]),
        0,
    );

    return (
        <fieldset className="flex min-w-0 flex-col gap-3">
            <legend
                className={
                    showLegend
                        ? 'mb-3 text-sm leading-none font-medium'
                        : 'sr-only'
                }
            >
                {legend}
            </legend>
            <div className="flex flex-wrap gap-3">
                {sizes.map((size) => {
                    const error = errors[size.id];

                    return (
                        <FormField
                            key={size.id}
                            id={`${idPrefix}-size-${size.id}`}
                            label={size.label}
                            hint={hints[size.id]}
                            error={error}
                            // A message needs room: that size takes a row of its own.
                            className={error ? 'basis-full' : 'w-24'}
                        >
                            {(control) => (
                                <Input
                                    {...control}
                                    type="text"
                                    inputMode="numeric"
                                    autoComplete="off"
                                    className="w-24"
                                    aria-invalid={
                                        control['aria-invalid'] ??
                                        (wholeId ? true : undefined)
                                    }
                                    aria-describedby={
                                        [control['aria-describedby'], wholeId]
                                            .filter(Boolean)
                                            .join(' ') || undefined
                                    }
                                    value={values[size.id] ?? ''}
                                    onChange={(event) =>
                                        onChange(size.id, event.target.value)
                                    }
                                />
                            )}
                        </FormField>
                    );
                })}
            </div>
            {(showTotal || totalError) && (
                <div className="flex flex-col gap-1">
                    {showTotal && (
                        <p className="text-sm font-medium">
                            Total {formatUnits(total, locale)}
                        </p>
                    )}
                    {totalError && (
                        <p
                            id={totalErrorId}
                            className="text-sm text-destructive-foreground"
                        >
                            {totalError}
                        </p>
                    )}
                </div>
            )}
        </fieldset>
    );
}
