import { describe, expect, it } from 'vite-plus/test';
import {
    EMPTY_VALUE,
    formatMoney,
    formatPercent,
    formatQuantity,
} from './format';

const eur = { currency: 'EUR', locale: 'en' };

describe('formatMoney', () => {
    it('formats the worked-example unit cost as €35.75', () => {
        expect(formatMoney('35.7540', eur)).toBe('€35.75');
    });

    it('always shows 2 decimals for normal amounts', () => {
        expect(formatMoney('12.0000', eur)).toBe('€12.00');
        expect(formatMoney('0.3500', eur)).toBe('€0.35');
    });

    it('rounds half up on the string value', () => {
        expect(formatMoney('1.0050', eur)).toBe('€1.01');
        expect(formatMoney('1.0049', eur)).toBe('€1.00');
    });

    it('rounds tiny costs to 2 decimals by default', () => {
        expect(formatMoney('0.0040', eur)).toBe('€0.00');
    });

    describe('precise', () => {
        it('shows 4 fraction digits for per-unit costs below 0.01', () => {
            expect(formatMoney('0.0040', { ...eur, precise: true })).toBe(
                '€0.0040',
            );
            expect(formatMoney('0.0099', { ...eur, precise: true })).toBe(
                '€0.0099',
            );
            expect(formatMoney('0.0001', { ...eur, precise: true })).toBe(
                '€0.0001',
            );
        });

        it('keeps 2 decimals from 0.01 upwards', () => {
            expect(formatMoney('0.0100', { ...eur, precise: true })).toBe(
                '€0.01',
            );
            expect(formatMoney('0.1200', { ...eur, precise: true })).toBe(
                '€0.12',
            );
            expect(formatMoney('35.7540', { ...eur, precise: true })).toBe(
                '€35.75',
            );
        });

        it('keeps zero at 2 decimals', () => {
            expect(formatMoney('0.0000', { ...eur, precise: true })).toBe(
                '€0.00',
            );
        });

        it('handles negative tiny values', () => {
            expect(formatMoney('-0.0040', { ...eur, precise: true })).toBe(
                '-€0.0040',
            );
        });
    });

    it('formats zero', () => {
        expect(formatMoney('0.0000', eur)).toBe('€0.00');
        expect(formatMoney('0', eur)).toBe('€0.00');
    });

    it('does not show a minus sign for negative values that round to zero', () => {
        expect(formatMoney('-0.0040', eur)).toBe('€0.00');
    });

    it('formats negative amounts', () => {
        expect(formatMoney('-12.5000', eur)).toBe('-€12.50');
    });

    it('does not lose precision on large values (no float conversion)', () => {
        // 17 significant digits: a double would turn this into ...1234.5679 or worse.
        expect(formatMoney('12345678901234.5678', eur)).toBe(
            '€12,345,678,901,234.57',
        );
        expect(formatMoney('99999999999999999999.9949', eur)).toBe(
            '€99,999,999,999,999,999,999.99',
        );
        expect(formatMoney('9007199254740993.0000', eur)).toBe(
            '€9,007,199,254,740,993.00',
        );
    });

    it('uses the given currency and locale', () => {
        expect(
            formatMoney('1234.5000', { currency: 'USD', locale: 'en' }),
        ).toBe('$1,234.50');

        const french = formatMoney('1234.5000', {
            currency: 'EUR',
            locale: 'fr',
        });
        // Intl uses non-breaking / narrow no-break spaces in French.
        expect(french.replace(/\s/g, ' ')).toBe('1 234,50 €');
    });

    it.each([null, undefined, '', 'abc', '1e5', '12,5', ' 1.00'])(
        'renders %j as an em dash',
        (value) => {
            expect(formatMoney(value, eur)).toBe(EMPTY_VALUE);
        },
    );
});

/**
 * TND (Tunisian dinar, the company currency) pins TODAY's behaviour.
 *
 * TND's ISO minor unit is 3 decimals (millimes), but formatMoney forces 2
 * fraction digits for every currency (costing-domain rule 7). Whether to switch
 * to the currency's own minor units is a pending product decision; these tests
 * make any later change to the precision explicit and deliberate.
 * Intl separates "TND" and the number with a no-break space (U+00A0).
 */
describe('formatMoney (TND, en)', () => {
    const tnd = { currency: 'TND', locale: 'en' };
    const nbsp = ' ';

    it('formats the worked-example unit cost with 2 decimals (not 35.754)', () => {
        expect(formatMoney('35.7540', tnd)).toBe(`TND${nbsp}35.75`);
    });

    it('keeps 4 digits for precise values below 0.01', () => {
        expect(formatMoney('0.0040', { ...tnd, precise: true })).toBe(
            `TND${nbsp}0.0040`,
        );
    });

    it('rounds tiny costs to 2 decimals when not precise', () => {
        expect(formatMoney('0.0040', tnd)).toBe(`TND${nbsp}0.00`);
    });

    it('keeps 2 decimals from 0.01 upwards, even when precise', () => {
        expect(formatMoney('0.0100', { ...tnd, precise: true })).toBe(
            `TND${nbsp}0.01`,
        );
    });

    it('formats zero', () => {
        expect(formatMoney('0.0000', tnd)).toBe(`TND${nbsp}0.00`);
    });

    it('formats negative amounts (the minus sign precedes the code)', () => {
        expect(formatMoney('-12.5000', tnd)).toBe(`-TND${nbsp}12.50`);
    });

    it('formats large values without losing precision', () => {
        expect(formatMoney('12345678901234.5678', tnd)).toBe(
            `TND${nbsp}12,345,678,901,234.57`,
        );
    });
});

describe('formatQuantity', () => {
    it('drops trailing zeros and appends the unit', () => {
        expect(formatQuantity('1.9800', { locale: 'en', unit: 'm' })).toBe(
            '1.98 m',
        );
        expect(formatQuantity('8.0000', { locale: 'en', unit: 'pc' })).toBe(
            '8 pc',
        );
    });

    it('keeps up to 4 decimals for tiny quantities', () => {
        expect(formatQuantity('0.0001', { locale: 'en', unit: 'm' })).toBe(
            '0.0001 m',
        );
    });

    it('groups thousands without losing precision', () => {
        expect(formatQuantity('20000.0000', { locale: 'en', unit: 'm' })).toBe(
            '20,000 m',
        );
        expect(formatQuantity('12345678901234.5678', { locale: 'en' })).toBe(
            '12,345,678,901,234.5678',
        );
    });

    it('works without a unit', () => {
        expect(formatQuantity('250.0000', { locale: 'en' })).toBe('250');
    });

    it('formats zero', () => {
        expect(formatQuantity('0.0000', { locale: 'en', unit: 'kg' })).toBe(
            '0 kg',
        );
    });

    it('uses the locale number format', () => {
        const german = formatQuantity('1234.5000', { locale: 'de', unit: 'm' });
        expect(german).toBe('1.234,5 m');
    });

    it.each([null, undefined, '', 'abc'])(
        'renders %j as an em dash (no unit)',
        (value) => {
            expect(formatQuantity(value, { locale: 'en', unit: 'm' })).toBe(
                EMPTY_VALUE,
            );
        },
    );
});

describe('formatPercent', () => {
    it('shows percent points as-is, without decimals when whole', () => {
        expect(formatPercent('10.00', 'en')).toBe('10%');
        expect(formatPercent('0.00', 'en')).toBe('0%');
    });

    it('keeps meaningful decimals', () => {
        expect(formatPercent('7.50', 'en')).toBe('7.5%');
        expect(formatPercent('12.25', 'en')).toBe('12.25%');
    });

    it('does not scale the value (10.00 is 10 %, not 1000 %)', () => {
        expect(formatPercent('99.99', 'en')).toBe('99.99%');
    });

    it.each([null, undefined, '', 'x'])('renders %j as an em dash', (value) => {
        expect(formatPercent(value, 'en')).toBe(EMPTY_VALUE);
    });
});
