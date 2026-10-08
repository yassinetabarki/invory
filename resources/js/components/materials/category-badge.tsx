import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

type CategoryBadgeProps = {
    /** The server's label for the category ("Fastener"). */
    label: string;
    className?: string;
};

/** A neutral pill: the category is information, not a status. */
export function CategoryBadge({ label, className }: CategoryBadgeProps) {
    return (
        <Badge
            variant="outline"
            className={cn(
                'rounded-full border-transparent bg-neutral font-semibold text-neutral-foreground',
                className,
            )}
        >
            {label}
        </Badge>
    );
}
