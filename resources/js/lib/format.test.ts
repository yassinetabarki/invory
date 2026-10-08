import { describe, expect, it, vi } from 'vite-plus/test';
import {
    EMPTY_VALUE,
    formatCount,
    formatDate,
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
 * TND (Tunisian dinar, the company currency) is shown with its ISO minor unit:
 * 3 decimals (millimes). Rule: money uses each currency's own minor-unit digits
 * (decided 2026-10-08, backlog F-10); `precise` shows 4 digits only below one
 * minor unit (TND 0.001). Intl separates "TND" and the number with a no-break
 * space (U+00A0).
 */
describe('formatMoney (TND, en)', () => {
    const tnd = { currency: 'TND', locale: 'en' };
    const nbsp = ' ';

    it('formats the worked-example unit cost with 3 decimals', () => {
        expect(formatMoney('35.7540', tnd)).toBe(`TND${nbsp}35.754`);
    });

    it('formats zero', () => {
        expect(formatMoney('0.0000', tnd)).toBe(`TND${nbsp}0.000`);
    });

    it('formats negative amounts (the minus sign precedes the code)', () => {
        expect(formatMoney('-12.5000', tnd)).toBe(`-TND${nbsp}12.500`);
    });

    it('does not show a minus sign for negative values that round to zero', () => {
        expect(formatMoney('-0.0004', tnd)).toBe(`TND${nbsp}0.000`);
    });

    it('formats large values without losing precision', () => {
        expect(formatMoney('12345678901234.5678', tnd)).toBe(
            `TND${nbsp}12,345,678,901,234.568`,
        );
    });

    it('rounds half up at the 3rd decimal', () => {
        expect(formatMoney('0.0005', tnd)).toBe(`TND${nbsp}0.001`);
        expect(formatMoney('1.2345', tnd)).toBe(`TND${nbsp}1.235`);
        expect(formatMoney('1.2344', tnd)).toBe(`TND${nbsp}1.234`);
    });

    it('rounds sub-millime amounts to 3 decimals when not precise', () => {
        expect(formatMoney('0.0004', tnd)).toBe(`TND${nbsp}0.000`);
    });

    describe('precise', () => {
        const precise = { ...tnd, precise: true };

        it('shows 4 fraction digits below one millime (0.001)', () => {
            expect(formatMoney('0.0004', precise)).toBe(`TND${nbsp}0.0004`);
            expect(formatMoney('0.0009', precise)).toBe(`TND${nbsp}0.0009`);
            expect(formatMoney('-0.0004', precise)).toBe(`-TND${nbsp}0.0004`);
        });

        it('uses 3 decimals from one millime upwards', () => {
            expect(formatMoney('0.0040', precise)).toBe(`TND${nbsp}0.004`);
            expect(formatMoney('0.0010', precise)).toBe(`TND${nbsp}0.001`);
            expect(formatMoney('35.7540', precise)).toBe(`TND${nbsp}35.754`);
        });

        it('keeps zero at 3 decimals', () => {
            expect(formatMoney('0.0000', precise)).toBe(`TND${nbsp}0.000`);
        });
    });
});

describe('formatMoney (JPY, en: zero-decimal currency)', () => {
    const jpy = { currency: 'JPY', locale: 'en' };

    it('formats with no decimals, rounding half up', () => {
        expect(formatMoney('35.7540', jpy)).toBe('¥36');
        expect(formatMoney('35.4999', jpy)).toBe('¥35');
        expect(formatMoney('0.5000', jpy)).toBe('¥1');
    });

    it('shows 4 fraction digits below one yen when precise', () => {
        expect(formatMoney('0.5000', { ...jpy, precise: true })).toBe(
            '¥0.5000',
        );
        expect(formatMoney('0.0040', { ...jpy, precise: true })).toBe(
            '¥0.0040',
        );
    });

    it('uses no decimals from one yen upwards, even when precise', () => {
        expect(formatMoney('1.0000', { ...jpy, precise: true })).toBe('¥1');
        expect(formatMoney('0.0000', { ...jpy, precise: true })).toBe('¥0');
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

describe('formatDate', () => {
    it('formats a calendar date day first for plain en', () => {
        expect(formatDate('2026-10-07', 'en')).toBe('7 Oct 2026');
        expect(formatDate('2026-01-05', 'en')).toBe('5 Jan 2026');
        expect(formatDate('2026-12-31', 'en')).toBe('31 Dec 2026');
    });

    it('uses the short English month name for September, not en-GB "Sept"', () => {
        expect(formatDate('2026-09-07', 'en')).toBe('7 Sep 2026');
    });

    it('accepts ISO-8601 timestamps and shows the written calendar date', () => {
        expect(formatDate('2026-10-07T09:30:00Z', 'en')).toBe('7 Oct 2026');
        expect(formatDate('2026-10-07T09:30:00.000000Z', 'en')).toBe(
            '7 Oct 2026',
        );
        expect(formatDate('2026-10-07 09:30:00', 'en')).toBe('7 Oct 2026');
        expect(formatDate('2026-10-07T09:30', 'en')).toBe('7 Oct 2026');
    });

    it('never shifts the date across midnight, whatever the offset', () => {
        expect(formatDate('2026-10-07T00:00:00Z', 'en')).toBe('7 Oct 2026');
        expect(formatDate('2026-10-07T23:59:59Z', 'en')).toBe('7 Oct 2026');
        expect(formatDate('2026-10-07T23:30:00-05:00', 'en')).toBe(
            '7 Oct 2026',
        );
        expect(formatDate('2026-10-07T00:30:00+02:00', 'en')).toBe(
            '7 Oct 2026',
        );
    });

    it.each(['Pacific/Auckland', 'America/Los_Angeles'])(
        'is not affected by the process timezone (%s)',
        (timeZone) => {
            vi.stubEnv('TZ', timeZone);

            expect(formatDate('2026-10-07', 'en')).toBe('7 Oct 2026');
            expect(formatDate('2026-10-07T23:30:00Z', 'en')).toBe('7 Oct 2026');

            vi.unstubAllEnvs();
        },
    );

    it('uses the locale order for regional and other locales', () => {
        expect(formatDate('2026-10-07', 'en-US')).toBe('Oct 7, 2026');
        expect(formatDate('2026-10-07', 'fr')).toBe('7 oct. 2026');
        expect(formatDate('2026-10-07', 'de')).toBe('7. Okt. 2026');
    });

    it.each([
        null,
        undefined,
        '',
        'abc',
        '2026-13-01',
        '2026-02-30',
        '2026-10-7',
        '07/10/2026',
        '2026-10-07T',
        '2026-10-07T25',
        ' 2026-10-07',
    ])('renders %j as an em dash', (value) => {
        expect(formatDate(value, 'en')).toBe(EMPTY_VALUE);
    });

    it('accepts a leap day only in a leap year', () => {
        expect(formatDate('2028-02-29', 'en')).toBe('29 Feb 2028');
        expect(formatDate('2026-02-29', 'en')).toBe(EMPTY_VALUE);
    });
});

describe('formatCount', () => {
    it('groups thousands with the locale separator', () => {
        expect(formatCount(1250, 'en')).toBe('1,250');
        expect(formatCount(1250, 'de')).toBe('1.250');
        expect(formatCount(1000000, 'en')).toBe('1,000,000');
    });

    it('shows small numbers and zero as they are', () => {
        expect(formatCount(0, 'en')).toBe('0');
        expect(formatCount(112, 'en')).toBe('112');
    });

    it('formats negative whole numbers', () => {
        expect(formatCount(-5, 'en')).toBe('-5');
    });

    it.each([
        null,
        undefined,
        Number.NaN,
        Number.POSITIVE_INFINITY,
        Number.NEGATIVE_INFINITY,
        2.5,
    ])('renders %j as an em dash', (value) => {
        expect(formatCount(value, 'en')).toBe(EMPTY_VALUE);
    });
});
