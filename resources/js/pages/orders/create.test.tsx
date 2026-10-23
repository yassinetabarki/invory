import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import OrdersCreate from '@/pages/orders/create';
import { formProduct, sizeLists } from '@/test/fixtures';
import { resetInertiaMock } from '@/test/inertia';
import type { OrderCreateProps } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const props: OrderCreateProps = {
    products: [
        formProduct({
            id: 1,
            name: 'Denim Jacket',
            sku: 'PRD-JKT-001',
            status: 'active',
            deleted: false,
        }),
    ],
    size_lists: sizeLists,
};

describe('orders/create page', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('opens "New order" with Details, Products and one empty line', () => {
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
            screen.getByRole('combobox', { name: 'Product' }),
        ).toHaveTextContent('Choose a product…');
        expect(screen.getByLabelText('Quantity')).toHaveValue('');
        expect(screen.getByRole('link', { name: 'Cancel' })).toHaveAttribute(
            'href',
            '/orders',
        );
    });
});
