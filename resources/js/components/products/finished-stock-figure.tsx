import { router } from '@inertiajs/react';
import { useRef, useState } from 'react';
import {
    BIG,
    HeroCard,
    UNITS,
    unitsWord,
} from '@/components/products/product-hero';
import { UpdateStockDialog } from '@/components/products/update-stock-dialog';
import { SizeChips } from '@/components/shared/size-chips';
import { Button } from '@/components/ui/button';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
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
                <SizeChips
                    sizes={stock.sizes.map(({ size, units }) => ({
                        label: size.label,
                        units,
                    }))}
                    keepZeros
                    spokenPrefix="In stock per size: "
                    className="mt-2"
                />
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
