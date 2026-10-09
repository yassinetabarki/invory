import { renderHook } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { useReference } from '@/hooks/use-reference';
import { referenceData } from '@/test/fixtures/reference';
import { resetInertiaMock, setPageProps } from '@/test/inertia';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

describe('useReference', () => {
    beforeEach(() => resetInertiaMock());

    it('returns the shared reference prop', () => {
        const { result } = renderHook(() => useReference());

        expect(result.current.units).toHaveLength(13);
        expect(result.current.material_categories).toHaveLength(12);
        expect(result.current.product_categories).toHaveLength(6);
    });

    it('follows the page when the reference prop changes', () => {
        const { result } = renderHook(() => useReference());

        setPageProps({
            reference: referenceData({ product_categories: [] }),
        });

        expect(result.current.product_categories).toEqual([]);
    });

    it('returns empty lists when the server sent none (guests)', () => {
        const { result } = renderHook(() => useReference());

        setPageProps({ reference: undefined });

        expect(result.current.units).toEqual([]);
        expect(result.current.material_categories).toEqual([]);
        expect(result.current.product_categories).toEqual([]);
    });
});
