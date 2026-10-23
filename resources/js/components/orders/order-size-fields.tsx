import type { ReactNode } from 'react';
import { formatUnits } from '@/components/orders/stage-figure';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAppSettings } from '@/hooks/use-app-settings';
import { cn } from '@/lib/utils';
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
    /** The server's message for each size, by size id: it shows under the fields, as sent, and describes that size's field. */
    errors?: Readonly<Record<number, string | undefined>>;
    /** An optional hint under a size, by size id. */
    hints?: Readonly<Record<number, ReactNode>>;
    /** The server's message for the line as a whole: it shows under "Total", before the sizes' messages. */
    totalError?: string;
    /** Id of a message about the whole group that shows elsewhere: every field is marked invalid and points at it. */
    messageId?: string;
    /** The "Total N units" line; on by default. */
    showTotal?: boolean;
    /** Sizes (by id) whose field is highlighted as changed. */
    changed?: ReadonlySet<number>;
};

/** The whole number typed; an empty or non-numeric field counts 0. */
export function typedUnits(value: string | undefined): number {
    const text = (value ?? '').trim();

    return /^\d+$/.test(text) ? Number(text) : 0;
}

/**
 * One small field per size (the size small and bold above, the figure centred),
 * wrapping onto several rows on a phone (11 shoe sizes fit without sideways
 * scrolling), and under them "Total N units": the whole numbers typed, added up
 * as you type. Nothing is validated here. A field in error keeps its place; the
 * server's messages show under the Total (or the fields), the line's first and
 * then one per size in size order, and each field is described by its own.
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
    changed,
}: OrderSizeFieldsProps) {
    const { locale } = useAppSettings();
    const totalErrorId = `${idPrefix}-total-error`;
    // The message about the whole group: shown under the fields, or elsewhere (`messageId`).
    const wholeId = totalError ? totalErrorId : messageId;
    const sizesInError = sizes.filter((size) => errors[size.id]);
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
            {/* One wrapping grid: its columns fill the row (four at 375 px, three at 320 px); from tablet width a field keeps a fixed width.
                At 16 px "1000000" is 67 px wide, so a field has no side padding and slightly tighter figures on a phone: four of them, 7 digits whole, fit a 275 px row. */}
            <div
                data-slot="size-fields"
                className="grid grid-cols-[repeat(auto-fill,minmax(4rem,1fr))] items-start gap-x-1 gap-y-2 md:grid-cols-[repeat(auto-fill,5rem)] md:gap-x-2"
            >
                {sizes.map((size) => {
                    const id = `${idPrefix}-size-${size.id}`;
                    const hint = hints[size.id];
                    const error = errors[size.id];
                    // Its own hint, then its own message, then the message about the whole group.
                    const describedBy = [
                        hint ? `${id}-hint` : null,
                        error ? `${id}-error` : null,
                        wholeId,
                    ]
                        .filter(Boolean)
                        .join(' ');

                    return (
                        <div
                            key={size.id}
                            className="flex min-w-0 flex-col items-center gap-1"
                        >
                            <Label
                                htmlFor={id}
                                className="text-[13px] leading-none font-bold"
                            >
                                {size.label}
                            </Label>
                            <Input
                                id={id}
                                type="text"
                                inputMode="numeric"
                                autoComplete="off"
                                className={cn(
                                    'h-11 px-0 text-center tracking-tighter tabular-nums md:px-2 md:tracking-normal',
                                    changed?.has(size.id) &&
                                        'border-primary ring-[3px] ring-primary/15',
                                )}
                                aria-invalid={
                                    error || wholeId ? true : undefined
                                }
                                aria-describedby={describedBy || undefined}
                                value={values[size.id] ?? ''}
                                onChange={(event) =>
                                    onChange(size.id, event.target.value)
                                }
                            />
                            {hint && (
                                <p
                                    id={`${id}-hint`}
                                    className="flex flex-wrap items-center justify-center gap-x-1 text-center text-xs text-muted-foreground"
                                >
                                    {hint}
                                </p>
                            )}
                        </div>
                    );
                })}
            </div>
            {(showTotal || totalError || sizesInError.length > 0) && (
                <div className="flex flex-col gap-1">
                    {showTotal && (
                        <p className="text-sm font-medium">
                            Total {formatUnits(total, locale)}
                        </p>
                    )}
                    {/* The messages, each as the server sent it: the line's own, then one per size in size order. */}
                    {totalError && (
                        <p
                            id={totalErrorId}
                            className="text-sm text-destructive-foreground"
                        >
                            {totalError}
                        </p>
                    )}
                    {sizesInError.map((size) => (
                        <p
                            key={size.id}
                            id={`${idPrefix}-size-${size.id}-error`}
                            className="text-sm text-destructive-foreground"
                        >
                            {errors[size.id]}
                        </p>
                    ))}
                </div>
            )}
        </fieldset>
    );
}
