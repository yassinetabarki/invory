import { usePage } from '@inertiajs/react';

export type AppSettings = {
    currency: string;
    locale: string;
};

export const DEFAULT_APP_SETTINGS: AppSettings = {
    currency: 'TND',
    locale: 'en',
};

/**
 * Currency and locale from the shared Inertia props (`app.currency`,
 * `app.locale`), falling back to TND (Tunisian dinar) / en when the server did
 * not send them. The server (`app.currency`) stays the source of truth.
 */
export function useAppSettings(): AppSettings {
    const app: Partial<AppSettings> | null | undefined = usePage().props.app;

    return {
        currency: app?.currency || DEFAULT_APP_SETTINGS.currency,
        locale: app?.locale || DEFAULT_APP_SETTINGS.locale,
    };
}
