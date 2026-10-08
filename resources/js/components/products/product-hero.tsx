import type { ReactNode } from 'react';
import { Money } from '@/components/shared/money';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Capacity, Decimal } from '@/types/domain';

type ProductHeroProps = {
    capacity: Capacity;
    unitCost: Decimal;
    /** Sales of the last 30 days. */
    sales: { units_sold: number; revenue: Decimal };
};

function HeroCard({
    label,
    ariaLabel,
    primary = false,
    children,
}: {
    label: string;
    ariaLabel: string;
    primary?: boolean;
    children: ReactNode;
}) {
    return (
        <section
            aria-label={ariaLabel}
            className={cn(
                'flex flex-col gap-1 rounded-xl border px-5 py-[18px]',
                primary
                    ? 'border-primary bg-primary text-primary-foreground'
                    : 'bg-card text-card-foreground',
            )}
        >
            <span
                className={cn(
                    'text-[0.8125rem] font-semibold',
                    !primary && 'text-muted-foreground',
                )}
            >
                {label}
            </span>
            {children}
        </section>
    );
}

const BIG = 'text-[2rem] leading-[1.1] font-bold tracking-tight tabular-nums';
const BIG_TEXT = 'text-[1.625rem] leading-[1.2] font-bold tracking-tight';
const UNITS = 'ml-1 text-lg font-semibold';

function unitsWord(count: number): string {
    return count === 1 ? 'unit' : 'units';
}

/**
 * The three numbers that matter on a product: how many can be produced now
 * (the primary card), what one unit costs, and what sold in the last 30 days.
 */
export function ProductHero({ capacity, unitCost, sales }: ProductHeroProps) {
    const { locale } = useAppSettings();
    const { max_producible: producible } = capacity;

    return (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
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
                            {formatCount(producible, locale)}
                            <span className={UNITS}>
                                {unitsWord(producible)}
                            </span>
                        </span>
                        <span className="text-[0.8125rem] text-primary-foreground/85">
                            from current stock
                        </span>
                    </>
                )}
            </HeroCard>

            <HeroCard label="Unit cost" ariaLabel="Unit cost">
                <span className={BIG}>
                    <Money value={unitCost} />
                </span>
                <span className="text-[0.8125rem] text-muted-foreground">
                    materials, labor and overhead
                </span>
            </HeroCard>

            <HeroCard
                label="Sold · last 30 days"
                ariaLabel="Sales, last 30 days"
            >
                <span className={BIG}>
                    {formatCount(sales.units_sold, locale)}
                    <span className={UNITS}>{unitsWord(sales.units_sold)}</span>
                </span>
                <span className="text-[0.8125rem] text-muted-foreground">
                    <Money value={sales.revenue} /> in sales
                </span>
            </HeroCard>
        </div>
    );
}
