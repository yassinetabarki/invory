import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { isNegativeDecimal } from '@/components/products/decimals';
import { Money } from '@/components/shared/money';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatPercent } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { CostBreakdown, Decimal, Margin } from '@/types/domain';

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
    className,
}: {
    label: ReactNode;
    children: ReactNode;
    strong?: boolean;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'flex items-baseline justify-between gap-4 border-t py-2.5 first:border-t-0',
                strong && 'text-base font-bold',
                className,
            )}
        >
            <dt className={cn(!strong && 'text-muted-foreground')}>{label}</dt>
            <dd className="text-right tabular-nums">{children}</dd>
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
    const { locale } = useAppSettings();
    const negative = margin !== null && isNegativeDecimal(margin.amount);

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
                >
                    <Money value={cost.material_cost} />
                </Row>
                <Row label="Labor">
                    <Money value={cost.labor_cost} />
                </Row>
                <Row label="Overhead">
                    <Money value={cost.overhead_cost} />
                </Row>
                <Row label="Unit cost" strong className="border-t-foreground">
                    <Money value={cost.unit_cost} />
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
                            <Row label="Margin">
                                <span
                                    className={cn(
                                        'font-semibold',
                                        negative && 'text-danger-foreground',
                                    )}
                                >
                                    <Money value={margin.amount} />
                                    {margin.percent !== null && (
                                        <>
                                            {' · '}
                                            {formatPercent(
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
