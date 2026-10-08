import { CircleCheck, CircleX, Minus } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import { cn } from '@/lib/utils';

type CapacityBadgeProps = {
    /** `max_producible` from the server: units that can be made now, or null without a composition. */
    value: number | null;
    className?: string;
};

/**
 * How many units of a product can be made from stock now. The server
 * computes the number; this only labels it. Text and icon always accompany
 * the colour:
 * - `null`: "No composition" (neutral)
 * - `0`: "Can't produce" (danger, x)
 * - `n`: "n units" / "1 unit" (success, check)
 */
export function CapacityBadge({ value, className }: CapacityBadgeProps) {
    const { locale } = useAppSettings();

    const {
        state,
        label,
        icon: Icon,
        tone,
    } = capacityPresentation(value, locale);

    return (
        <Badge
            variant="outline"
            data-capacity={state}
            className={cn(
                'rounded-full border-transparent font-semibold',
                tone,
                className,
            )}
        >
            <Icon aria-hidden="true" />
            {label}
        </Badge>
    );
}

function capacityPresentation(value: number | null, locale: string) {
    if (value === null) {
        return {
            state: 'none',
            label: 'No composition',
            icon: Minus,
            tone: 'bg-neutral text-neutral-foreground',
        };
    }

    if (value === 0) {
        return {
            state: 'zero',
            label: "Can't produce",
            icon: CircleX,
            tone: 'bg-danger text-danger-foreground',
        };
    }

    return {
        state: 'available',
        label: `${formatCount(value, locale)} ${value === 1 ? 'unit' : 'units'}`,
        icon: CircleCheck,
        tone: 'bg-success text-success-foreground',
    };
}
