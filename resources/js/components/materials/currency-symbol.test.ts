import { describe, expect, it } from 'vite-plus/test';
import { currencySymbol } from '@/components/materials/currency-symbol';

describe('currencySymbol', () => {
    it("returns the currency's own symbol from Intl", () => {
        expect(currencySymbol('EUR', 'en')).toBe('€');
        expect(currencySymbol('USD', 'en')).toBe('$');
    });

    it('returns the ISO code when the currency has no symbol in the locale', () => {
        expect(currencySymbol('TND', 'en')).toBe('TND');
    });

    it('follows the locale', () => {
        expect(currencySymbol('USD', 'fr')).toBe('$US');
    });
});
