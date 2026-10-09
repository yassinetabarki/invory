import { Archive, CircleCheck } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

type ReferenceStatusBadgeProps = {
    archived: boolean;
    className?: string;
};

/** "Active" or "Archived": text and icon as well as colour, never colour alone. */
export function ReferenceStatusBadge({
    archived,
    className,
}: ReferenceStatusBadgeProps) {
    const Icon = archived ? Archive : CircleCheck;

    return (
        <Badge
            variant="outline"
            data-status={archived ? 'archived' : 'active'}
            className={cn(
                'rounded-full border-transparent font-semibold',
                archived
                    ? 'bg-neutral text-neutral-foreground'
                    : 'bg-success text-success-foreground',
                className,
            )}
        >
            <Icon aria-hidden="true" />
            {archived ? 'Archived' : 'Active'}
        </Badge>
    );
}
