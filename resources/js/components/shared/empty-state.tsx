import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

type EmptyStateProps = {
    icon?: LucideIcon;
    title: string;
    description?: string;
    /** Call to action, e.g. an "Add your first material" button. */
    action?: ReactNode;
};

export function EmptyState({
    icon: Icon,
    title,
    description,
    action,
}: EmptyStateProps) {
    return (
        <div className="flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed px-6 py-12 text-center">
            {Icon && (
                <div className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                    <Icon className="size-6" aria-hidden="true" />
                </div>
            )}
            <h2 className="text-base font-semibold">{title}</h2>
            {description && (
                <p className="max-w-sm text-sm text-muted-foreground">
                    {description}
                </p>
            )}
            {action && <div className="mt-1">{action}</div>}
        </div>
    );
}
