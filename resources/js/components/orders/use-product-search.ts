import { useHttp } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import OrderProductSearchController from '@/actions/App/Http/Controllers/OrderProductSearchController';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import type { OrderProductSearch } from '@/types/domain';

/** How long the search text must rest before the server is asked (ms). */
export const ORDER_SEARCH_DEBOUNCE_MS = 250;

type Settled = { key: string; found: OrderProductSearch | null };

export type ProductSearchState =
    | { status: 'idle' }
    | { status: 'searching' }
    | { status: 'failed' }
    | { status: 'found'; found: OrderProductSearch };

/**
 * "Search products" on New order and Edit order (INV-13, AC-1), from `GET
 * orders/products/search?q=` (ADR-0009 rule 5): at most 6 active products,
 * each with its shelf stock per colour and size. Nothing is asked while the box
 * is empty; a search goes out once the text has rested, the last one wins, and
 * a failure can be retried.
 */
export function useProductSearch(search: string): {
    state: ProductSearchState;
    retry: () => void;
} {
    const { get, cancel } = useHttp<
        Record<string, never>,
        OrderProductSearch
    >();
    const typed = search.trim();
    const term = useDebouncedValue(typed, ORDER_SEARCH_DEBOUNCE_MS);
    const [attempt, setAttempt] = useState(0);
    const [settled, setSettled] = useState<Settled | null>(null);
    const url =
        term === ''
            ? null
            : OrderProductSearchController.index.url({ query: { q: term } });
    const key = `${attempt}:${url}`;

    useEffect(() => {
        if (url === null) {
            return;
        }

        let current = true;

        get(url).then(
            (data) => {
                if (current) {
                    setSettled({
                        key,
                        found:
                            data && Array.isArray(data.products) ? data : null,
                    });
                }
            },
            () => {
                if (current) {
                    setSettled({ key, found: null });
                }
            },
        );

        return () => {
            current = false;
            cancel();
        };
    }, [get, cancel, url, key]);

    const retry = () => setAttempt((count) => count + 1);

    if (typed === '') {
        return { state: { status: 'idle' }, retry };
    }

    if (typed !== term || settled?.key !== key) {
        return { state: { status: 'searching' }, retry };
    }

    return settled.found === null
        ? { state: { status: 'failed' }, retry }
        : { state: { status: 'found', found: settled.found }, retry };
}
