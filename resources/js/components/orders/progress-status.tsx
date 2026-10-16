import { CircleCheck, CircleDashed, Clock } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { OrderProgress } from '@/types/domain';

type ProgressStatusProps = {
    status: OrderProgress;
    /** The server's word for the status ("Not started", "In progress", "Shipped"). */
    label: string;
    className?: string;
};

// The word carries the meaning; the icon and the tone only help the eye.
const LOOK: Record<OrderProgress, { icon: LucideIcon; tone: string }> = {
    not_started: {
        icon: CircleDashed,
        tone: 'bg-neutral text-neutral-foreground',
    },
    in_progress: { icon: Clock, tone: 'bg-info text-info-foreground' },
    shipped: { icon: CircleCheck, tone: 'bg-success text-success-foreground' },
};

/** An order's or a line's status: an icon (hidden from screen readers) and the word. */
export function ProgressStatus({
    status,
    label,
    className,
}: ProgressStatusProps) {
    const { icon: Icon, tone } = LOOK[status];

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
