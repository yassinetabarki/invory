import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import type { ReactNode } from 'react';
import { prefetchPropsForUrl } from '@/lib/cache-tags';
import { cn } from '@/lib/utils';

type StatTone = 'default' | 'warning' | 'danger';

type StatCardProps = {
    /** What the number is, e.g. "Needs restock". */
    label: string;
    /** The figure. Pass formatted text (`<Money>`, a formatted count), never raw maths. */
    value: ReactNode;
    /** Context under the figure, e.g. "3 low · 1 out of stock". */
    hint?: ReactNode;
    /** Makes the whole card a link (a Wayfinder route or a URL string). */
    href?: string | { url: string };
    /** Colours the hint: `warning` (amber) or `danger` (red). */
    tone?: StatTone;
    className?: string;
};

const HINT_TONE: Record<StatTone, string> = {
    default: 'text-muted-foreground',
    warning: 'font-semibold text-warning-foreground',
    danger: 'font-semibold text-danger-foreground',
};

/** A KPI card: label, big figure and a hint, optionally a link to the full list. */
export function StatCard({
    label,
    value,
    hint,
    href,
    tone = 'default',
    className,
}: StatCardProps) {
    const content = (
        <>
            <span className="text-sm font-medium text-muted-foreground">
                {label}
            </span>{' '}
            {/* Plain spaces keep the card's accessible name readable ("Needs restock 4 …"). */}
            <span className="text-[1.75rem] leading-tight font-bold tracking-tight tabular-nums">
                {value}
            </span>{' '}
            {(hint || href) && (
                <span
                    className={cn(
                        'flex items-center gap-1 text-[0.8125rem]',
                        HINT_TONE[tone],
                    )}
                >
                    {hint}
                    {href && (
                        <ArrowRight
                            className="size-3.5 shrink-0"
                            aria-hidden="true"
                        />
                    )}
                </span>
            )}
        </>
    );

    const base =
        'flex flex-col gap-1.5 rounded-xl border bg-card px-5 py-[18px] text-card-foreground';

    if (href) {
        const url = typeof href === 'string' ? href : href.url;

        return (
            <Link
                href={url}
                {...prefetchPropsForUrl(url)}
                data-slot="stat-card"
                className={cn(
                    base,
                    'transition-colors outline-none hover:border-primary/40 hover:bg-accent focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50',
                    className,
                )}
            >
                {content}
            </Link>
        );
    }

    return (
        <div data-slot="stat-card" className={cn(base, className)}>
            {content}
        </div>
    );
}
