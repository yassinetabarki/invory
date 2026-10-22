import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import ProductsEdit from '@/pages/products/edit';
import {
    answerMaterialOptions,
    categorySizesFixture,
    choreJacket,
    denimJacket,
    productStatuses,
} from '@/test/fixtures/products';
import {
    archived,
    referenceData,
    referenceProductCategories,
} from '@/test/fixtures/reference';
import {
    inertiaMock,
    resetInertiaMock,
    respondToHttp,
    setFormState,
    setPageProps,
} from '@/test/inertia';
import type { Product, ProductEditProps } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function props(product: Product = denimJacket): ProductEditProps {
    return {
        product,
        statuses: productStatuses,
        category_sizes: categorySizesFixture,
    };
}

describe('products/edit page', () => {
    beforeEach(() => {
        resetInertiaMock();
        respondToHttp(({ params }) => answerMaterialOptions(params));
        setPageProps(
            {},
            { url: '/products/1/edit', component: 'products/edit' },
        );
    });

    it('titles the page with the product name and pre-fills the form from it', () => {
        render(<ProductsEdit {...props()} />);

        expect(
            screen.getByRole('heading', {
                level: 1,
                name: 'Edit Denim Jacket',
            }),
        ).toBeInTheDocument();
        expect(screen.getByLabelText(/^SKU/)).toHaveValue('PRD-JKT-001');
        expect(screen.getByLabelText(/^Name/)).toHaveValue('Denim Jacket');
        expect(screen.getByLabelText(/^Labor cost/)).toHaveValue('12');
        expect(screen.getByLabelText(/^Overhead cost/)).toHaveValue('3.5');
        expect(screen.getByLabelText(/^Selling price/)).toHaveValue('89');
        expect(screen.getByText('4 materials')).toBeInTheDocument();
        expect(
            within(
                screen.getByRole('group', { name: /^Row 2:/ }),
            ).getByLabelText('Quantity per unit for Metal shank button 17mm'),
        ).toHaveValue('8');
    });

    it('shows the sizes of its category at once', () => {
        render(<ProductsEdit {...props()} />);

        expect(screen.getByText('Sizes: S, M, L, XL')).toBeInTheDocument();
        expect(
            screen.getByText('Sizes come with the category.'),
        ).toBeInTheDocument();
    });

    it('shows the saved rows with their units without asking the server for materials', () => {
        render(<ProductsEdit {...props()} />);

        expect(
            within(screen.getByRole('group', { name: /^Row 1:/ })).getByRole(
                'combobox',
                { name: /Denim 12oz Indigo/ },
            ),
        ).toHaveTextContent('MAT-DEN-012');
        expect(
            screen.getByLabelText(
                'Quantity per unit for Metal shank button 17mm',
            ).parentElement,
        ).toHaveTextContent('pc');
        expect(inertiaMock.http.requests).toHaveLength(0);
    });

    it('asks for the picked materials along with the first page when a picker opens', async () => {
        const user = userEvent.setup();

        render(<ProductsEdit {...props()} />);
        await user.click(screen.getByRole('button', { name: 'Add material' }));
        await screen.findAllByRole('option');

        expect(inertiaMock.http.requests).toHaveLength(1);
        expect(inertiaMock.http.requests[0].params).toEqual({
            limit: '20',
            include: ['4', '5', '8', '12'],
        });
    });

    it('shows the current photo', () => {
        render(<ProductsEdit {...props()} />);

        expect(
            screen.getByRole('img', { name: 'Current photo' }),
        ).toHaveAttribute('src', '/mock/photos/prd-jkt-001.svg');
    });

    it('cancels back to the product page', () => {
        render(<ProductsEdit {...props()} />);

        for (const link of screen.getAllByRole('link', { name: 'Cancel' })) {
            expect(link).toHaveAttribute('href', '/products/1');
        }
    });

    it('sends PUT to the update URL', async () => {
        const user = userEvent.setup();

        render(<ProductsEdit {...props()} />);

        await user.clear(screen.getByLabelText(/^Selling price/));
        await user.type(screen.getByLabelText(/^Selling price/), '95');
        await user.click(
            screen.getAllByRole('button', { name: 'Save product' })[0],
        );

        expect(inertiaMock.submissions).toHaveLength(1);
        expect(inertiaMock.submissions[0]).toMatchObject({
            action: '/products/1',
            method: 'put',
            data: { sku: 'PRD-JKT-001', selling_price: '95' },
        });
    });

    it('starts a different product from its own values (the form is keyed by id)', () => {
        const { rerender } = render(<ProductsEdit {...props()} />);

        rerender(<ProductsEdit {...props(choreJacket)} />);

        expect(screen.getByLabelText(/^SKU/)).toHaveValue('PRD-JKT-006');
        expect(screen.getByLabelText(/^Selling price/)).toHaveValue('');
        expect(screen.getByText('4 materials')).toBeInTheDocument();
    });

    it("shows the server's validation errors on the right row", () => {
        setFormState({
            errors: {
                'components.0.waste_percent': 'The waste must be below 100.',
            },
        });
        render(<ProductsEdit {...props()} />);

        expect(
            within(screen.getByRole('group', { name: /^Row 1:/ })).getByText(
                'The waste must be below 100.',
            ),
        ).toBeInTheDocument();
    });

    it('defines the Products > product > Edit breadcrumbs', () => {
        expect(ProductsEdit.layout(props()).breadcrumbs).toEqual([
            expect.objectContaining({
                title: 'Products',
                href: { url: '/products', method: 'get' },
            }),
            expect.objectContaining({
                title: 'Denim Jacket',
                href: { url: '/products/1', method: 'get' },
            }),
            expect.objectContaining({
                title: 'Edit',
                href: { url: '/products/1/edit', method: 'get' },
            }),
        ]);
    });
});

describe('products/edit page categories', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('keeps the archived category the product uses and hides other archived ones', async () => {
        const user = userEvent.setup();

        setPageProps({
            reference: referenceData({
                product_categories: [
                    archived(referenceProductCategories[0]),
                    archived(referenceProductCategories[1]),
                    referenceProductCategories[2],
                ],
            }),
        });
        render(<ProductsEdit {...props()} />); // Denim Jacket: 'apparel'

        expect(screen.getByLabelText(/^Category/)).toHaveTextContent(
            'Apparel (archived)',
        );

        await user.click(screen.getByLabelText(/^Category/));

        expect(
            within(screen.getByRole('listbox'))
                .getAllByRole('option')
                .map((option) => option.textContent),
        ).toEqual(['Apparel (archived)', 'Bag']);
    });
});
