import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { isNegativeDecimal } from '@/components/products/decimals';
import { Money, MoneyRange } from '@/components/shared/money';
import { useAppSettings } from '@/hooks/use-app-settings';
import {
    RANGE_SEPARATOR,
    formatMoneyRange,
    formatPercentRange,
} from '@/lib/format';
import { cn } from '@/lib/utils';
import type {
    CostBreakdown,
    Decimal,
    DecimalRange,
    Margin,
} from '@/types/domain';

type CostBreakdownCardProps = {
    cost: CostBreakdown;
    /** Lines in the composition, for "Materials (N lines, waste included)". */
    linesCount: number;
    sellingPrice: Decimal | null;
    /** From the server; null when there is no selling price. */
    margin: Margin | null;
    /** Where "Set a price" goes: the product's edit page. */
    editHref: string;
};

function Row({
    label,
    children,
    strong = false,
    wrapRange = false,
    className,
}: {
    label: ReactNode;
    children: ReactNode;
    strong?: boolean;
    /** The value is a range: on a phone it wraps onto its own line under the label. */
    wrapRange?: boolean;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'flex items-baseline justify-between gap-x-4 gap-y-0.5 border-t py-2.5 first:border-t-0',
                wrapRange && 'max-sm:flex-wrap',
                strong && 'text-base font-bold',
                className,
            )}
        >
            <dt className={cn(!strong && 'text-muted-foreground')}>{label}</dt>
            <dd
                className={cn(
                    'text-right tabular-nums',
                    wrapRange && 'max-sm:basis-full max-sm:text-left',
                )}
            >
                {children}
            </dd>
        </div>
    );
}

/**
 * Where the unit cost comes from (materials, labor, overhead), then the
 * selling price and margin. Everything is a server value; the margin's sign
 * is read from the text, never computed.
 */
export function CostBreakdownCard({
    cost,
    linesCount,
    sellingPrice,
    margin,
    editHref,
}: CostBreakdownCardProps) {
    const { currency, locale } = useAppSettings();
    // Below cost when the lowest end is: some colour and size sells under its cost.
    const negative = margin !== null && isNegativeDecimal(margin.amount.min);
    const varies = (range: DecimalRange) =>
        formatMoneyRange(range, { currency, locale }).includes(RANGE_SEPARATOR);

    return (
        <section
            aria-labelledby="cost-breakdown-title"
            className="rounded-xl border bg-card px-5 py-4 text-card-foreground"
        >
            <h2 id="cost-breakdown-title" className="mb-1 text-base font-bold">
                Cost breakdown
            </h2>
            <dl>
                <Row
                    label={`Materials (${linesCount} ${
                        linesCount === 1 ? 'line' : 'lines'
                    }, waste included)`}
                    wrapRange={varies(cost.material_cost)}
                >
                    <MoneyRange value={cost.material_cost} />
                </Row>
                <Row label="Labor">
                    <Money value={cost.labor_cost} />
                </Row>
                <Row label="Overhead">
                    <Money value={cost.overhead_cost} />
                </Row>
                <Row
                    label="Unit cost"
                    strong
                    wrapRange={varies(cost.unit_cost)}
                    className="border-t-foreground"
                >
                    <MoneyRange value={cost.unit_cost} />
                </Row>

                {sellingPrice === null ? (
                    <div className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 border-t py-2.5">
                        <dt className="text-muted-foreground">Selling price</dt>
                        <dd className="text-right">
                            No selling price set.{' '}
                            <Link
                                href={editHref}
                                className="font-semibold text-primary underline underline-offset-2"
                            >
                                Set a price
                            </Link>
                        </dd>
                    </div>
                ) : (
                    <>
                        <Row label="Selling price">
                            <Money value={sellingPrice} />
                        </Row>
                        {margin && (
                            <Row
                                label="Margin"
                                wrapRange={varies(margin.amount)}
                            >
                                <span
                                    className={cn(
                                        'font-semibold',
                                        negative && 'text-danger-foreground',
                                    )}
                                >
                                    <MoneyRange value={margin.amount} />
                                    {margin.percent !== null && (
                                        <>
                                            {' · '}
                                            {formatPercentRange(
                                                margin.percent,
                                                locale,
                                            )}
                                        </>
                                    )}
                                    {negative && (
                                        <span className="sr-only">
                                            , below cost
                                        </span>
                                    )}
                                </span>
                            </Row>
                        )}
                    </>
                )}
            </dl>
        </section>
    );
}
