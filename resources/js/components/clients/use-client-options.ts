import { useHttp } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import ClientOptionController from '@/actions/App/Http/Controllers/ClientOptionController';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import type { ClientOptionsQuery, ClientRef } from '@/types/domain';

/** How long the search text must rest before the server is asked (ms). */
export const CLIENT_SEARCH_DEBOUNCE_MS = 250;

type Settled = { key: string; options: ClientRef[]; failed: boolean };

/**
 * The client picker's options from `GET clients/options` (INV-4; ADR-0009
 * rule 5), as the material picker does: the first request goes out when the
 * picker opens, later ones wait out the debounce; the last request wins, and a
 * failure can be retried. `include` brings the chosen client along.
 */
export function useClientOptions(search: string, include: number | null) {
    const { get, cancel } = useHttp<Record<string, never>, ClientRef[]>();
    const term = useDebouncedValue(search.trim(), CLIENT_SEARCH_DEBOUNCE_MS);
    const [attempt, setAttempt] = useState(0);
    const [settled, setSettled] = useState<Settled | null>(null);

    const query: ClientOptionsQuery = {
        ...(term === '' ? {} : { search: term }),
        ...(include === null ? {} : { include: [include] }),
    };
    const url = ClientOptionController.index.url({ query });
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
