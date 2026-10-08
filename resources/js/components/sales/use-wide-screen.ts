import { useSyncExternalStore } from 'react';

/** Tailwind's `lg` breakpoint: from here the sale form sits beside the table. */
export const WIDE_SCREEN_QUERY = '(min-width: 1024px)';

function subscribe(onChange: () => void): () => void {
    const query = window.matchMedia(WIDE_SCREEN_QUERY);

    query.addEventListener('change', onChange);

    return () => query.removeEventListener('change', onChange);
}

function getSnapshot(): boolean {
    return window.matchMedia(WIDE_SCREEN_QUERY).matches;
}

/** Before the browser can be asked (never the case in the SPA), assume wide. */
function getServerSnapshot(): boolean {
    return true;
}

/**
 * True when the viewport is at least `lg` wide. Follows window resizes, so the
 * sale form moves between the side panel and the sheet without a reload.
 */
export function useIsWideScreen(): boolean {
    return useSyncExternalStore(subscribe, getSnapshot, getServerSnapshot);
}
