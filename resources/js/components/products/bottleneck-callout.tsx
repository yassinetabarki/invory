import { Link } from '@inertiajs/react';
import { AddColourLink } from '@/components/products/colour-stock-line';
import type { AddColour } from '@/components/products/colour-stock-line';
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
    /** Opens the window that adds the missing colour to the material (INV-21-p); the link shows only when given. */
    onAddColour?: AddColour;
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
    onAddColour,
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

    const { bottleneck, bottleneck_colour: colour } = capacity;

    if (bottleneck === null) {
        return null;
    }

    const line = components.find(
        (component) => component.material.id === bottleneck.id,
    );
    const openMaterial = (
        <Link href={materialHref(bottleneck)}>Open material</Link>
    );
    // A material kept per colour: the stock that limits is the binding colour's, not the material's total (INV-21).
    const stock = colour
        ? capacity.bottleneck_stock
        : (line?.stock_on_hand ?? null);
    // "It's also below its reorder level": the bottleneck's own stock decides (a per-colour material: the colour that limits, not any colour of it).
    const colourStatus = capacity.bottleneck_colour_status;
    const belowReorder =
        line !== undefined &&
        (colour
            ? (colourStatus === 'low' || colourStatus === 'out') &&
              line.material.reorder_level !== null
            : line.material.stock_status === 'low');
    const missingColour =
        colour !== null && capacity.bottleneck_missing ? colour : null;
    const missingBody = missingColour && (
        <>
            {bottleneck.name} has no {missingColour.name} yet, so {productName}{' '}
            can't be made in {missingColour.name}.
            {onAddColour && (
                <>
                    {' '}
                    <AddColourLink
                        materialName={bottleneck.name}
                        colourName={missingColour.name}
                        onClick={() =>
                            onAddColour(bottleneck.id, missingColour.id)
                        }
                        className="font-semibold text-current underline underline-offset-2"
                    />
                </>
            )}
        </>
    );

    if (capacity.best === 0) {
        const isOut = colour
            ? stock !== null && isZeroDecimal(stock)
            : line?.material.stock_status === 'out';

        return (
            <Callout
                tone="danger"
                title={`${productName} can't be produced:`}
                action={openMaterial}
            >
                {missingBody ? (
                    missingBody
                ) : isOut || !line ? (
                    <>{bottleneck.name} is out of stock.</>
                ) : (
                    <>
                        {bottleneck.name} doesn't cover one unit.{' '}
                        <Quantity value={stock} unit={bottleneck.unit} />
                        {colour && ` of ${colour.name}`} in stock
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
        const size = byVariant.binding.size
            ? `size ${byVariant.binding.size.label}`
            : null;
        const title = missingColour
            ? `Bottleneck: ${bottleneck.name} — no ${missingColour.name} stock.`
            : colour
              ? `Bottleneck: ${bottleneck.name}, ${colour.name} stock${size ? ` (${size})` : ''}.`
              : `Bottleneck: ${bottleneck.name}${name ? ` (${name})` : ''}.`;

        return (
            <Callout tone="warning" title={title} action={openMaterial}>
                {missingBody}
                {!missingBody && stock !== null && cannotBeMade && (
                    <>
                        {isZeroDecimal(stock) ? (
                            <>
                                {bottleneck.name} is out of stock, so {name}{' '}
                                can't be made.
                            </>
                        ) : (
                            <>
                                {bottleneck.name} doesn't cover one unit of{' '}
                                {name}.{' '}
                                <Quantity
                                    value={stock}
                                    unit={bottleneck.unit}
                                />{' '}
                                in stock.
                            </>
                        )}
                    </>
                )}
                {!missingBody && stock !== null && !cannotBeMade && (
                    <>
                        <Quantity value={stock} unit={bottleneck.unit} />{' '}
                        {colour && `of ${colour.name} `}in stock covers{' '}
                        {formatCount(guaranteed, locale)}{' '}
                        {guaranteed === 1 ? 'unit' : 'units'}
                        {name &&
                            ` of ${name} — the smallest across every ${spanName(byVariant.binding, singleColour)}`}
                        .
                    </>
                )}
                {!missingBody && line && belowReorder && (
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
                    {belowReorder && (
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
