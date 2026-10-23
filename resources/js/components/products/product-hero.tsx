import type { ReactNode } from 'react';
import { Money } from '@/components/shared/money';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Capacity, Decimal } from '@/types/domain';

type SalesFigure = { units_sold: number; revenue: Decimal };

type ProductHeroProps = {
    capacity: Capacity;
    unitCost: Decimal;
    /** The product has more than one size (S-58). */
    multipleSizes: boolean;
    /** Sales of the last 30 days. */
    sales: SalesFigure;
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
                'text-[0.8125rem] font-semibold',
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
                'flex flex-col gap-1 rounded-xl border px-5 py-[18px]',
                primary
                    ? 'border-primary bg-primary text-primary-foreground'
                    : 'bg-card text-card-foreground',
            )}
        >
            {action ? (
                <div className="flex items-center justify-between gap-2">
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

export const BIG =
    'text-[2rem] leading-[1.1] font-bold tracking-tight tabular-nums';
const BIG_TEXT = 'text-[1.625rem] leading-[1.2] font-bold tracking-tight';
/** The unit word after a count; the count and the word are separated by a real space in the text. */
export const UNITS = 'text-lg font-semibold';

export function unitsWord(count: number): string {
    return count === 1 ? 'unit' : 'units';
}

function SoldCard({ sales }: { sales: SalesFigure }) {
    const { locale } = useAppSettings();

    return (
        <HeroCard label="Sold · last 30 days" ariaLabel="Sales, last 30 days">
            <span className={BIG}>
                {formatCount(sales.units_sold, locale)}{' '}
                <span className={UNITS}>{unitsWord(sales.units_sold)}</span>
            </span>
            <span className="text-[0.8125rem] text-muted-foreground">
                <Money value={sales.revenue} /> in sales
            </span>
        </HeroCard>
    );
}

/**
 * The numbers that matter on a product: how many can be produced now (the
 * primary card), how many are in stock (`stock`), what one unit costs, and
 * what sold in the last 30 days. One column on a phone, two by two from `sm`.
 */
export function ProductHero({
    capacity,
    unitCost,
    multipleSizes,
    sales,
    stock,
}: ProductHeroProps) {
    const { locale } = useAppSettings();
    const { max_producible: producible } = capacity;

    return (
        <div className="grid gap-4 sm:grid-cols-2">
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
                <span className={BIG}>
                    <Money value={unitCost} />
                </span>
                <span className="text-[0.8125rem] text-muted-foreground">
                    materials, labor and overhead
                </span>
            </HeroCard>

            <SoldCard sales={sales} />
        </div>
    );
}
