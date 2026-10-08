import { isNegativeDecimal } from '@/components/products/decimals';
import { Money } from '@/components/shared/money';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatPercent } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Decimal, Margin } from '@/types/domain';

type PriceMarginProps = {
    sellingPrice: Decimal | null;
    /** From the server; null when there is no selling price. */
    margin: Margin | null;
    className?: string;
};

/**
 * The selling price with the margin percentage under it. A product without a
 * selling price shows an em dash. A negative margin is red and carries its
 * minus sign and a screen-reader note, so it never relies on colour alone.
 * Without a percentage (a zero selling price) the margin amount is shown.
 */
export function PriceMargin({
    sellingPrice,
    margin,
    className,
}: PriceMarginProps) {
    const { locale } = useAppSettings();

    if (sellingPrice === null) {
        return (
            <span className={cn('text-muted-foreground', className)}>
                <span aria-hidden="true">—</span>
                <span className="sr-only">No selling price</span>
            </span>
        );
    }

    const negative = margin !== null && isNegativeDecimal(margin.amount);

    return (
        <span className={cn('flex flex-col', className)}>
            <Money value={sellingPrice} className="font-medium" />
            {margin && (
                <span
                    className={cn(
                        'text-xs tabular-nums',
                        negative
                            ? 'font-semibold text-danger-foreground'
                            : 'text-muted-foreground',
                    )}
                >
                    {margin.percent !== null ? (
                        formatPercent(margin.percent, locale)
                    ) : (
                        <Money value={margin.amount} />
                    )}
                    <span className="sr-only">
                        {negative ? ' margin, below cost' : ' margin'}
                    </span>
                </span>
            )}
        </span>
    );
}
