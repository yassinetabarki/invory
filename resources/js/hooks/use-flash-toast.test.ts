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

    it('opens "Print ticket B-0155" in a new tab after a split (INV-15)', () => {
        const open = vi.spyOn(window, 'open').mockReturnValue({} as Window);
        renderHook(() => useFlashToast());
        flash({
            type: 'success',
            message:
                '4 units of Denim Jacket · Indigo M moved to Finishing. Batch B-0149 was split: the units moved are on B-0155.',
            action: {
                label: 'Print ticket B-0155',
                href: '/progress/tickets?batches%5B0%5D=8',
                new_tab: true,
            },
        });

        const [, options] = toast.success.mock.calls[0];
        expect(options.action.label).toBe('Print ticket B-0155');

        options.action.onClick();
        expect(open).toHaveBeenCalledWith(
            '/progress/tickets?batches%5B0%5D=8',
            '_blank',
        );
        expect(inertiaMock.router.visit).not.toHaveBeenCalled();
        open.mockRestore();
    });
});
