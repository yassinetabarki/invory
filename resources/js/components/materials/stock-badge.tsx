import { CircleAlert, CircleCheck, CircleX } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { StockStatus } from '@/types/domain';

type StockBadgeProps = {
    status: StockStatus;
    className?: string;
};

/**
 * Status is always conveyed by text and icon as well as colour (check,
 * exclamation, x). Colours come from the semantic status tokens, which have
 * light and dark values.
 */
const STATUS: Record<
    StockStatus,
    { label: string; icon: LucideIcon; className: string }
> = {
    in_stock: {
        label: 'In stock',
        icon: CircleCheck,
        className: 'bg-success text-success-foreground',
    },
    low: {
        label: 'Low stock',
        icon: CircleAlert,
        className: 'bg-warning text-warning-foreground',
    },
    out: {
        label: 'Out of stock',
        icon: CircleX,
        className: 'bg-danger text-danger-foreground',
    },
};

export function StockBadge({ status, className }: StockBadgeProps) {
    const { label, icon: Icon, className: tone } = STATUS[status];

    return (
        <Badge
            variant="outline"
            data-status={status}
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
