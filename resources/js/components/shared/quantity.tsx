import { useAppSettings } from '@/hooks/use-app-settings';
import { formatQuantity } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Decimal } from '@/types/domain';

type QuantityProps = {
    value: Decimal | null | undefined;
    /** Unit code shown after the number (`m`, `pc`…), always next to the quantity. */
    unit?: string | null;
    className?: string;
};

export function Quantity({ value, unit, className }: QuantityProps) {
    const { locale } = useAppSettings();

    return (
        <span className={cn('tabular-nums', className)}>
            {formatQuantity(value, { locale, unit })}
        </span>
    );
}
