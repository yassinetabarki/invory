/**
 * String-only helpers for decimals that arrive from the server as strings.
 * Nothing here parses a number or does arithmetic: values are inspected and
 * rewritten as text.
 */

const DECIMAL_PATTERN = /^-?\d+(\.\d+)?$/;

/**
 * Drops insignificant trailing zeros, so a form input shows what a person
 * would type: `"12.0000"` → `"12"`, `"1.8000"` → `"1.8"`, `"0.0040"` →
 * `"0.004"`, `"10.00"` → `"10"`. Anything that is not a plain decimal string
 * is returned untouched.
 */
export function trimDecimal(value: string): string {
    if (!DECIMAL_PATTERN.test(value) || !value.includes('.')) {
        return value;
    }

    return value.replace(/0+$/, '').replace(/\.$/, '');
}

/**
 * True when a quantity cell holds something other than nothing or zero, judged
 * from its text (INV-16: an empty cell and a 0 are both "no cell"). Anything
 * that is not a number stays filled, so the server can refuse it under the cell.
 */
export function isFilledQuantity(value: string): boolean {
    const text = value.trim();

    return text !== '' && !/^(0+\.?0*|\.0+)$/.test(text);
}

/**
 * True when a decimal string is below zero, judged from its digits: `"-5.7540"`
 * is negative, `"-0.0000"` and `"53.2460"` are not.
 */
export function isNegativeDecimal(value: string): boolean {
    return /^-\d*\.?\d*[1-9]/.test(value);
}

/** A decimal string split for comparing: no leading or trailing zeros, "-0" is not negative. */
function decimalParts(value: string) {
    const match = /^(-?)(\d*)(?:\.(\d*))?$/.exec(value.trim());

    if (!match || (match[2] === '' && (match[3] ?? '') === '')) {
        return null;
    }

    const whole = match[2].replace(/^0+/, '');
    const fraction = (match[3] ?? '').replace(/0+$/, '');

    return {
        negative: match[1] === '-' && (whole !== '' || fraction !== ''),
        whole,
        fraction,
    };
}

/**
 * Compares two decimal strings by their digits, never as numbers: sign, then
 * the length and digits of the whole part, then the fraction with the shorter
 * one padded by zeros. `"1.6"` equals `"1.6000"` and `"10"` is above `"9.9999"`.
 * Text that is not a decimal falls back to comparing the text.
 */
export function compareDecimals(a: string, b: string): -1 | 0 | 1 {
    const left = decimalParts(a);
    const right = decimalParts(b);

    if (!left || !right) {
        return a < b ? -1 : a > b ? 1 : 0;
    }

    if (left.negative !== right.negative) {
        return left.negative ? -1 : 1;
    }

    const width = Math.max(left.fraction.length, right.fraction.length);
    const [x, y] = [
        left.whole.padStart(
            Math.max(left.whole.length, right.whole.length),
            '0',
        ) + left.fraction.padEnd(width, '0'),
        right.whole.padStart(
            Math.max(left.whole.length, right.whole.length),
            '0',
        ) + right.fraction.padEnd(width, '0'),
    ];
    const order = x < y ? -1 : x > y ? 1 : 0;

    return left.negative ? (-order as -1 | 0 | 1) : order;
}
