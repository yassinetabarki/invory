import { CircleCheck, CircleX, TriangleAlert } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { StockStatus } from '@/types/domain';

type StockBadgeProps = {
    status: StockStatus;
    className?: string;
};

/**
 * Status is always conveyed by text and icon as well as colour.
 * Colours are semantic (green / amber / red) with dark-mode variants.
 */
const STATUS: Record<
    StockStatus,
    { label: string; icon: LucideIcon; className: string }
> = {
    in_stock: {
        label: 'In stock',
        icon: CircleCheck,
        className:
            'border-green-600/30 bg-green-50 text-green-800 dark:border-green-400/30 dark:bg-green-950/50 dark:text-green-300',
    },
    low: {
        label: 'Low stock',
        icon: TriangleAlert,
        className:
            'border-amber-600/40 bg-amber-50 text-amber-900 dark:border-amber-400/30 dark:bg-amber-950/50 dark:text-amber-300',
    },
    out: {
        label: 'Out of stock',
        icon: CircleX,
        className:
            'border-red-600/30 bg-red-50 text-red-800 dark:border-red-400/30 dark:bg-red-950/50 dark:text-red-300',
    },
};

export function StockBadge({ status, className }: StockBadgeProps) {
    const { label, icon: Icon, className: tone } = STATUS[status];

    return (
        <Badge
            variant="outline"
            data-status={status}
            className={cn(tone, className)}
        >
            <Icon aria-hidden="true" />
            {label}
        </Badge>
    );
}
