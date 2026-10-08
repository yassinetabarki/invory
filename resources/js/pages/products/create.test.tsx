import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import ProductsCreate from '@/pages/products/create';
import {
    materialOptions,
    productCategories,
    productStatuses,
} from '@/test/fixtures/products';
import {
    inertiaMock,
    resetInertiaMock,
    setFormState,
    setPageProps,
} from '@/test/inertia';
import type { ProductCreateProps } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const props: ProductCreateProps = {
    categories: productCategories,
    statuses: productStatuses,
    materials: materialOptions,
};

describe('products/create page', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps(
            {},
            { url: '/products/create', component: 'products/create' },
        );
    });

    it('renders an empty product form titled "Add product"', () => {
        render(<ProductsCreate {...props} />);

        expect(
            screen.getByRole('heading', { level: 1, name: 'Add product' }),
        ).toBeInTheDocument();
        expect(screen.getByLabelText(/^SKU/)).toHaveValue('');
        expect(screen.getByLabelText(/^Name/)).toHaveValue('');
        expect(screen.getByText('0 materials')).toBeInTheDocument();
    });

    it('offers the categories, statuses and materials the server sent', async () => {
        const user = userEvent.setup();

        render(<ProductsCreate {...props} />);

        await user.click(screen.getByLabelText(/^Category/));

        expect(
            within(screen.getByRole('listbox'))
                .getAllByRole('option')
                .map((option) => option.textContent),
        ).toEqual(productCategories.map((option) => option.label));

        await user.keyboard('{Escape}');
        await user.click(screen.getByRole('button', { name: 'Add material' }));

        expect(screen.getAllByRole('option')).toHaveLength(
            materialOptions.length,
        );
    });

    it('cancels back to the products list', () => {
        render(<ProductsCreate {...props} />);

        for (const link of screen.getAllByRole('link', { name: 'Cancel' })) {
            expect(link).toHaveAttribute('href', '/products');
        }
    });

    it('posts to the store URL', async () => {
        const user = userEvent.setup();

        render(<ProductsCreate {...props} />);

        await user.type(screen.getByLabelText(/^SKU/), 'PRD-NEW-100');
        await user.type(screen.getByLabelText(/^Name/), 'Overshirt');
        await user.click(
            screen.getAllByRole('button', { name: 'Save product' })[0],
        );

        expect(inertiaMock.submissions).toHaveLength(1);
        expect(inertiaMock.submissions[0]).toMatchObject({
            action: '/products',
            method: 'post',
            data: { sku: 'PRD-NEW-100', name: 'Overshirt', components: [] },
        });
    });

    it("shows the server's validation errors", () => {
        setFormState({ errors: { sku: 'The sku has already been taken.' } });
        render(<ProductsCreate {...props} />);

        expect(
            screen.getByText('The sku has already been taken.'),
        ).toBeInTheDocument();
    });

    it('defines the Products > Add product breadcrumbs', () => {
        expect(ProductsCreate.layout.breadcrumbs).toEqual([
            expect.objectContaining({
                title: 'Products',
                href: { url: '/products', method: 'get' },
            }),
            expect.objectContaining({
                title: 'Add product',
                href: { url: '/products/create', method: 'get' },
            }),
        ]);
    });
});
