import { useAppSettings } from '@/hooks/use-app-settings';
import { formatMoney, formatMoneyRange } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Decimal, DecimalRange } from '@/types/domain';

type MoneyProps = {
    value: Decimal | null | undefined;
    /** Per-unit costs: show 4 decimals for non-zero amounts below 0.01. */
    precise?: boolean;
    className?: string;
};

export function Money({ value, precise = false, className }: MoneyProps) {
    const { currency, locale } = useAppSettings();

    return (
        <span className={cn('tabular-nums', className)}>
            {formatMoney(value, { currency, locale, precise })}
        </span>
    );
}

type MoneyRangeProps = {
    value: DecimalRange | null | undefined;
    className?: string;
};

/**
 * A unit cost or margin from its lowest to its highest (INV-16): one figure
 * when the ends read the same, `TND 33.884–39.494` (currency once) otherwise.
 */
export function MoneyRange({ value, className }: MoneyRangeProps) {
    const { currency, locale } = useAppSettings();

    return (
        <span className={cn('tabular-nums', className)}>
            {formatMoneyRange(value, { currency, locale })}
        </span>
    );
}
