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
 * True when a decimal string is below zero, judged from its digits: `"-5.7540"`
 * is negative, `"-0.0000"` and `"53.2460"` are not.
 */
export function isNegativeDecimal(value: string): boolean {
    return /^-\d*\.?\d*[1-9]/.test(value);
}
