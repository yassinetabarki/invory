import { renderHook } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { inertiaMock, resetInertiaMock } from '@/test/inertia';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const toast = vi.hoisted(() => ({
    success: vi.fn(),
    info: vi.fn(),
    warning: vi.fn(),
    error: vi.fn(),
}));
vi.mock('sonner', () => ({ toast }));

function flash(data: unknown) {
    const calls = inertiaMock.router.on.mock.calls as unknown as [
        string,
        (event: CustomEvent) => void,
    ][];
    const listener = calls[0][1];
    listener(new CustomEvent('flash', { detail: { flash: { toast: data } } }));
}

describe('useFlashToast', () => {
    beforeEach(() => {
        resetInertiaMock();
        vi.clearAllMocks();
    });

    it("shows the server's toast", () => {
        renderHook(() => useFlashToast());
        flash({ type: 'success', message: 'Phase added.' });

        expect(toast.success).toHaveBeenCalledWith('Phase added.', undefined);
    });

    it('adds the toast\'s link as its action: "See the restock" after Put in stock (INV-5)', () => {
        renderHook(() => useFlashToast());
        flash({
            type: 'success',
            message: '4 units of Denim Jacket · Indigo M put in stock.',
            action: { label: 'See the restock', href: '/restocks?restock=7' },
        });

        const [, options] = toast.success.mock.calls[0];
        expect(options.action.label).toBe('See the restock');

        options.action.onClick();
        expect(inertiaMock.router.visit).toHaveBeenCalledWith(
            '/restocks?restock=7',
        );
    });
});
