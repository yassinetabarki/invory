import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import type { ReactNode } from 'react';
import { buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { Paginated, PaginationLink } from '@/types/domain';

type PaginationProps = {
    meta: Paginated<unknown>['meta'];
    /** Show "Showing 1–25 of 120" next to the controls. Defaults to true. */
    showSummary?: boolean;
    /**
     * Props the list page reloads when the page changes (a partial reload).
     * Leave out to reload everything.
     */
    only?: string[];
};

const itemClass = cn(buttonVariants({ variant: 'outline', size: 'sm' }));

function StepItem({
    link,
    children,
    label,
    only,
}: {
    link: PaginationLink | undefined;
    children: ReactNode;
    label: string;
    only?: string[];
}) {
    if (!link?.url) {
        return (
            <span
                aria-disabled="true"
                aria-label={label}
                className={cn(itemClass, 'pointer-events-none opacity-50')}
            >
                {children}
            </span>
        );
    }

    return (
        <Link
            href={link.url}
            only={only}
            preserveScroll
            aria-label={label}
            className={itemClass}
        >
            {children}
        </Link>
    );
}

/**
 * Laravel's `meta.links` is `[previous, ...pages, next]`. The first and last
 * entries carry HTML-entity labels ("&laquo; Previous"), so they are replaced
 * by our own accessible labels and icons; page entries are "1", "2"… or "..."
 * for gaps.
 */
export function Pagination({
    meta,
    showSummary = true,
    only,
}: PaginationProps) {
    if (meta.last_page <= 1) {
        return null;
    }

    const previous = meta.links[0];
    const next = meta.links[meta.links.length - 1];
    const pages = meta.links.slice(1, -1);

    return (
        <div className="flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
            {showSummary && meta.from !== null && meta.to !== null ? (
                <p className="text-sm text-muted-foreground">
                    Showing {meta.from}–{meta.to} of {meta.total}
                </p>
            ) : (
                <span />
            )}
            <nav aria-label="Pagination">
                <ul className="flex items-center gap-1">
                    <li>
                        <StepItem
                            link={previous}
                            label="Previous page"
                            only={only}
                        >
                            <ChevronLeft aria-hidden="true" />
                            <span className="hidden sm:inline">Previous</span>
                        </StepItem>
                    </li>
                    <li className="px-2 text-sm text-muted-foreground sm:hidden">
                        Page {meta.current_page} of {meta.last_page}
                    </li>
                    {pages.map((link, index) => (
                        <li
                            // Gaps ("...") repeat, so the index is part of the key.
                            key={`${index}-${link.label}`}
                            className="hidden sm:block"
                        >
                            {link.url === null ? (
                                link.active ? (
                                    <span
                                        aria-current="page"
                                        className={cn(
                                            buttonVariants({
                                                variant: 'default',
                                                size: 'sm',
                                            }),
                                            'pointer-events-none',
                                        )}
                                    >
                                        {link.label}
                                    </span>
                                ) : (
                                    <span
                                        aria-hidden="true"
                                        className="px-2 text-sm text-muted-foreground"
                                    >
                                        …
                                    </span>
                                )
                            ) : (
                                <Link
                                    href={link.url}
                                    only={only}
                                    preserveScroll
                                    aria-label={`Page ${link.label}`}
                                    aria-current={
                                        link.active ? 'page' : undefined
                                    }
                                    className={cn(
                                        buttonVariants({
                                            variant: link.active
                                                ? 'default'
                                                : 'outline',
                                            size: 'sm',
                                        }),
                                    )}
                                >
                                    {link.label}
                                </Link>
                            )}
                        </li>
                    ))}
                    <li>
                        <StepItem link={next} label="Next page" only={only}>
                            <span className="hidden sm:inline">Next</span>
                            <ChevronRight aria-hidden="true" />
                        </StepItem>
                    </li>
                </ul>
            </nav>
        </div>
    );
}
