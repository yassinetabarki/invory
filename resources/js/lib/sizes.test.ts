import { describe, expect, it } from 'vite-plus/test';
import { formatSizes } from '@/lib/sizes';

describe('formatSizes', () => {
    it.each([
        {
            name: 'several sizes, in order',
            sizes: [
                { label: 'S', units: 10 },
                { label: 'M', units: 20 },
                { label: 'L', units: 15 },
                { label: 'XL', units: 5 },
            ],
            locale: 'en',
            expected: 'S 10 · M 20 · L 15 · XL 5',
        },
        {
            name: 'a size with 0 units is left out',
            sizes: [
                { label: 'S', units: 10 },
                { label: 'M', units: 0 },
                { label: 'XL', units: 5 },
            ],
            locale: 'en',
            expected: 'S 10 · XL 5',
        },
        {
            name: 'thousands follow the locale',
            sizes: [{ label: 'M', units: 12000 }],
            locale: 'en',
            expected: 'M 12,000',
        },
        {
            name: 'a locale with another separator',
            sizes: [{ label: 'M', units: 12000 }],
            locale: 'de',
            expected: 'M 12.000',
        },
        {
            name: 'number sizes get a colon, letter sizes do not',
            sizes: [
                { label: '36', units: 4 },
                { label: '37', units: 8 },
                { label: '38', units: 0 },
            ],
            locale: 'en',
            expected: '36: 4 · 37: 8',
        },
        {
            name: 'one size and sizes with letters and digits are not numbers',
            sizes: [
                { label: 'One size', units: 3 },
                { label: '2XL', units: 1 },
            ],
            locale: 'en',
            expected: 'One size 3 · 2XL 1',
        },
    ])('$name', ({ sizes, locale, expected }) => {
        expect(formatSizes(sizes, locale)).toBe(expected);
    });
});
