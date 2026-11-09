import { Link } from '@inertiajs/react';
import { Callout } from '@/components/shared/callout';
import { Quantity } from '@/components/shared/quantity';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import type {
    Capacity,
    MaterialRef,
    ProductComponentLine,
    VariantRef,
} from '@/types/domain';

type BottleneckCalloutProps = {
    productName: string;
    capacity: Capacity;
    components: ProductComponentLine[];
    /** The product has one colour only (INV-16): the colour is then left out of the variant's name. */
    singleColour: boolean;
    /** Where "Open material" goes (Wayfinder in the page). */
    materialHref: (material: MaterialRef) => string;
    /** Where "Edit composition" goes when there is no composition. */
    editHref: string;
};

/**
 * The variant that limits production as the callout names it: "Black, size XL",
 * "Black" for a "One size" product, "size XL" for a one-colour product. Null for
 * a one-colour "One size" product (nothing to name).
 */
function variantName(
    binding: VariantRef,
    singleColour: boolean,
): string | null {
    const parts = [
        singleColour ? null : binding.colour.name,
        binding.size ? `size ${binding.size.label}` : null,
    ].filter((part) => part !== null);

    return parts.length > 0 ? parts.join(', ') : null;
}

/** A server decimal string that is zero ("0", "0.0000"); no number conversion. */
function isZeroDecimal(value: string): boolean {
    return !/[1-9]/.test(value);
}

/** What the guaranteed figure is the smallest across: "colour and size", "colour" or "size". */
function spanName(binding: VariantRef, singleColour: boolean): string {
    return [singleColour ? null : 'colour', binding.size ? 'size' : null]
        .filter((part) => part !== null)
        .join(' and ');
}

/**
 * Explains what limits production, in one of three states:
 * - no composition (`max_producible` is null): an info note with a link to edit it
 * - can't produce (0): a danger alert naming the material that is out or short
 * - otherwise: a warning naming the bottleneck, how far its stock goes, and
 *   whether it is also below its reorder level
 * With a By colour and size line (`by_variant`) the warning names the colour and
 * size that limit the guaranteed figure and adds a quieter "Best case" line;
 * "can't produce" is only when even the best case is 0 (INV-16).
 */
export function BottleneckCallout({
    productName,
    capacity,
    components,
    singleColour,
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

    if (capacity.best === 0) {
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
                        in stock
                        {line.effective_quantity !== null && (
                            <>
                                , and each unit needs{' '}
                                <Quantity
                                    value={line.effective_quantity}
                                    unit={bottleneck.unit}
                                />
                            </>
                        )}
                        .
                    </>
                )}
            </Callout>
        );
    }

    const { by_variant: byVariant } = capacity;

    if (byVariant) {
        const name = variantName(byVariant.binding, singleColour);
        const { best_at: bestAt } = byVariant;
        const guaranteed = capacity.max_producible;
        // The bottleneck's variant can't be made at all, but another one can (best > 0).
        const cannotBeMade = guaranteed === 0 && name !== null;

        return (
            <Callout
                tone="warning"
                title={`Bottleneck: ${bottleneck.name}${name ? ` (${name})` : ''}.`}
                action={openMaterial}
            >
                {line && cannotBeMade && (
                    <>
                        {isZeroDecimal(line.stock_on_hand) ? (
                            <>
                                {bottleneck.name} is out of stock, so {name}{' '}
                                can't be made.
                            </>
                        ) : (
                            <>
                                {bottleneck.name} doesn't cover one unit of{' '}
                                {name}.{' '}
                                <Quantity
                                    value={line.stock_on_hand}
                                    unit={bottleneck.unit}
                                />{' '}
                                in stock.
                            </>
                        )}
                    </>
                )}
                {line && !cannotBeMade && (
                    <>
                        <Quantity
                            value={line.stock_on_hand}
                            unit={bottleneck.unit}
                        />{' '}
                        in stock covers {formatCount(guaranteed, locale)}{' '}
                        {guaranteed === 1 ? 'unit' : 'units'}
                        {name &&
                            ` of ${name} — the smallest across every ${spanName(byVariant.binding, singleColour)}`}
                        .
                    </>
                )}
                {line?.material.stock_status === 'low' && (
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
                <span className="mt-1 block opacity-85">
                    Best case: {formatCount(capacity.best, locale)}{' '}
                    {capacity.best === 1 ? 'unit' : 'units'}, in{' '}
                    {bestAt.colour.name} — limited there by{' '}
                    <Link
                        href={materialHref(bestAt.bottleneck)}
                        className="underline underline-offset-2"
                    >
                        {bestAt.bottleneck.name}
                    </Link>
                    .
                </span>
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
