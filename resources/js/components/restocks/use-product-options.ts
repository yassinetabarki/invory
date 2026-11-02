import { useHttp } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import RestockProductController from '@/actions/App/Http/Controllers/RestockProductController';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import type {
    RestockProductOptionsQuery,
    RestockProductRef,
} from '@/types/domain';

/** How long the search text must rest before the server is asked (ms). */
export const PRODUCT_SEARCH_DEBOUNCE_MS = 250;

type Settled = { key: string; options: RestockProductRef[]; failed: boolean };

/**
 * The products a restock can take, from `GET restocks/products/options`
 * (INV-3; ADR-0009 rule 5): every product but deleted ones, by name. The first
 * request goes out when the picker opens, later ones wait out the debounce;
 * the last request wins, and a failure can be retried. `include` brings the
 * chosen product along.
 */
export function useProductOptions(search: string, include: number | null) {
    const { get, cancel } = useHttp<
        Record<string, never>,
        RestockProductRef[]
    >();
    const term = useDebouncedValue(search.trim(), PRODUCT_SEARCH_DEBOUNCE_MS);
    const [attempt, setAttempt] = useState(0);
    const [settled, setSettled] = useState<Settled | null>(null);

    const query: RestockProductOptionsQuery = {
        ...(term === '' ? {} : { search: term }),
        ...(include === null ? {} : { include: [include] }),
    };
    const url = RestockProductController.index.url({ query });
    const key = `${attempt}:${url}`;

    useEffect(() => {
        let current = true;

        get(url).then(
            (data) => {
                if (current) {
                    setSettled(
                        Array.isArray(data)
                            ? { key, options: data, failed: false }
                            : { key, options: [], failed: true },
                    );
                }
            },
            () => {
                if (current) {
                    setSettled({ key, options: [], failed: true });
                }
            },
        );

        return () => {
            current = false;
            cancel();
        };
    }, [get, cancel, url, key]);

    const answered = settled?.key === key;

    return {
        options: answered ? settled.options : (settled?.options ?? []),
        loading: !answered || search.trim() !== term,
        error: answered && settled.failed,
        retry: () => setAttempt((count) => count + 1),
    };
}
