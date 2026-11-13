import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AddColourLink } from '@/components/products/colour-stock-line';
import type { AddColour } from '@/components/products/colour-stock-line';
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
    ProductComponentLine,
} from '@/types/domain';

/** A colour of the product that a material with cost per colour lacks: the variants in it have no unit cost (INV-21). */
export type MissingCostColour = {
    materialId: number;
    materialName: string;
    colourId: number;
    colourName: string;
};

/** One per material and colour, in composition order: the colours a cost-per-colour material lacks. */
export function missingCostColours(
    lines: readonly ProductComponentLine[],
): MissingCostColour[] {
    return lines
        .filter((line) => line.material.cost_mode === 'per_colour')
        .flatMap((line) =>
            (line.stock_by_colour ?? [])
                .filter((reading) => reading.missing)
                .map((reading) => ({
                    materialId: line.material.id,
                    materialName: line.material.name,
                    colourId: reading.colour.id,
                    colourName: reading.colour.name,
                })),
        );
}

/**
 * One line per colour a cost-per-colour material lacks, "No unit cost yet. Add
 * Light wash to Denim 12oz." (the second sentence is the link to the add-colour
 * window when the page can open it). It sits under the unit-cost range of the
 * colours that have a cost, or alone when the server sent no range. With
 * nothing missing to name it reads "No unit cost yet." alone.
 */
export function NoUnitCost({
    missing,
    onAddColour,
    className,
}: {
    missing: readonly MissingCostColour[];
    onAddColour?: AddColour;
    className?: string;
}) {
    if (missing.length === 0) {
        return <span className={className}>No unit cost yet.</span>;
    }

    return (
        <span className={cn('flex flex-col gap-1', className)}>
            {missing.map((item) => (
                <span
                    key={`${item.materialId}-${item.colourId}`}
                    className="flex flex-wrap items-baseline gap-x-1"
                >
                    <span>No unit cost yet.</span>{' '}
                    {onAddColour ? (
                        <span className="inline-flex items-baseline">
                            <AddColourLink
                                materialName={item.materialName}
                                colourName={item.colourName}
                                onClick={() =>
                                    onAddColour(item.materialId, item.colourId)
                                }
                                className="font-semibold text-primary underline underline-offset-2"
                            />
                            .
                        </span>
                    ) : (
                        <span>
                            Add {item.colourName} to {item.materialName}.
                        </span>
                    )}
                </span>
            ))}
        </span>
    );
}

type CostBreakdownCardProps = {
    cost: CostBreakdown;
    /** Lines in the composition, for "Materials (N lines, waste included)". */
    linesCount: number;
    sellingPrice: Decimal | null;
    /** From the server; null when there is no selling price. */
    margin: Margin | null;
    /** Where "Set a price" goes: the product's edit page. */
    editHref: string;
    /** The colours a cost-per-colour material lacks (INV-21): a line each under the unit cost; with no range at all, no margin shows. */
    missingCosts?: readonly MissingCostColour[];
    /** Opens the window that adds a missing colour to the material; the "Add … to …" link shows only when given. */
    onAddColour?: AddColour;
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
    missingCosts = [],
    onAddColour,
}: CostBreakdownCardProps) {
    const { currency, locale } = useAppSettings();
    // No range (and no margin) only when the server sent none: every colour lacks a cost. With some missing, the range of the others stays.
    const noUnitCost = cost.unit_cost === null;
    // Below cost when the lowest end is: some colour and size sells under its cost.
    const negative = margin !== null && isNegativeDecimal(margin.amount.min);
    const varies = (range: DecimalRange | null) =>
        range !== null &&
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
                    wrapRange={
                        noUnitCost ||
                        missingCosts.length > 0 ||
                        varies(cost.unit_cost)
                    }
                    className="border-t-foreground"
                >
                    {noUnitCost ? (
                        <NoUnitCost
                            missing={missingCosts}
                            onAddColour={onAddColour}
                            className="items-start text-left text-sm font-normal sm:items-end sm:text-right"
                        />
                    ) : (
                        <span className="flex flex-col gap-1 sm:items-end">
                            <MoneyRange value={cost.unit_cost} />
                            {missingCosts.length > 0 && (
                                <NoUnitCost
                                    missing={missingCosts}
                                    onAddColour={onAddColour}
                                    className="items-start text-left text-sm font-normal sm:items-end sm:text-right"
                                />
                            )}
                        </span>
                    )}
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
                        {margin && !noUnitCost && (
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
