import type { ReactNode } from 'react';
import { Money, MoneyRange } from '@/components/shared/money';
import { Quantity } from '@/components/shared/quantity';
import type { Material } from '@/types/domain';

function Kpi({
    label,
    note,
    children,
}: {
    label: string;
    /** The small line under the figure ("2 colours", "each colour", "per colour"). */
    note?: string;
    children: ReactNode;
}) {
    return (
        <div className="flex min-w-0 flex-col gap-0.5 rounded-lg border bg-card px-4 py-3.5">
            <dt className="text-xs font-semibold text-muted-foreground">
                {label}
            </dt>
            <dd className="text-xl font-bold tabular-nums">
                {children}
                {note && (
                    <span className="block text-xs font-normal text-muted-foreground">
                        {note}
                    </span>
                )}
            </dd>
        </div>
    );
}

/**
 * The four numbers of a material: stock with its unit, the reorder level
 * ("Not set" when there is none), the per-unit cost and the stock value.
 * For a per-colour material the figures say what they cover: the total over
 * "2 colours", the level "each colour", a cost range "per colour" (INV-21).
 */
export function MaterialKpis({ material }: { material: Material }) {
    const perColour = material.stock_mode === 'per_colour';
    const count = material.colours.length;

    return (
        <dl className="grid grid-cols-[repeat(auto-fit,minmax(9.5rem,1fr))] gap-3">
            <Kpi
                label={perColour ? 'In stock, all colours' : 'In stock'}
                note={
                    perColour
                        ? `${count} ${count === 1 ? 'colour' : 'colours'}`
                        : undefined
                }
            >
                <Quantity
                    value={material.quantity_on_hand}
                    unit={material.unit}
                />
            </Kpi>
            <Kpi
                label="Reorder at"
                note={
                    perColour && material.reorder_level !== null
                        ? 'each colour'
                        : undefined
                }
            >
                {material.reorder_level === null ? (
                    <span className="text-base font-medium text-muted-foreground">
                        Not set
                    </span>
                ) : (
                    <Quantity
                        value={material.reorder_level}
                        unit={material.unit}
                    />
                )}
            </Kpi>
            <Kpi
                label="Unit cost"
                note={material.unit_cost_range ? 'per colour' : undefined}
            >
                {material.unit_cost_range ? (
                    <MoneyRange value={material.unit_cost_range} />
                ) : (
                    <Money value={material.unit_cost} precise />
                )}
                <span className="text-sm font-normal text-muted-foreground">
                    {' '}
                    / {material.unit}
                </span>
            </Kpi>
            <Kpi label="Stock value">
                <Money value={material.stock_value} />
            </Kpi>
        </dl>
    );
}
