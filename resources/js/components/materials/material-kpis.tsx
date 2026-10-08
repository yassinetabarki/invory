import type { ReactNode } from 'react';
import { Money } from '@/components/shared/money';
import { Quantity } from '@/components/shared/quantity';
import type { Material } from '@/types/domain';

function Kpi({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="flex min-w-0 flex-col gap-0.5 rounded-lg border bg-card px-4 py-3.5">
            <dt className="text-xs font-semibold text-muted-foreground">
                {label}
            </dt>
            <dd className="text-xl font-bold tabular-nums">{children}</dd>
        </div>
    );
}

/**
 * The four numbers of a material: stock with its unit, the reorder level
 * ("Not set" when there is none), the per-unit cost and the stock value.
 */
export function MaterialKpis({ material }: { material: Material }) {
    return (
        <dl className="grid grid-cols-[repeat(auto-fit,minmax(9.5rem,1fr))] gap-3">
            <Kpi label="In stock">
                <Quantity
                    value={material.quantity_on_hand}
                    unit={material.unit}
                />
            </Kpi>
            <Kpi label="Reorder at">
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
            <Kpi label="Unit cost">
                <Money value={material.unit_cost} precise />
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
