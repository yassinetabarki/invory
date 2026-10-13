import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';

type SkeletonRowProps = {
    /** Show the leading square thumbnail placeholder. */
    thumb?: boolean;
    className?: string;
};

type SkeletonCardProps = {
    /** What is loading; announced as "Loading {label}…". */
    label: string;
    /** Visible panel title, shown while the data loads. */
    title?: string;
    /** Number of placeholder rows. */
    rows?: number;
    /** Show the thumbnail placeholder on every row. */
    thumb?: boolean;
    className?: string;
};

type SkeletonStatProps = {
    /** The KPI label, shown as-is and announced as "Loading {label}…". */
    label: string;
    className?: string;
};

/**
 * One decorative list row (optional thumb, two text lines, a short trailing
 * block), sized like a needs-restock row. Hidden from assistive tech: the
 * parent card announces the loading state once.
 */
export function SkeletonRow({ thumb = true, className }: SkeletonRowProps) {
    return (
        <div
            data-slot="skeleton-row"
            aria-hidden="true"
            className={cn('flex items-center gap-3 border-t py-2.5', className)}
        >
            {thumb && (
                <Skeleton
                    aria-hidden="true"
                    className="size-10 shrink-0 motion-reduce:animate-none"
                />
            )}
            <div className="flex min-w-0 flex-1 flex-col gap-1.5">
                <Skeleton
                    aria-hidden="true"
                    className="h-4 w-2/3 motion-reduce:animate-none"
                />
                <Skeleton
                    aria-hidden="true"
                    className="h-3 w-1/3 motion-reduce:animate-none"
                />
            </div>
            <Skeleton
                aria-hidden="true"
                className="h-5 w-12 shrink-0 rounded-full motion-reduce:animate-none"
            />
        </div>
    );
}

/**
 * A dashboard-panel-shaped placeholder for a `<Deferred fallback>`: the panel
 * shell, an optional title, `rows` skeleton rows and a single screen-reader
 * status ("Loading {label}…").
 */
export function SkeletonCard({
    label,
    title,
    rows = 3,
    thumb = true,
    className,
}: SkeletonCardProps) {
    return (
        <div
            className={cn(
                'flex min-w-0 flex-col gap-3 rounded-xl border bg-card p-5 text-card-foreground',
                className,
            )}
        >
            <span role="status" className="sr-only">
                Loading {label}…
            </span>
            {title && <h2 className="text-base font-bold">{title}</h2>}
            <div>
                {Array.from({ length: rows }, (_, index) => (
                    <SkeletonRow key={index} thumb={thumb} />
                ))}
            </div>
        </div>
    );
}

/**
 * A KPI-card-shaped placeholder (same shell as `<StatCard>`): the visible
 * label, a pulsing figure and hint, and a single screen-reader status
 * ("Loading {label}…").
 */
export function SkeletonStat({ label, className }: SkeletonStatProps) {
    return (
        <div
            className={cn(
                'flex flex-col gap-1.5 rounded-xl border bg-card px-5 py-[18px] text-card-foreground',
                className,
            )}
        >
            <span role="status" className="sr-only">
                Loading {label}…
            </span>
            <span
                aria-hidden="true"
                className="text-sm font-medium text-muted-foreground"
            >
                {label}
            </span>
            <Skeleton
                aria-hidden="true"
                className="h-8 w-24 motion-reduce:animate-none"
            />
            <Skeleton
                aria-hidden="true"
                className="h-3.5 w-32 motion-reduce:animate-none"
            />
        </div>
    );
}
