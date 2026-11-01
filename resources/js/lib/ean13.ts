/**
 * EAN-13 bars from the 13 digits the server sends. Digit work only: no money,
 * no quantity. Each digit is a 7-module pattern ("1" bar, "0" space); the
 * first digit is not drawn, it picks which of L or G the left six use.
 */
const L = [
    '0001101',
    '0011001',
    '0010011',
    '0111101',
    '0100011',
    '0110001',
    '0101111',
    '0111011',
    '0110111',
    '0001011',
];
const G = [
    '0100111',
    '0110011',
    '0011011',
    '0100001',
    '0011101',
    '0111001',
    '0000101',
    '0010001',
    '0001001',
    '0010111',
];
const R = [
    '1110010',
    '1100110',
    '1101100',
    '1000010',
    '1011100',
    '1001110',
    '1010000',
    '1000100',
    '1001000',
    '1110100',
];
/** Which table (L or G) each of the six left digits uses, by the first digit. */
const PARITY = [
    'LLLLLL',
    'LLGLGG',
    'LLGGLG',
    'LLGGGL',
    'LGLLGG',
    'LGGLLG',
    'LGGGLL',
    'LGLGLG',
    'LGLGGL',
    'LGGLGL',
];

/** The check digit (13th) of the first 12 digits, as a digit. */
function checkDigit(first12: string): number {
    let sum = 0;

    for (let i = 0; i < 12; i++) {
        sum += Number(first12[i]) * (i % 2 === 0 ? 1 : 3);
    }

    return (10 - (sum % 10)) % 10;
}

/**
 * The 95 modules of an EAN-13 number as a string of "1" (bar) and "0"
 * (space): start guard, six left digits, middle guard, six right digits, end
 * guard. `null` unless `digits` is exactly 13 ASCII digits with the right
 * check digit.
 */
export function ean13Modules(digits: string): string | null {
    if (!/^[0-9]{13}$/.test(digits)) {
        return null;
    }

    if (checkDigit(digits.slice(0, 12)) !== Number(digits[12])) {
        return null;
    }

    const parity = PARITY[Number(digits[0])];
    let modules = '101';

    for (let i = 1; i <= 6; i++) {
        const table = parity[i - 1] === 'L' ? L : G;

        modules += table[Number(digits[i])];
    }

    modules += '01010';

    for (let i = 7; i <= 12; i++) {
        modules += R[Number(digits[i])];
    }

    return `${modules}101`;
}
