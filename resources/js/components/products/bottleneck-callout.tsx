import { Link } from '@inertiajs/react';
import { Callout } from '@/components/shared/callout';
import { Quantity } from '@/components/shared/quantity';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import type {
    Capacity,
    MaterialRef,
    ProductComponentLine,
} from '@/types/domain';

type BottleneckCalloutProps = {
    productName: string;
    capacity: Capacity;
    components: ProductComponentLine[];
    /** Where "Open material" goes (Wayfinder in the page). */
    materialHref: (material: MaterialRef) => string;
    /** Where "Edit composition" goes when there is no composition. */
    editHref: string;
};

/**
 * Explains what limits production, in one of three states:
 * - no composition (`max_producible` is null): an info note with a link to edit it
 * - can't produce (0): a danger alert naming the material that is out or short
 * - otherwise: a warning naming the bottleneck, how far its stock goes, and
 *   whether it is also below its reorder level
 */
export function BottleneckCallout({
    productName,
    capacity,
    components,
    materialHref,
    editHref,
}: BottleneckCalloutProps) {
    const { locale } = useAppSettings();

    if (capacity.max_producible === null) {
        return (
            <Callout
                tone="info"
                title="No composition defined."
                action={<Link href={editHref}>Edit composition</Link>}
            >
                Add the materials that make one unit to see its cost and how
                many can be produced.
            </Callout>
        );
    }

    const { bottleneck } = capacity;

    if (bottleneck === null) {
        return null;
    }

    const line = components.find(
        (component) => component.material.id === bottleneck.id,
    );
    const openMaterial = (
        <Link href={materialHref(bottleneck)}>Open material</Link>
    );

    if (capacity.max_producible === 0) {
        const isOut = line?.material.stock_status === 'out';

        return (
            <Callout
                tone="danger"
                title={`${productName} can't be produced:`}
                action={openMaterial}
            >
                {isOut || !line ? (
                    <>{bottleneck.name} is out of stock.</>
                ) : (
                    <>
                        {bottleneck.name} doesn't cover one unit.{' '}
                        <Quantity
                            value={line.stock_on_hand}
                            unit={bottleneck.unit}
                        />{' '}
                        in stock, and each unit needs{' '}
                        <Quantity
                            value={line.effective_quantity}
                            unit={bottleneck.unit}
                        />
                        .
                    </>
                )}
            </Callout>
        );
    }

    return (
        <Callout
            tone="warning"
            title={`Bottleneck: ${bottleneck.name}.`}
            action={openMaterial}
        >
            {line && (
                <>
                    <Quantity
                        value={line.stock_on_hand}
                        unit={bottleneck.unit}
                    />{' '}
                    in stock covers{' '}
                    {formatCount(capacity.max_producible, locale)}{' '}
                    {capacity.max_producible === 1 ? 'unit' : 'units'} at{' '}
                    <Quantity
                        value={line.effective_quantity}
                        unit={bottleneck.unit}
                    />{' '}
                    each.
                    {line.material.stock_status === 'low' && (
                        <>
                            {' '}
                            It's also below its reorder level of{' '}
                            <Quantity
                                value={line.material.reorder_level}
                                unit={bottleneck.unit}
                            />
                            .
                        </>
                    )}
                </>
            )}
        </Callout>
    );
}
