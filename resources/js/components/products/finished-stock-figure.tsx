import { router } from '@inertiajs/react';
import { useRef, useState } from 'react';
import {
    BIG,
    HeroCard,
    UNITS,
    unitsWord,
} from '@/components/products/product-hero';
import { UpdateStockDialog } from '@/components/products/update-stock-dialog';
import { Button } from '@/components/ui/button';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import { formatSizes, sizeLabelInText } from '@/lib/sizes';
import { cn } from '@/lib/utils';
import type { FinishedStock, Product } from '@/types/domain';

/**
 * "In stock": the finished units on the shelf, "10 units", and under it a chip
 * per size of the product's list, zeros included ("S 3", "M 0", greyed), from
 * 1 unit up; at 0 units, and for a "One size" product, the total only. The chips
 * wrap, so a shoe's 11 sizes never scroll the phone sideways; screen readers
 * hear one spoken line instead of the chips. "Update stock", at the top right
 * on the label's row, opens the window that sets the counts.
 */
export function FinishedStockFigure({
    product,
    stock,
}: {
    product: Pick<Product, 'id' | 'name'>;
    stock: FinishedStock;
}) {
    const { locale } = useAppSettings();
    const [updating, setUpdating] = useState(false);
    const updateButtonRef = useRef<HTMLButtonElement>(null);
    const showSizes = stock.sizes.length > 1 && stock.units > 0;

    return (
        <HeroCard
            label="In stock"
            ariaLabel="Finished stock"
            action={
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    ref={updateButtonRef}
                    onClick={() => setUpdating(true)}
                >
                    Update stock
                </Button>
            }
        >
            <span className={BIG}>
                {formatCount(stock.units, locale)}{' '}
                <span className={UNITS}>{unitsWord(stock.units)}</span>
            </span>
            {showSizes && (
                <>
                    <p className="sr-only">
                        In stock per size:{' '}
                        {formatSizes(
                            stock.sizes.map(({ size, units }) => ({
                                label: size.label,
                                units,
                            })),
                            locale,
                            { keepZeros: true, separator: ', ' },
                        )}
                    </p>
                    <div
                        aria-hidden="true"
                        className="mt-2 flex flex-wrap gap-1.5"
                    >
                        {stock.sizes.map(({ size, units }) => (
                            <span
                                key={size.id}
                                data-zero={units === 0 ? '' : undefined}
                                className="inline-flex items-stretch overflow-hidden rounded-md border text-sm leading-none"
                            >
                                <b className="bg-muted px-2 py-1.5 font-semibold">
                                    {sizeLabelInText(size.label)}
                                </b>
                                <span
                                    className={cn(
                                        'px-2 py-1.5 font-mono text-[0.8125rem] tabular-nums',
                                        units === 0
                                            ? 'font-normal text-muted-foreground'
                                            : 'font-semibold',
                                    )}
                                >
                                    {formatCount(units, locale)}
                                </span>
                            </span>
                        ))}
                    </div>
                </>
            )}
            <UpdateStockDialog
                product={product}
                stock={stock}
                open={updating}
                onOpenChange={setUpdating}
                returnFocusTo={updateButtonRef}
            />
        </HeroCard>
    );
}

/** The figure when the stock couldn't be read: only this prop is reloaded. */
export function FinishedStockError({ reloading }: { reloading: boolean }) {
    return (
        <HeroCard label="In stock" ariaLabel="Finished stock">
            <div
                role="alert"
                className="flex flex-col items-start gap-2 text-sm"
            >
                <p>Couldn&apos;t load the stock.</p>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={reloading}
                    onClick={() => router.reload({ only: ['finished_stock'] })}
                >
                    Try again
                </Button>
            </div>
        </HeroCard>
    );
}
