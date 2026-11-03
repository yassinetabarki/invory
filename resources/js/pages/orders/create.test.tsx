import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import OrdersCreate from '@/pages/orders/create';
import { resetInertiaMock } from '@/test/inertia';
import type { OrderCreateProps } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const props: OrderCreateProps = {
    cities: [],
    client_sources: [],
    client_preset: null,
};

describe('orders/create page', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('opens "New order" with Details, and Products with its search and no product yet (INV-13)', () => {
        render(<OrdersCreate {...props} />);

        expect(
            screen.getByRole('heading', { level: 1, name: 'New order' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('heading', { level: 2, name: 'Details' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('heading', { level: 2, name: 'Products' }),
        ).toBeInTheDocument();
        expect(screen.getByLabelText('Order date')).toHaveValue('2026-10-08');
        expect(
            screen.getByRole('searchbox', { name: 'Search products' }),
        ).toHaveValue('');
        expect(
            screen.getByText(
                'Search what the client wants, check what is in stock, then add it.',
            ),
        ).toBeVisible();
        expect(screen.getByText('No products on this order yet')).toBeVisible();
        expect(
            screen.getByText(
                'Search for a product above and add what the client ordered.',
            ),
        ).toBeVisible();
        expect(screen.getByRole('link', { name: 'Cancel' })).toHaveAttribute(
            'href',
            '/orders',
        );
    });
});
