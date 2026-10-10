import { useHttp } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import MaterialOptionController from '@/actions/App/Http/Controllers/MaterialOptionController';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import type { MaterialOption, MaterialOptionsQuery } from '@/types/domain';

/** How long the search text must rest before the server is asked (ms). */
export const SEARCH_DEBOUNCE_MS = 250;

/** The most options one request brings (the server's default). */
export const OPTIONS_LIMIT = 20;

type UseMaterialOptionsArgs = {
    /** What the person typed (not yet debounced). */
    search: string;
    /** Ids of the materials already picked on the form: the server returns them too. */
    include: readonly number[];
    limit?: number;
    /** Called with every successful response (not with stale ones). Does not need to be stable. */
    onLoaded?: (options: MaterialOption[]) => void;
};

type UseMaterialOptionsResult = {
    /** The options of the last answered request; empty after a failure. */
    options: MaterialOption[];
    /** A request is waiting out the debounce or in flight. */
    loading: boolean;
    /** The last request failed (network, server error or an invalid reply). */
    error: boolean;
    /** Ask again after a failure. */
    retry: () => void;
};

type Settled = { key: string; options: MaterialOption[]; failed: boolean };

/**
 * Material options from `GET /materials/options` through Inertia's `useHttp`
 * (ADR-0009 rule 5). The first request (empty search) goes out when the hook
 * mounts, i.e. when the picker opens; later ones wait `SEARCH_DEBOUNCE_MS`
 * after the last keystroke.
 *
 * Last request wins: starting or abandoning a request aborts the one in
 * flight, and an answer whose request is no longer current is ignored, so a
 * slow early response can never overwrite a newer one. (`useHttp` keeps a
 * single abort controller per hook, so the abort is best effort; the
 * "is this still the current request" check is what guarantees correctness.)
 *
 * The query rides in the URL (Wayfinder builds it), not in `useHttp`'s form
 * data: the data would be read from state that has not updated yet when the
 * request fires.
 */
export function useMaterialOptions({
    search,
    include,
    limit = OPTIONS_LIMIT,
    onLoaded,
}: UseMaterialOptionsArgs): UseMaterialOptionsResult {
    const { get, cancel } = useHttp<Record<string, never>, MaterialOption[]>();
    const term = useDebouncedValue(search.trim(), SEARCH_DEBOUNCE_MS);
    const [attempt, setAttempt] = useState(0);
    const [settled, setSettled] = useState<Settled | null>(null);

    const onLoadedRef = useRef(onLoaded);

    useEffect(() => {
        onLoadedRef.current = onLoaded;
    });

    const query: MaterialOptionsQuery = {
        ...(term === '' ? {} : { search: term }),
        limit,
        // Sorted, so reordering rows does not change the request.
        ...(include.length === 0
            ? {}
            : { include: [...include].sort((a, b) => a - b) }),
    };
    const url = MaterialOptionController.index.url({ query });
    // A retry asks the same URL again, so it needs its own identity.
    const key = `${attempt}:${url}`;

    useEffect(() => {
        let current = true;

        get(url).then(
            (data) => {
                if (!current) {
                    return;
                }

                // A rejected query resolves with `undefined` (422), never with a list.
                if (!Array.isArray(data)) {
                    setSettled({ key, options: [], failed: true });

                    return;
                }

                onLoadedRef.current?.(data);
                setSettled({ key, options: data, failed: false });
            },
            () => {
                // An abort only follows the cleanup below, which already made this run stale.
                if (!current) {
                    return;
                }

                setSettled({ key, options: [], failed: true });
            },
        );

        return () => {
            current = false;
            cancel();
        };
    }, [get, cancel, url, key]);

    const answered = settled?.key === key;

    return {
        options: settled?.options ?? [],
        // The text typed is ahead of the text asked while the debounce runs.
        loading: !answered || search.trim() !== term,
        error: answered && settled.failed,
        retry: () => setAttempt((count) => count + 1),
    };
}
