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

/**
 * True when 0 < |value| < 0.01, decided by inspecting the digits (no maths):
 * the integer part and the first two fractional digits are zero, and a later
 * digit is non-zero. Zero itself is not "tiny", and 0.0100 is not below 0.01.
 */
function isBelowOneCent(value: string): boolean {
    const match = DECIMAL_PATTERN.exec(value);

    if (!match) {
        return false;
    }

    const integerPart = match[2];
    const fraction = match[3] ?? '';

    // 0.00xxxx: integer part and the first two fractional digits are zero.
    if (
        !/^0+$/.test(integerPart) ||
        !fraction.padEnd(2, '0').startsWith('00')
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
     * 0.01 are shown with 4 fraction digits instead of rounding to €0.00.
     * Everything else keeps 2 fraction digits.
     *
     * Note: the fraction digits are forced regardless of the currency's own
     * minor unit. TND's ISO minor unit is 3 decimals, so `TND 35.75` is shown
     * rather than `TND 35.754`; whether to follow the currency instead is a
     * pending product decision.
     */
    precise?: boolean;
};

/**
 * `"35.7540"` → `€35.75` (en, EUR) or `TND 35.75` (en, TND; the separator is
 * a no-break space). With `precise`, `"0.0040"` → `€0.0040` / `TND 0.0040`.
 * Null, undefined, empty or malformed input renders as an em dash.
 */
export function formatMoney(
    value: Maybe<Decimal>,
    { currency, locale, precise = false }: FormatMoneyOptions,
): string {
    if (value == null || !isNumericString(value)) {
        return EMPTY_VALUE;
    }

    const fractionDigits = precise && isBelowOneCent(value) ? 4 : 2;

    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency,
        minimumFractionDigits: fractionDigits,
        maximumFractionDigits: fractionDigits,
        // Hides the minus sign of values that round to zero ("-€0.00").
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
