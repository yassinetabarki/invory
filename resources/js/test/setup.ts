import * as matchers from '@testing-library/jest-dom/matchers';
import { cleanup } from '@testing-library/react';
import { afterEach, expect } from 'vite-plus/test';

expect.extend(matchers);

// jsdom lacks the pointer-capture / scroll APIs Radix (Select, AlertDialog)
// relies on, so every test file gets no-op versions.
const domPolyfills = {
    hasPointerCapture: () => false,
    setPointerCapture: () => undefined,
    releasePointerCapture: () => undefined,
    scrollIntoView: () => undefined,
};

if (typeof Element !== 'undefined') {
    for (const [name, implementation] of Object.entries(domPolyfills)) {
        if (!(name in Element.prototype)) {
            Object.defineProperty(Element.prototype, name, {
                value: implementation,
                configurable: true,
                writable: true,
            });
        }
    }
}

/**
 * Browser APIs jsdom does not implement, as inert stand-ins so that modules
 * which touch them at import or render time (the sidebar's `useIsMobile`,
 * Radix Popover, cmdk's list, the photo field's previews) load in tests.
 * Tests that care can replace them (`vi.spyOn(URL, 'createObjectURL')`).
 */
if (typeof window !== 'undefined') {
    if (!window.matchMedia) {
        // Desktop-sized, light, no reduced motion: nothing matches.
        window.matchMedia = (query: string): MediaQueryList => ({
            matches: false,
            media: query,
            onchange: null,
            addEventListener: () => undefined,
            removeEventListener: () => undefined,
            addListener: () => undefined,
            removeListener: () => undefined,
            dispatchEvent: () => false,
        });
    }

    if (typeof window.ResizeObserver === 'undefined') {
        class ResizeObserverStub implements ResizeObserver {
            observe = () => undefined;
            unobserve = () => undefined;
            disconnect = () => undefined;
        }

        window.ResizeObserver = ResizeObserverStub;
    }

    // Vitest's own jsdom shim throws for jsdom Files, so these are always replaced.
    let objectUrlCount = 0;

    URL.createObjectURL = () => `blob:invory-test/${++objectUrlCount}`;
    URL.revokeObjectURL = () => undefined;
}

afterEach(() => {
    cleanup();
});
