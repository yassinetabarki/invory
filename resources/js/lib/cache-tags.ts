import { toUrl } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as materialsIndex } from '@/routes/materials';
import { index as productsIndex } from '@/routes/products';
import { index as salesIndex } from '@/routes/sales';
import type { InertiaLinkProps } from '@inertiajs/react';

/**
 * Prefetch cache tags (ADR-0009, rule 3).
 *
 * A prefetched page is cached by Inertia for 30 s. Writes do not flush that
 * cache by themselves, so every link that prefetches carries the tag of the
 * data its page shows (`cacheTags`), and every write passes the tags it
 * affects (`invalidateCacheTags`).
 */
export const CACHE_TAGS = {
    materials: 'materials',
    products: 'products',
    sales: 'sales',
    dashboard: 'dashboard',
} as const;

export type CacheTag = (typeof CACHE_TAGS)[keyof typeof CACHE_TAGS];

/** The kinds of write the app makes. */
export type CacheWrite = 'material' | 'product' | 'sale';

const INVALIDATED_BY: Record<CacheWrite, readonly CacheTag[]> = {
    // Material create / update / delete / restock: costs, capacity, stock alerts.
    material: [CACHE_TAGS.materials, CACHE_TAGS.products, CACHE_TAGS.dashboard],
    // Product create / update / delete: the BOM feeds material usage and the
    // product pickers of the sales pages.
    product: [
        CACHE_TAGS.products,
        CACHE_TAGS.materials,
        CACHE_TAGS.sales,
        CACHE_TAGS.dashboard,
    ],
    // Sale record / edit / delete: sales log, "sold (30 d)" and the dashboard.
    sale: [CACHE_TAGS.sales, CACHE_TAGS.products, CACHE_TAGS.dashboard],
};

/** The tags a write has to flush: pass them as `invalidateCacheTags`. */
export function invalidatedBy(write: CacheWrite): CacheTag[] {
    return [...INVALIDATED_BY[write]];
}

type PrefetchProps = Required<
    Pick<InertiaLinkProps, 'prefetch'> & { cacheTags: CacheTag[] }
>;

/** Spread on a `<Link>`: prefetch on hover and cache the response under `tags`. */
export function prefetchProps(...tags: CacheTag[]): PrefetchProps {
    return { prefetch: true, cacheTags: tags };
}

function pathnameOf(url: string): string {
    const path = url
        .replace(/^[a-z][a-z\d+.-]*:\/\/[^/]+/i, '')
        .split(/[?#]/)[0];

    return path.length > 1 ? path.replace(/\/+$/, '') : path || '/';
}

function isUnder(path: string, base: string): boolean {
    return path === base || path.startsWith(`${base}/`);
}

/** The tag of the page a URL leads to; null for pages that are not cached by tag. */
export function cacheTagForUrl(
    url: NonNullable<InertiaLinkProps['href']>,
): CacheTag | null {
    const path = pathnameOf(toUrl(url));
    const sections: [string, CacheTag][] = [
        [materialsIndex.url(), CACHE_TAGS.materials],
        [productsIndex.url(), CACHE_TAGS.products],
        [salesIndex.url(), CACHE_TAGS.sales],
    ];

    // The dashboard is a single page: nothing lives below it.
    if (path === pathnameOf(dashboard.url())) {
        return CACHE_TAGS.dashboard;
    }

    return (
        sections.find(([base]) => isUnder(path, pathnameOf(base)))?.[1] ?? null
    );
}

/**
 * Like `prefetchProps`, with the tag taken from the URL. Empty for URLs
 * without a tag (settings, auth): nothing could invalidate those, so they are
 * not prefetched here.
 */
export function prefetchPropsForUrl(
    url: NonNullable<InertiaLinkProps['href']>,
): Partial<PrefetchProps> {
    const tag = cacheTagForUrl(url);

    return tag === null ? {} : prefetchProps(tag);
}
