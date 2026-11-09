import { Fragment } from 'react';
import type { ReactNode } from 'react';
import { useAppSettings } from '@/hooks/use-app-settings';
import {
    RANGE_SEPARATOR,
    formatCount,
    formatMoneyRangeParts,
} from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Capacity, DecimalRange } from '@/types/domain';

type ProductHeroProps = {
    capacity: Capacity;
    /** `cost.unit_cost`: lowest to highest over every colour and size (INV-16); one figure when nothing varies. */
    unitCost: DecimalRange;
    /** The product has more than one size (S-58). */
    multipleSizes: boolean;
    /** The product has more than one colour (INV-16): a one-colour product's "any mix" spans sizes only. */
    multipleColours: boolean;
    /** The "In stock" figure (S-56): the grid's second cell, with its own loading state. */
    stock: ReactNode;
};

export function HeroCard({
    label,
    ariaLabel,
    primary = false,
    action,
    children,
}: {
    label: string;
    ariaLabel: string;
    primary?: boolean;
    /** A control at the top right, on the label's row ("Update stock"). */
    action?: ReactNode;
    children: ReactNode;
}) {
    const labelText = (
        <span
            className={cn(
                'text-[0.9375rem] font-semibold',
                !primary && 'text-muted-foreground',
            )}
        >
            {label}
        </span>
    );

    return (
        <section
            aria-label={ariaLabel}
            className={cn(
                'flex min-w-0 flex-col gap-1 rounded-xl border px-5 py-[18px]',
                primary
                    ? 'border-primary bg-primary text-primary-foreground'
                    : 'bg-card text-card-foreground',
            )}
        >
            {action ? (
                <div className="flex flex-wrap items-center justify-between gap-2">
                    {labelText}
                    {action}
                </div>
            ) : (
                labelText
            )}
            {children}
        </section>
    );
}

/**
 * The row of the three figures: the size board's own rule. Equal cards that
 * wrap (two, then one) rather than go under 200 px; on a phone they stack in
 * DOM order. Shared with the page skeleton.
 */
export const HERO_ROW =
    'grid gap-4 grid-cols-[repeat(auto-fit,minmax(min(200px,100%),1fr))]';
export const BIG =
    'text-[2.25rem] leading-[1.1] font-bold tracking-tight tabular-nums';
const BIG_TEXT = 'text-[1.625rem] leading-[1.2] font-bold tracking-tight';
/** A range is twice as long as a figure: the size of the text figures, so it fits a card of the row. */
const BIG_RANGE = `${BIG_TEXT} tabular-nums`;
/** The unit word after a count; the count and the word are separated by a real space in the text. */
export const UNITS = 'text-lg font-semibold';

export function unitsWord(count: number): string {
    return count === 1 ? 'unit' : 'units';
}

/**
 * The unit cost at the big size, its currency part (`TND`, `€`) at the size of
 * "units" so the figure fits a card of the row. The pieces join to exactly the
 * `formatMoneyRange` text (the `formatMoney` text when nothing varies); a
 * `<wbr>` after the space (and after the dash of a range) lets a large cost
 * wrap. Parts are told apart by type, never by position. A range is drawn
 * smaller, as it is twice as long.
 */
function UnitCostFigure({
    varies,
    parts,
}: {
    varies: boolean;
    parts: Intl.NumberFormatPart[];
}) {
    return (
        <span className={varies ? BIG_RANGE : BIG}>
            {parts.map((part, index) => {
                if (part.type === 'currency') {
                    return (
                        <span key={index} className={UNITS}>
                            {part.value}
                        </span>
                    );
                }

                const beside = [parts[index - 1], parts[index + 1]].some(
                    (other) => other?.type === 'currency',
                );

                return part.type === 'literal' &&
                    (beside || part.value === RANGE_SEPARATOR) ? (
                    <Fragment key={index}>
                        {part.value}
                        <wbr />
                    </Fragment>
                ) : (
                    <Fragment key={index}>{part.value}</Fragment>
                );
            })}
        </span>
    );
}

/**
 * The numbers that matter on a product: how many can be produced now (the
 * primary card), how many are in stock (`stock`) and what one unit costs.
 * One row of three equal cards (`HERO_ROW`), stacked on a phone. The 30-day
 * sales figure lives under "Recent sales" (S-73).
 */
export function ProductHero({
    capacity,
    unitCost,
    multipleSizes,
    multipleColours,
    stock,
}: ProductHeroProps) {
    const { currency, locale } = useAppSettings();
    const costParts = formatMoneyRangeParts(unitCost, { currency, locale });
    const costVaries = costParts.some(
        (part) => part.type === 'literal' && part.value === RANGE_SEPARATOR,
    );
    const {
        max_producible: producible,
        best,
        by_variant: byVariant,
    } = capacity;
    // "Can't produce" only when not even the best colour and size can make a unit.
    const cantProduce = best === 0;
    // The caption names what "any mix" spans: colours too when a line varies by colour and size and the product has several (INV-16).
    const mixColours = byVariant !== null && multipleColours;
    const mix = mixColours
        ? multipleSizes
            ? 'any mix of colours and sizes'
            : 'any mix of colours'
        : multipleSizes
          ? 'any mix of sizes'
          : null;

    return (
        <div className={HERO_ROW}>
            <HeroCard
                label="Can produce now"
                ariaLabel="Production capacity"
                primary
            >
                {producible === null && (
                    <>
                        <span className={BIG_TEXT}>No composition</span>
                        <span className="text-[0.8125rem] text-primary-foreground/85">
                            Add materials to see capacity
                        </span>
                    </>
                )}
                {cantProduce && (
                    <>
                        <span className={BIG_TEXT}>Can&apos;t produce</span>
                        <span className="text-[0.8125rem] text-primary-foreground/85">
                            with current stock
                        </span>
                    </>
                )}
                {producible !== null && !cantProduce && (
                    <>
                        <span className={BIG}>
                            {formatCount(producible, locale)}{' '}
                            <span className={UNITS}>
                                {unitsWord(producible)}
                            </span>
                        </span>
                        <span className="text-[0.8125rem] text-primary-foreground/85">
                            {mix
                                ? `from current stock, ${mix}`
                                : 'from current stock'}
                        </span>
                    </>
                )}
            </HeroCard>

            {stock}

            <HeroCard label="Unit cost" ariaLabel="Unit cost">
                <UnitCostFigure varies={costVaries} parts={costParts} />
                <span className="text-[0.8125rem] text-muted-foreground">
                    {costVaries
                        ? 'varies by colour and size'
                        : multipleSizes
                          ? 'same for every size'
                          : 'materials, labor and overhead'}
                </span>
            </HeroCard>
        </div>
    );
}
