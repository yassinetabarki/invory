import { router } from '@inertiajs/react';
import { Fragment } from 'react';
import {
    BIG,
    HeroCard,
    UNITS,
    unitsWord,
} from '@/components/products/product-hero';
import { ColourSwatch } from '@/components/shared/colour-tag';
import { Button } from '@/components/ui/button';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { FinishedStock } from '@/types/domain';

/** The id of the Sizes & colours card, which the figure's link moves to. */
export const SIZES_COLOURS_ID = 'sizes-colours';

/**
 * "In stock": the finished units on the shelf, "10 units", and under it one
 * line with every colour of the product in the order of Settings › Colours: a
 * swatch, the name and its count in bold ("■ Black 4 · ■ Indigo 6 · ■ Light
 * wash 0"), a colour at 0 in the grey text colour with its "0" still shown.
 * The line wraps; screen readers hear one sentence instead ("In stock per
 * colour: Black 4, Indigo 6, Light wash 0"). At 0 units there is no colour
 * line. The link "By size and colour" moves to the Sizes & colours card, where
 * "Update stock" lives. Every figure comes from the server.
 */
export function FinishedStockFigure({ stock }: { stock: FinishedStock }) {
    const { locale } = useAppSettings();
    const count = (units: number) => formatCount(units, locale);
    const showColours = stock.units > 0 && stock.colours.length > 0;

    return (
        <HeroCard label="In stock" ariaLabel="Finished stock">
            <span className={BIG}>
                {count(stock.units)}{' '}
                <span className={UNITS}>{unitsWord(stock.units)}</span>
            </span>
            {showColours && (
                <>
                    <p className="sr-only">
                        In stock per colour:{' '}
                        {stock.colours
                            .map(
                                ({ colour, units }) =>
                                    `${colour.name} ${count(units)}`,
                            )
                            .join(', ')}
                    </p>
                    <p aria-hidden="true" className="mt-1 text-sm">
                        {stock.colours.map(({ colour, units }, index) => (
                            <Fragment key={colour.id}>
                                {index > 0 && ' · '}
                                <span className="inline-flex max-w-full items-center gap-1.5 align-middle">
                                    <ColourSwatch swatch={colour.swatch} />
                                    {/* A 32-character name breaks anywhere, so it never widens the card. */}
                                    <span
                                        className={cn(
                                            'min-w-0 [overflow-wrap:anywhere]',
                                            units === 0 &&
                                                'text-muted-foreground',
                                        )}
                                    >
                                        {colour.name}{' '}
                                        <b
                                            className={cn(
                                                'font-mono tabular-nums',
                                                units === 0
                                                    ? 'font-normal'
                                                    : 'font-semibold',
                                            )}
                                        >
                                            {count(units)}
                                        </b>
                                    </span>
                                </span>
                            </Fragment>
                        ))}
                    </p>
                </>
            )}
            <a
                href={`#${SIZES_COLOURS_ID}`}
                className="inline-flex min-h-11 items-center self-start text-sm font-semibold text-primary underline-offset-4 hover:underline md:min-h-8"
            >
                By size and colour
            </a>
        </HeroCard>
    );
}

/**
 * "Couldn't load the stock." with Try again. It reloads the one deferred prop
 * the In stock figure and the Sizes & colours card share, so both come back
 * together.
 */
export function StockLoadError({ reloading }: { reloading: boolean }) {
    return (
        <div role="alert" className="flex flex-col items-start gap-2 text-sm">
            <p>Couldn&apos;t load the stock.</p>
            <Button
                type="button"
                variant="outline"
                size="sm"
                className="aria-disabled:opacity-50"
                // Not `disabled`: the focus stays on the button while it reloads.
                aria-disabled={reloading ? 'true' : undefined}
                onClick={() => {
                    if (!reloading) {
                        router.reload({ only: ['finished_stock'] });
                    }
                }}
            >
                Try again
            </Button>
        </div>
    );
}

/** The figure when the stock couldn't be read. */
export function FinishedStockError({ reloading }: { reloading: boolean }) {
    return (
        <HeroCard label="In stock" ariaLabel="Finished stock">
            <StockLoadError reloading={reloading} />
        </HeroCard>
    );
}
