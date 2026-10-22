import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import ProductsCreate from '@/pages/products/create';
import {
    answerMaterialOptions,
    categorySizesFixture,
    materialOptions,
    productCategories,
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
import type { ProductCreateProps } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const props: ProductCreateProps = {
    statuses: productStatuses,
    category_sizes: categorySizesFixture,
};

describe('products/create page', () => {
    beforeEach(() => {
        resetInertiaMock();
        respondToHttp(({ params }) => answerMaterialOptions(params));
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

    it('offers the categories the shared reference sent', async () => {
        const user = userEvent.setup();

        render(<ProductsCreate {...props} />);

        await user.click(screen.getByLabelText(/^Category/));

        expect(
            within(screen.getByRole('listbox'))
                .getAllByRole('option')
                .map((option) => option.textContent),
        ).toEqual(productCategories.map((option) => option.label));
    });

    it('shows the sizes of the chosen category under "Category", and none before', async () => {
        const user = userEvent.setup();

        render(<ProductsCreate {...props} />);

        const category = screen.getByLabelText(/^Category/);

        expect(screen.queryByText(/^Sizes/)).not.toBeInTheDocument();
        expect(screen.queryByText('One size')).not.toBeInTheDocument();
        expect(
            screen.queryByText('Sizes come with the category.'),
        ).not.toBeInTheDocument();

        await user.click(category);
        await user.click(screen.getByRole('option', { name: 'Apparel' }));

        expect(screen.getByText('Sizes: S, M, L, XL')).toBeInTheDocument();
        expect(
            screen.getByText('Sizes come with the category.'),
        ).toBeInTheDocument();
        expect(category).toHaveAccessibleDescription(
            expect.stringContaining('Sizes: S, M, L, XL'),
        );

        await user.click(category);
        await user.click(screen.getByRole('option', { name: 'Footwear' }));

        expect(screen.getByText('Sizes: 36 to 46')).toBeInTheDocument();
        expect(
            screen.queryByText('Sizes: S, M, L, XL'),
        ).not.toBeInTheDocument();

        await user.click(category);
        await user.click(screen.getByRole('option', { name: 'Bag' }));

        expect(screen.getByText('One size')).toBeInTheDocument();
        expect(category).toHaveAccessibleDescription(
            expect.stringContaining('One size'),
        );
    });

    it('asks the server for materials only when a picker opens', async () => {
        const user = userEvent.setup();

        render(<ProductsCreate {...props} />);

        expect(inertiaMock.http.requests).toHaveLength(0);

        await user.click(screen.getByRole('button', { name: 'Add material' }));

        expect(await screen.findAllByRole('option')).toHaveLength(
            materialOptions.length,
        );
        expect(inertiaMock.http.requests).toHaveLength(1);
        expect(inertiaMock.http.requests[0]).toMatchObject({
            method: 'get',
            path: '/materials/options',
            params: { limit: '20' },
        });
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

describe('products/create page categories', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('lists the active product categories from the shared reference prop', async () => {
        const user = userEvent.setup();

        setPageProps({
            reference: referenceData({
                product_categories: [
                    referenceProductCategories[0],
                    archived(referenceProductCategories[1]),
                    {
                        id: 70,
                        code: 'hat',
                        name: 'Hat',
                        position: 7,
                        archived: false,
                    },
                ],
            }),
        });
        render(<ProductsCreate {...props} />);

        await user.click(screen.getByLabelText(/^Category/));

        expect(
            within(screen.getByRole('listbox'))
                .getAllByRole('option')
                .map((option) => option.textContent),
        ).toEqual(['Apparel', 'Hat']);
    });
});
