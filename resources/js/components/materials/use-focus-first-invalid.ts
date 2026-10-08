import { useEffect } from 'react';
import type { RefObject } from 'react';

/**
 * Moves focus to the first control marked `aria-invalid` inside `container`
 * once a submission has failed, so keyboard and screen-reader users land on the
 * problem. Does nothing while the request is in flight or when there are no
 * errors, and re-runs only when the set of errors changes.
 */
export function useFocusFirstInvalid(
    container: RefObject<HTMLElement | null>,
    errors: Record<string, unknown>,
    processing: boolean,
): void {
    const errorSignature = Object.entries(errors)
        .filter(([, message]) => Boolean(message))
        .map(([name, message]) => `${name}:${String(message)}`)
        .join('|');

    useEffect(() => {
        if (processing || errorSignature === '') {
            return;
        }

        container.current
            ?.querySelector<HTMLElement>('[aria-invalid="true"]')
            ?.focus();
    }, [container, errorSignature, processing]);
}
