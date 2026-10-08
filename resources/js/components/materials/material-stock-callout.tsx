import { Callout } from '@/components/shared/callout';
import { Quantity } from '@/components/shared/quantity';
import type { Material } from '@/types/domain';

/**
 * A warning when the stock is at or below the reorder level, a danger callout
 * when it is gone; nothing while the material is in stock.
 */
export function MaterialStockCallout({ material }: { material: Material }) {
    if (material.stock_status === 'in_stock') {
        return null;
    }

    const reorder = material.reorder_level !== null && (
        <>
            , reorder at{' '}
            <Quantity value={material.reorder_level} unit={material.unit} />
        </>
    );

    if (material.stock_status === 'out') {
        return (
            <Callout tone="danger" title="Out of stock.">
                <Quantity
                    value={material.quantity_on_hand}
                    unit={material.unit}
                />{' '}
                on hand{reorder}.
            </Callout>
        );
    }

    return (
        <Callout tone="warning" title="Below reorder level.">
            <Quantity value={material.quantity_on_hand} unit={material.unit} />{' '}
            on hand{reorder}.
        </Callout>
    );
}
