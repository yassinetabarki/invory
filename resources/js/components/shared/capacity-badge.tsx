import { CircleAlert, CircleCheck, CircleX, Minus } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import { cn } from '@/lib/utils';

type CapacityBadgeProps = {
    /** `max_producible` from the server: units that can be made now (the guaranteed figure), or null without a composition. */
    value: number | null;
    /**
     * `best` from the server: the most one colour and size can make on its own
     * (INV-16). Defaults to `value`, which is what a product without a By
     * colour and size line has, and what every caller that has no variants passes.
     */
    best?: number | null;
    className?: string;
};

/**
 * How many units of a product can be made from stock now. The server
 * computes the numbers; this only labels them. Text and icon always accompany
 * the colour:
 * - `null`: "No composition" (neutral)
 * - best `0`: "Can't produce" (danger, x)
 * - guaranteed `0`, best above it: "0–112 units" (warning, exclamation)
 * - guaranteed below best: "16–112 units" (success, check)
 * - `n`: "n units" / "1 unit" (success, check)
 */
export function CapacityBadge({
    value,
    best = value,
    className,
}: CapacityBadgeProps) {
    const { locale } = useAppSettings();

    const {
        state,
        label,
        icon: Icon,
        tone,
    } = capacityPresentation(value, best ?? value, locale);

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

function capacityPresentation(
    value: number | null,
    best: number | null,
    locale: string,
) {
    if (value === null) {
        return {
            state: 'none',
            label: 'No composition',
            icon: Minus,
            tone: 'bg-neutral text-neutral-foreground',
        };
    }

    // "Can't produce" only when not even the best colour and size can make a unit.
    if (value === 0 && (best ?? 0) === 0) {
        return {
            state: 'zero',
            label: "Can't produce",
            icon: CircleX,
            tone: 'bg-danger text-danger-foreground',
        };
    }

    if (best !== null && best !== value) {
        return {
            state: value === 0 ? 'short' : 'range',
            label: `${formatCount(value, locale)}–${formatCount(best, locale)} units`,
            icon: value === 0 ? CircleAlert : CircleCheck,
            tone:
                value === 0
                    ? 'bg-warning text-warning-foreground'
                    : 'bg-success text-success-foreground',
        };
    }

    return {
        state: 'available',
        label: `${formatCount(value, locale)} ${value === 1 ? 'unit' : 'units'}`,
        icon: CircleCheck,
        tone: 'bg-success text-success-foreground',
    };
}
