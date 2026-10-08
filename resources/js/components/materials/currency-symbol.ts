/**
 * The currency's own symbol or code, for the prefix of a money input
 * (`EUR` → `€`, `TND` → `TND` in the `en` locale). Taken from Intl, never
 * hard-coded, so it follows the installation currency (`app.currency`).
 */
export function currencySymbol(currency: string, locale: string): string {
    const parts = new Intl.NumberFormat(locale, {
        style: 'currency',
        currency,
    }).formatToParts(0);

    return parts.find((part) => part.type === 'currency')?.value ?? currency;
}
