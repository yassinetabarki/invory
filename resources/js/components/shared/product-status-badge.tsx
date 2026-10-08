import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { ProductStatus } from '@/types/domain';

type ProductStatusBadgeProps = {
    status: ProductStatus;
    /** The server's label for the status ("Draft", "Active", "Archived"). */
    label: string;
    className?: string;
};

/** A draft stands out (info tone); active and archived products are neutral. */
const TONE: Record<ProductStatus, string> = {
    draft: 'bg-info text-info-foreground',
    active: 'bg-neutral text-neutral-foreground',
    archived: 'bg-neutral text-neutral-foreground',
};

export function ProductStatusBadge({
    status,
    label,
    className,
}: ProductStatusBadgeProps) {
    return (
        <Badge
            variant="outline"
            data-status={status}
            className={cn(
                'rounded-full border-transparent font-semibold',
                TONE[status],
                className,
            )}
        >
            {label}
        </Badge>
    );
}
