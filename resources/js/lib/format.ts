import type { Decimal } from '@/types/domain';

/**
 * Display formatting for server decimals.
 *
 * Decimals arrive as strings (`"35.7540"`) and are only ever *formatted* here:
 * no Number()/parseFloat/arithmetic. `Intl.NumberFormat#format` accepts numeric
 * strings and formats them exactly (no float conversion) on modern engines.
 */

/** Shown when there is no value to display (null, undefined, empty or malformed). */
export const EMPTY_VALUE = '—';

type Maybe<T> = T | null | undefined;

const DECIMAL_PATTERN = /^(-?)(\d+)(?:\.(\d+))?$/;

type NumericString = `${number}`;

function isNumericString(value: string): value is NumericString {
    return DECIMAL_PATTERN.test(value);
}

/** Fraction digits shown for sub-minor-unit amounts when `precise` is set. */
const PRECISE_FRACTION_DIGITS = 4;

/**
 * The currency's own display digits, from Intl (ISO 4217 minor unit):
 * TND 3, EUR 2, JPY 0. Min and max agree for every currency, so one number
 * drives both.
 */
function currencyFractionDigits(currency: string, locale: string): number {
    return (
        new Intl.NumberFormat(locale, {
            style: 'currency',
            currency,
        }).resolvedOptions().maximumFractionDigits ?? 2
    );
}

/**
 * True when 0 < |value| < one minor unit (10^-digits), decided by inspecting
 * the digits (no maths): the integer part and the first `digits` fractional
 * digits are zero, and a later digit is non-zero. Zero itself is not "tiny",
 * and exactly one minor unit (EUR 0.0100, TND 0.0010) is not below it.
 */
function isBelowMinorUnit(value: string, digits: number): boolean {
    const match = DECIMAL_PATTERN.exec(value);

    if (!match) {
        return false;
    }

    const integerPart = match[2];
    const fraction = match[3] ?? '';

    // 0.00xxxx: integer part and the first `digits` fractional digits are zero.
    if (
        !/^0+$/.test(integerPart) ||
        !fraction.padEnd(digits, '0').startsWith('0'.repeat(digits))
    ) {
        return false;
    }

    return /[1-9]/.test(fraction);
}

export type FormatMoneyOptions = {
    /** ISO 4217 code, e.g. `TND` (company default) or `EUR`. */
    currency: string;
    /** BCP 47 locale, e.g. `en`. */
    locale: string;
    /**
     * For per-unit costs (e.g. thread at 0.0040 per m). Non-zero amounts below
     * one minor unit of the currency (TND 0.001, EUR 0.01, JPY 1) are shown
     * with 4 fraction digits instead of rounding to nothing. Everything else
     * uses the currency's own digits.
     */
    precise?: boolean;
};

/**
 * Money is shown with the currency's own ISO minor-unit digits (decided
 * 2026-10-08, backlog F-10): `"35.7540"` → `TND 35.754` (en, TND; the
 * separator is a no-break space), `€35.75` (EUR) or `¥36` (JPY). With
 * `precise`, amounts below one minor unit keep 4 digits: EUR `"0.0040"` →
 * `€0.0040`, TND `"0.0004"` → `TND 0.0004`, while TND `"0.0040"` → `TND 0.004`.
 * Null, undefined, empty or malformed input renders as an em dash.
 */
export function formatMoney(
    value: Maybe<Decimal>,
    { currency, locale, precise = false }: FormatMoneyOptions,
): string {
    if (value == null || !isNumericString(value)) {
        return EMPTY_VALUE;
    }

    const currencyDigits = currencyFractionDigits(currency, locale);
    const fractionDigits =
        precise && isBelowMinorUnit(value, currencyDigits)
            ? PRECISE_FRACTION_DIGITS
            : currencyDigits;

    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency,
        minimumFractionDigits: fractionDigits,
        maximumFractionDigits: fractionDigits,
        // Hides the minus sign of values that round to zero ("-TND 0.000").
        signDisplay: 'negative',
    }).format(value);
}

export type FormatQuantityOptions = {
    locale: string;
    /** Short unit code shown after the number, e.g. `m`, `pc`. */
    unit?: Maybe<string>;
};

/**
 * `"1.9800", { unit: 'm' }` → `1.98 m`. Up to 4 fraction digits, trailing
 * zeros dropped. Null, undefined, empty or malformed input renders as an em dash.
 */
export function formatQuantity(
    value: Maybe<Decimal>,
    { locale, unit }: FormatQuantityOptions,
): string {
    if (value == null || !isNumericString(value)) {
        return EMPTY_VALUE;
    }

    const formatted = new Intl.NumberFormat(locale, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 4,
        signDisplay: 'negative',
    }).format(value);

    return unit ? `${formatted} ${unit}` : formatted;
}

/**
 * Percentages arrive as scale-2 strings that are already percent points
 * (`"10.00"` means 10 %), so no division by 100 is needed or allowed.
 * `"10.00"` → `10%`, `"7.50"` → `7.5%`. Uses Intl's `percent` unit, which
 * formats the number as-is and applies the locale's percent sign.
 */
export function formatPercent(value: Maybe<Decimal>, locale: string): string {
    if (value == null || !isNumericString(value)) {
        return EMPTY_VALUE;
    }

    return new Intl.NumberFormat(locale, {
        style: 'unit',
        unit: 'percent',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
        signDisplay: 'negative',
    }).format(value);
}

/** `YYYY-MM-DD`, optionally followed by an ISO-8601 time (and offset). */
const DATE_PATTERN =
    /^(\d{4})-(\d{2})-(\d{2})(?:[T ]\d{2}:\d{2}(?::\d{2}(?:\.\d+)?)?(?:Z|[+-]\d{2}(?::?\d{2})?)?)?$/;

const SHORT_DATE: Intl.DateTimeFormatOptions = {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    // The date is already a calendar date: never move it across midnight.
    timeZone: 'UTC',
};

/**
 * A calendar date for display: `"2026-10-07"` and `"2026-10-07T09:30:00Z"`
 * (any ISO-8601 timestamp) → `7 Oct 2026`.
 *
 * The date is the one *written* in the value, formatted as UTC, so it never
 * shifts with the browser's or the server's timezone. Plain `en` reads day
 * first (`7 Oct 2026`, as in the approved mockup); every other locale uses
 * its own order (`en-US` → `Oct 7, 2026`, `fr` → `7 oct. 2026`). Null,
 * undefined, malformed values and dates that do not exist (`2026-02-30`)
 * render as an em dash.
 */
export function formatDate(value: Maybe<string>, locale: string): string {
    const match = value == null ? null : DATE_PATTERN.exec(value);

    if (!match) {
        return EMPTY_VALUE;
    }

    const year = Number(match[1]);
    const month = Number(match[2]);
    const day = Number(match[3]);
    const date = new Date(Date.UTC(year, month - 1, day));

    if (
        date.getUTCFullYear() !== year ||
        date.getUTCMonth() !== month - 1 ||
        date.getUTCDate() !== day
    ) {
        return EMPTY_VALUE;
    }

    const formatter = new Intl.DateTimeFormat(locale, SHORT_DATE);

    if (formatter.resolvedOptions().locale !== 'en') {
        return formatter.format(date);
    }

    const parts = formatter.formatToParts(date);
    const part = (type: Intl.DateTimeFormatPartTypes) =>
        parts.find((p) => p.type === type)?.value ?? '';

    return `${part('day')} ${part('month')} ${part('year')}`;
}

/**
 * A whole-number count with the locale's grouping: `1250` → `1,250`
 * (en), `1.250` (de). Null, undefined, NaN, infinite and non-integer values
 * render as an em dash: counts are whole numbers, and a fraction means the
 * wrong field was passed.
 */
export function formatCount(value: Maybe<number>, locale: string): string {
    if (value == null || !Number.isSafeInteger(value)) {
        return EMPTY_VALUE;
    }

    return new Intl.NumberFormat(locale, { maximumFractionDigits: 0 }).format(
        value,
    );
}
