import type { ReactNode } from 'react';

type PageHeaderProps = {
    title: string;
    description?: string;
    /** Buttons or links shown beside the title (stacked under it on mobile). */
    actions?: ReactNode;
};

export function PageHeader({ title, description, actions }: PageHeaderProps) {
    return (
        <header className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div className="min-w-0 space-y-1">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {title}
                </h1>
                {description && (
                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
            {actions && (
                <div className="flex shrink-0 flex-wrap items-center gap-2">
                    {actions}
                </div>
            )}
        </header>
    );
}
