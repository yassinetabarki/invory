import { describe, expect, it } from 'vite-plus/test';

/**
 * Hover prefetch fires a full page request per hover, so no app source may
 * turn it on with a `prefetch` JSX prop. Scans the raw sources, which also covers components without a test of their
 * own (headers, tables, cards).
 */
const sources = import.meta.glob(
    [
        '../components/**/*.tsx',
        '../pages/**/*.tsx',
        '../layouts/**/*.tsx',
        '!../**/*.test.tsx',
        '!../components/ui/**',
    ],
    { query: '?raw', import: 'default', eager: true },
) as Record<string, string>;

/** A `prefetch` attribute on its own line, or inline on a `<Link …>` tag. */
const PREFETCH_PROP =
    /^\s*prefetch(?![\w-])(?:=|\s*\/?>?\s*$)|<Link\b[^\n]*\sprefetch(?![\w-])/m;

function offenders(pattern: RegExp): string[] {
    return Object.entries(sources)
        .filter(([, source]) => pattern.test(source))
        .map(([file]) => file);
}

describe('Inertia links', () => {
    it('scans the app sources', () => {
        expect(Object.keys(sources).length).toBeGreaterThan(0);
    });

    it('never prefetch on hover', () => {
        expect(offenders(PREFETCH_PROP)).toEqual([]);
    });
});
