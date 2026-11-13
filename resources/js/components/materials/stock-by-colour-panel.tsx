import { InlinePanel } from '@/components/materials/inline-panel';
import { StockBadge } from '@/components/materials/stock-badge';
import { ColourTag } from '@/components/shared/colour-tag';
import { Quantity } from '@/components/shared/quantity';
import type { Material } from '@/types/domain';

type StockByColourPanelProps = {
    id: string;
    /** A per-colour material: its `colours` come in Settings › Colours order. */
    material: Material;
    onClose: () => void;
};

/**
 * The Materials list's third row panel: one tile per colour with its quantity
 * and, only when the colour is Low or Out, its stock badge. Tiles are not
 * buttons; the colour is always named beside its swatch. On a phone each
 * colour is one line.
 */
export function StockByColourPanel({
    id,
    material,
    onClose,
}: StockByColourPanelProps) {
    return (
        <InlinePanel
            id={id}
            title={`Stock by colour · ${material.name}`}
            closeLabel="Close"
            focusOnMount
            onClose={onClose}
            className="max-w-4xl"
        >
            <ul className="grid gap-2 sm:grid-cols-[repeat(auto-fill,minmax(9.5rem,1fr))]">
                {material.colours.map(
                    ({ colour, quantity_on_hand, stock_status }) => (
                        <li
                            key={colour.id}
                            className="flex flex-wrap items-center justify-between gap-x-3 gap-y-1.5 rounded-lg border bg-background p-3 sm:flex-col sm:items-start sm:justify-start"
                        >
                            <ColourTag colour={colour} className="text-sm" />
                            <Quantity
                                value={quantity_on_hand}
                                unit={material.unit}
                                className="font-semibold"
                            />
                            {stock_status !== 'in_stock' && (
                                <StockBadge status={stock_status} />
                            )}
                        </li>
                    ),
                )}
            </ul>
            {material.reorder_level !== null && (
                <p className="text-xs text-muted-foreground">
                    Reorder at{' '}
                    <Quantity
                        value={material.reorder_level}
                        unit={material.unit}
                    />{' '}
                    for each colour.
                </p>
            )}
        </InlinePanel>
    );
}
