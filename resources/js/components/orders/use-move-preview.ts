import { useHttp } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import { preview as previewRoute } from '@/routes/orders/lines/moves';
import type { MovePreview, OrderLineMove } from '@/types/domain';

/** How long the number typed must rest before the server is asked (ms). */
export const PREVIEW_DEBOUNCE_MS = 250;

/** The two moves that take or return materials, so the only ones with a table. */
export type PreviewMove = MovePreview['move'];

export function hasPreview(move: OrderLineMove): move is PreviewMove {
    return move === 'start_cutting' || move === 'back_to_start';
}

/** What the window knows about the materials of the move for the number typed now. */
export type PreviewState =
    | { status: 'idle' } // no table for this move, or nothing typed in the field yet
    | { status: 'loading' } // the number is waiting out the debounce, or its request is in flight
    | { status: 'failed'; retry: () => void } // network, server error or an invalid reply
    | { status: 'invalid'; message: string } // the server refused the number (422)
    | { status: 'ready'; preview: MovePreview };

type UseMovePreviewArgs = {
    order: number;
    line: number;
    move: OrderLineMove;
    /** The text typed in "How many units?" (not yet debounced). */
    units: string;
};

type Outcome =
    | { kind: 'ready'; preview: MovePreview }
    | { kind: 'invalid'; message: string }
    | { kind: 'failed' };
type Settled = { key: string; outcome: Outcome };

function isMovePreview(data: unknown): data is MovePreview {
    return (
        typeof data === 'object' &&
        data !== null &&
        'rows' in data &&
        Array.isArray(data.rows)
    );
}

/**
 * The move window's materials table, from `GET orders.lines.moves.preview`
 * through Inertia's `useHttp` (ADR-0009 rule 5), the same pattern as the
 * material picker: the number typed waits `PREVIEW_DEBOUNCE_MS` after the last
 * keystroke, starting a request aborts the one in flight, and an answer whose
 * request is no longer current is ignored, so a slow early answer never
 * overwrites a newer one. Nothing is computed here: `ready` carries the
 * server's figures as sent.
 *
 * It stays `loading` until the answer for the number typed now is in, so the
 * window never shows (or lets someone confirm) a table of another number.
 * Moves without a table, and a field nobody has typed in yet, are `idle` and
 * ask nothing. Once a number has been typed, emptying the field is asked like
 * any other value, so the server's own words ("Enter a whole number of units,
 * at least 1.") come back as `invalid`.
 */
export function useMovePreview({
    order,
    line,
    move,
    units,
}: UseMovePreviewArgs): PreviewState {
    const { get, cancel } = useHttp<Record<string, never>, MovePreview>();
    const typed = units.trim();
    const term = useDebouncedValue(typed, PREVIEW_DEBOUNCE_MS);
    // Once something has been typed, the field counts as touched: emptying it is then a value to ask about (422).
    const [touched, setTouched] = useState(false);
    const [attempt, setAttempt] = useState(0);
    const [settled, setSettled] = useState<Settled | null>(null);

    if (typed !== '' && !touched) {
        setTouched(true);
    }

    // The empty value is asked once it is the number typed (not while a number waits out the debounce).
    const url =
        hasPreview(move) && (term !== '' || (touched && typed === ''))
            ? previewRoute.url(
                  { order, line },
                  { query: { move, units: term } },
              )
            : null;
    // A retry asks the same URL again, so it needs its own identity.
    const key = `${attempt}:${url}`;

    useEffect(() => {
        if (url === null) {
            return;
        }

        let current = true;
        let message: string | null = null;

        get(url, {
            onError: (errors) => {
                message = errors.units ?? errors.move ?? null;
            },
        }).then(
            (data) => {
                if (!current) {
                    return;
                }

                // A refused number resolves without a preview (422); `onError` ran just before.
                if (!isMovePreview(data)) {
                    setSettled({
                        key,
                        outcome:
                            message === null
                                ? { kind: 'failed' }
                                : { kind: 'invalid', message },
                    });

                    return;
                }

                setSettled({ key, outcome: { kind: 'ready', preview: data } });
            },
            () => {
                // An abort only follows the cleanup below, which already made this run stale.
                if (current) {
                    setSettled({ key, outcome: { kind: 'failed' } });
                }
            },
        );

        return () => {
            current = false;
            cancel();
        };
    }, [get, cancel, url, key]);

    // Before the first keystroke nothing is asked; after it, an empty field is asked like any number.
    if (!hasPreview(move) || (typed === '' && !touched)) {
        return { status: 'idle' };
    }

    // The number typed is ahead of the number asked while the debounce runs.
    if (url === null || settled?.key !== key || typed !== term) {
        return { status: 'loading' };
    }

    switch (settled.outcome.kind) {
        case 'ready':
            return { status: 'ready', preview: settled.outcome.preview };
        case 'invalid':
            return { status: 'invalid', message: settled.outcome.message };
        case 'failed':
            return {
                status: 'failed',
                retry: () => setAttempt((count) => count + 1),
            };
    }
}
