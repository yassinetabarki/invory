import { Fragment } from 'react';
import type { ReactNode } from 'react';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount, formatMoneyParts } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Capacity, Decimal } from '@/types/domain';

type ProductHeroProps = {
    capacity: Capacity;
    unitCost: Decimal;
    /** The product has more than one size (S-58). */
    multipleSizes: boolean;
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
/** The unit word after a count; the count and the word are separated by a real space in the text. */
export const UNITS = 'text-lg font-semibold';

export function unitsWord(count: number): string {
    return count === 1 ? 'unit' : 'units';
}

/**
 * The unit cost at the big size, its currency part (`TND`, `€`) at the size of
 * "units" so the figure fits a card of the row. The pieces join to exactly the
 * `formatMoney` text; a `<wbr>` after the space lets a large cost put its
 * amount under the code. Parts are told apart by type, never by position.
 */
function UnitCostFigure({ value }: { value: Decimal }) {
    const { currency, locale } = useAppSettings();
    const parts = formatMoneyParts(value, { currency, locale });

    return (
        <span className={BIG}>
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

                return part.type === 'literal' && beside ? (
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
    stock,
}: ProductHeroProps) {
    const { locale } = useAppSettings();
    const { max_producible: producible } = capacity;

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
                {producible === 0 && (
                    <>
                        <span className={BIG_TEXT}>Can&apos;t produce</span>
                        <span className="text-[0.8125rem] text-primary-foreground/85">
                            with current stock
                        </span>
                    </>
                )}
                {producible !== null && producible > 0 && (
                    <>
                        <span className={BIG}>
                            {formatCount(producible, locale)}{' '}
                            <span className={UNITS}>
                                {unitsWord(producible)}
                            </span>
                        </span>
                        <span className="text-[0.8125rem] text-primary-foreground/85">
                            {multipleSizes
                                ? 'from current stock, any mix of sizes'
                                : 'from current stock'}
                        </span>
                    </>
                )}
            </HeroCard>

            {stock}

            <HeroCard label="Unit cost" ariaLabel="Unit cost">
                <UnitCostFigure value={unitCost} />
                <span className="text-[0.8125rem] text-muted-foreground">
                    {multipleSizes
                        ? 'same for every size'
                        : 'materials, labor and overhead'}
                </span>
            </HeroCard>
        </div>
    );
}
