import { describe, expect, it } from 'vite-plus/test';
import { ean13Modules } from '@/lib/ean13';

describe('ean13Modules', () => {
    it('encodes the published EAN-13 pattern and refuses what cannot scan', () => {
        // 4006381333931: first digit 4 picks L G L L G G for 0 0 6 3 8 1, then R for 3 3 3 9 3 1.
        // guard 101 | 0001101 0100111 0101111 0111101 0001001 0110011 | 01010 |
        // 1000010 1000010 1000010 1110100 1000010 1100110 | guard 101
        expect(ean13Modules('4006381333931')).toBe(
            '10100011010100111010111101111010001001011001101010100001010000101000010111010010000101100110101',
        );

        // An in-store number: 95 modules with the three guards where they belong.
        const inStore = ean13Modules('2000000000015');

        expect(inStore).toHaveLength(95);
        expect(inStore?.slice(0, 3)).toBe('101');
        expect(inStore?.slice(45, 50)).toBe('01010');
        expect(inStore?.slice(92)).toBe('101');

        // Wrong check digit, 12 digits, and non-digits draw nothing.
        expect(ean13Modules('4006381333932')).toBeNull();
        expect(ean13Modules('400638133393')).toBeNull();
        expect(ean13Modules('40063813339 1')).toBeNull();
    });
});
