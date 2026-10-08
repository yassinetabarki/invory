import { renderHook } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { useAppSettings } from '@/hooks/use-app-settings';
import { resetInertiaMock, setPageProps } from '@/test/inertia';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

describe('useAppSettings', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('reads currency and locale from the shared props', () => {
        setPageProps({ app: { currency: 'GBP', locale: 'en' } });

        const { result } = renderHook(() => useAppSettings());

        expect(result.current).toEqual({ currency: 'GBP', locale: 'en' });
    });

    it('falls back to TND / en when app is missing', () => {
        setPageProps({ app: undefined });

        const { result } = renderHook(() => useAppSettings());

        expect(result.current).toEqual({ currency: 'TND', locale: 'en' });
    });

    it('falls back per field when a value is empty', () => {
        setPageProps({ app: { currency: '', locale: 'fr' } });

        const { result } = renderHook(() => useAppSettings());

        expect(result.current).toEqual({ currency: 'TND', locale: 'fr' });
    });
});
