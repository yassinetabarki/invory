import type { ReactNode } from 'react';
import { typedUnits } from '@/components/orders/order-size-fields';
import { ColourSwatch, ColourTag } from '@/components/shared/colour-tag';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { FinishedStock, FinishedStockColour } from '@/types/domain';

/** What each field holds, as text: by colour id, then size id. */
export type Typed = Record<number, Record<number, string>>;

/** The id of one field (one colour, one size): the window focuses it after a refusal. */
export function fieldId(uid: string, colourId: number, sizeId: number): string {
    return `${uid}-c${colourId}-size-${sizeId}`;
}

/** What each field holds when the window opens: today's count, as text. */
export function countsOf(stock: FinishedStock): Typed {
    return Object.fromEntries(
        stock.colours.map(({ colour, sizes }) => [
            colour.id,
            Object.fromEntries(
                sizes.map(({ size, units }) => [size.id, String(units)]),
            ),
        ]),
    );
}

/** The sizes of a colour whose text differs from today's count (they show "was N"). */
export function changedSizes(
    { colour, sizes }: FinishedStockColour,
    values: Typed,
): Set<number> {
    return new Set(
        sizes
            .filter(
                ({ size, units }) =>
                    (values[colour.id]?.[size.id] ?? '').trim() !==
                    String(units),
            )
            .map(({ size }) => size.id),
    );
}

/** The colour's total after saving: the whole numbers typed (an empty field counts 0). */
export function typedTotal(
    { colour, sizes }: FinishedStockColour,
    values: Typed,
): number {
    return sizes.reduce(
        (sum, { size }) => sum + typedUnits(values[colour.id]?.[size.id]),
        0,
    );
}

export type PillState = {
    changed: boolean;
    errors: number;
    total: number;
};

/** "1 error", "2 errors". */
export function errorsWord(count: number): string {
    return `${count} ${count === 1 ? 'error' : 'errors'}`;
}

/**
 * The colour pills: a radio group named "Colour" (arrow keys move between the
 * pills). Each shows the swatch, the name and the colour's total after saving in
 * grey; a small dot marks a colour with a changed size and "N errors" (text, in
 * red) one with refused numbers. The name screen readers hear carries both:
 * "Indigo, 10, changed".
 */
export function ColourPills({
    name,
    colours,
    states,
    activeId,
    onSelect,
}: {
    /** Groups the radios. */
    name: string;
    colours: FinishedStockColour[];
    /** By colour id. */
    states: Record<number, PillState>;
    activeId: number;
    onSelect: (colourId: number) => void;
}) {
    const { locale } = useAppSettings();

    return (
        <div
            role="radiogroup"
            aria-label="Colour"
            className="flex flex-wrap gap-2"
        >
            {colours.map(({ colour }) => {
                const { changed, errors, total } = states[colour.id];
                const spoken = [
                    colour.name,
                    formatCount(total, locale),
                    ...(changed ? ['changed'] : []),
                    ...(errors > 0 ? [errorsWord(errors)] : []),
                ].join(', ');

                return (
                    <label
                        key={colour.id}
                        className="relative inline-flex min-h-11 max-w-full min-w-0 cursor-pointer items-center gap-2 rounded-full border border-input bg-card px-3 text-sm has-checked:border-primary has-checked:bg-accent has-focus-visible:ring-[3px] has-focus-visible:ring-ring"
                    >
                        <input
                            type="radio"
                            name={name}
                            className="sr-only"
                            aria-label={spoken}
                            checked={colour.id === activeId}
                            onChange={() => onSelect(colour.id)}
                        />
                        <ColourSwatch swatch={colour.swatch} />
                        <span className="min-w-0 [overflow-wrap:anywhere]">
                            {colour.name}
                        </span>
                        <span className="text-muted-foreground tabular-nums">
                            {formatCount(total, locale)}
                        </span>
                        {changed && (
                            <span
                                aria-hidden="true"
                                className="size-2 shrink-0 rounded-full bg-primary"
                            />
                        )}
                        {errors > 0 && (
                            <span className="text-xs font-medium text-destructive-foreground">
                                {errorsWord(errors)}
                            </span>
                        )}
                    </label>
                );
            })}
        </div>
    );
}

/**
 * A "One size" product: no pills, one field per colour (swatch and name), under
 * "Units in stock per colour". Each shows "was N" when changed and the server's
 * message for it, as sent, under it.
 */
export function ColourFields({
    uid,
    colours,
    values,
    onChange,
    errors,
    hints,
    messageId,
}: {
    uid: string;
    colours: FinishedStockColour[];
    values: Typed;
    onChange: (colourId: number, sizeId: number, value: string) => void;
    /** The server's messages, by `stock.{colourId}.{sizeId}`. */
    errors: Readonly<Record<string, string | undefined>>;
    /** "was N" by colour id. */
    hints: Readonly<Record<number, ReactNode>>;
    /** Id of a message about the whole window, shown elsewhere. */
    messageId?: string;
}) {
    return (
        <fieldset className="flex min-w-0 flex-col gap-4">
            <legend className="mb-3 text-sm leading-none font-medium">
                Units in stock per colour
            </legend>
            {colours.map((entry) => {
                const { colour, sizes } = entry;
                const size = sizes[0].size;
                const id = fieldId(uid, colour.id, size.id);
                const error = errors[`stock.${colour.id}.${size.id}`];
                const hint = hints[colour.id];
                const describedBy = [
                    hint ? `${id}-hint` : null,
                    error ? `${id}-error` : null,
                    messageId,
                ]
                    .filter(Boolean)
                    .join(' ');

                return (
                    <div key={colour.id} className="flex flex-col gap-1.5">
                        <Label htmlFor={id} className="min-w-0 text-sm">
                            <ColourTag colour={colour} />
                        </Label>
                        <Input
                            id={id}
                            type="text"
                            inputMode="numeric"
                            autoComplete="off"
                            className={cn(
                                'h-11 w-28 text-center tabular-nums',
                                hint &&
                                    'border-primary ring-[3px] ring-primary/15',
                            )}
                            aria-invalid={error || messageId ? true : undefined}
                            aria-describedby={describedBy || undefined}
                            value={values[colour.id]?.[size.id] ?? ''}
                            onChange={(event) =>
                                onChange(colour.id, size.id, event.target.value)
                            }
                        />
                        {hint && (
                            <p
                                id={`${id}-hint`}
                                className="text-xs text-muted-foreground"
                            >
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
            })}
        </fieldset>
    );
}
