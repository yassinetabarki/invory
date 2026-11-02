import { useHttp } from '@inertiajs/react';
import { useCallback, useRef, useState } from 'react';
import ClientLookupController from '@/actions/App/Http/Controllers/ClientLookupController';
import type { ClientLookup, ClientSummary } from '@/types/domain';

/** Where a lookup stands: not asked, checking, answered (a client or none), or failed. */
export type LookupState =
    | { status: 'idle' }
    | { status: 'checking'; asked: string }
    | { status: 'found'; asked: string; client: ClientSummary }
    | { status: 'none'; asked: string }
    | { status: 'failed'; asked: string };

/**
 * `GET clients/lookup` (INV-4-b): the client with this phone (its digits) or
 * with this name (ignoring capitals). Only a hint: saving finds or adds the
 * client again. The last question asked wins; a slower earlier answer is
 * dropped. Nothing blocks typing while it runs.
 */
export function useClientLookup(by: 'phone' | 'name') {
    const { get } = useHttp<Record<string, never>, ClientLookup>();
    const [state, setState] = useState<LookupState>({ status: 'idle' });
    const latest = useRef(0);

    const lookUp = useCallback(
        (asked: string) => {
            const value = asked.trim();
            const ticket = ++latest.current;

            if (value === '') {
                setState({ status: 'idle' });

                return;
            }

            setState({ status: 'checking', asked: value });

            get(
                ClientLookupController.show.url({ query: { [by]: value } }),
            ).then(
                (data) => {
                    if (ticket !== latest.current) {
                        return;
                    }

                    if (!data || !('client' in data)) {
                        setState({ status: 'failed', asked: value });
                    } else if (data.client) {
                        setState({
                            status: 'found',
                            asked: value,
                            client: data.client,
                        });
                    } else {
                        setState({ status: 'none', asked: value });
                    }
                },
                () => {
                    if (ticket === latest.current) {
                        setState({ status: 'failed', asked: value });
                    }
                },
            );
        },
        [by, get],
    );

    const reset = useCallback(() => {
        latest.current++;
        setState({ status: 'idle' });
    }, []);

    return { state, lookUp, reset };
}
