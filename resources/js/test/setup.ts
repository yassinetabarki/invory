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

afterEach(() => {
    cleanup();
});
