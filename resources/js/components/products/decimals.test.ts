import { describe, expect, it } from 'vite-plus/test';
import { isNegativeDecimal, trimDecimal } from '@/components/products/decimals';

describe('trimDecimal', () => {
    it.each([
        ['12.0000', '12'],
        ['1.8000', '1.8'],
        ['0.0040', '0.004'],
        ['10.00', '10'],
        ['0.0000', '0'],
        ['100.00', '100'],
        ['126.0000', '126'],
        ['53.2460', '53.246'],
        ['-5.7540', '-5.754'],
    ])('turns %s into %s', (input, expected) => {
        expect(trimDecimal(input)).toBe(expected);
    });

    it('keeps values without a fraction exactly as they are', () => {
        expect(trimDecimal('100')).toBe('100');
        expect(trimDecimal('0')).toBe('0');
    });

    it('returns anything that is not a plain decimal untouched', () => {
        expect(trimDecimal('')).toBe('');
        expect(trimDecimal('abc')).toBe('abc');
        expect(trimDecimal('1,5')).toBe('1,5');
        expect(trimDecimal('1.50.0')).toBe('1.50.0');
    });
});

describe('isNegativeDecimal', () => {
    it.each(['-5.7540', '-0.0001', '-19.18', '-1'])('%s is negative', (v) => {
        expect(isNegativeDecimal(v)).toBe(true);
    });

    it.each(['53.2460', '0.0000', '-0.0000', '0', '', '19.18'])(
        '%s is not negative',
        (v) => {
            expect(isNegativeDecimal(v)).toBe(false);
        },
    );
});
