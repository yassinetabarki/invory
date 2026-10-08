import { useAppSettings } from '@/hooks/use-app-settings';
import { formatMoney } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Decimal } from '@/types/domain';

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
