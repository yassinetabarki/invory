import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { ProductsTable } from '@/components/products/products-table';
import {
    allProductItems,
    belowCostItem,
    choreJacketItem,
    denimJacketItem,
    sneakerItem,
    unfinishedItem,
} from '@/test/fixtures/products';
import { resetInertiaMock } from '@/test/inertia';
import type { ProductListItem } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const hrefs = {
    show: (product: ProductListItem) => `/products/${product.id}`,
    edit: (product: ProductListItem) => `/products/${product.id}/edit`,
    material: (material: { id: number }) => `/materials/${material.id}`,
};

function renderTable(products: ProductListItem[] = allProductItems) {
    return render(<ProductsTable products={products} hrefs={hrefs} />);
}

function table(): HTMLElement {
    return screen.getByRole('table', { name: 'Products' });
}

function row(name: RegExp): HTMLElement {
    return within(table()).getByRole('row', { name });
}

describe('<ProductsTable>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    describe('the table (md and up)', () => {
        it('has the columns of the mockup, with the new Price · margin column', () => {
            renderTable();

            expect(
                within(table())
                    .getAllByRole('columnheader')
                    .map((header) => header.textContent),
            ).toEqual([
                'Product',
                'Category',
                'Status',
                'Unit cost',
                'Price · margin',
                'Can produce',
                'Bottleneck',
                'Sold (30 d)',
                'Actions',
            ]);
        });

        it('keeps the order it is given (the server sorts by name)', () => {
            renderTable();

            const names = within(table())
                .getAllByRole('row')
                .slice(1)
                .map((candidate) => within(candidate).getAllByRole('link')[0])
                .map((link) => link.textContent);

            expect(names).toEqual([
                'Canvas Chore Jacket',
                'Canvas Low Sneaker',
                'Classic White T-shirt',
                'Denim Jacket',
                'Denim Jeans 5-pocket',
                'Leather Belt',
                'Leather Tote',
            ]);
        });

        it('shows a product with its SKU, materials count, cost, price, margin, capacity and sales', () => {
            renderTable();

            const jacket = within(row(/Denim Jacket/));

            expect(
                jacket.getByRole('link', { name: 'Denim Jacket' }),
            ).toHaveAttribute('href', '/products/1');
            expect(jacket.getByText('PRD-JKT-001 · 4 materials')).toHaveClass(
                'font-mono',
            );
            expect(jacket.getByText('Apparel')).toBeInTheDocument();
            expect(jacket.getByText('Active')).toBeInTheDocument();
            expect(jacket.getByText('€35.75')).toBeInTheDocument();
            expect(jacket.getByText('€89.00')).toBeInTheDocument();
            expect(jacket.getByText('59.83%')).toBeInTheDocument();
            expect(jacket.getByText('112 units')).toBeInTheDocument();
            expect(jacket.getByText('38')).toBeInTheDocument();
        });

        it('links the bottleneck to its material', () => {
            renderTable();

            expect(
                within(row(/Denim Jacket/)).getByRole('link', {
                    name: 'Metal shank button 17mm',
                }),
            ).toHaveAttribute('href', '/materials/5');
            expect(
                within(row(/Canvas Low Sneaker/)).getByRole('link', {
                    name: 'Rubber cupsole 42',
                }),
            ).toHaveAttribute('href', '/materials/9');
        });

        it('says "Can\'t produce" in words for a product at zero', () => {
            renderTable([sneakerItem]);

            expect(
                within(row(/Canvas Low Sneaker/)).getByText("Can't produce"),
            ).toBeInTheDocument();
        });

        it('shows a draft without a price: its status, no price (an em dash) and no sales', () => {
            renderTable([choreJacketItem]);

            const draft = within(row(/Canvas Chore Jacket/));

            expect(draft.getByText('Draft')).toBeInTheDocument();
            // Price · margin and Sold (30 d) both show an em dash.
            expect(draft.getAllByText('—')).toHaveLength(2);
            expect(draft.getByText('No selling price')).toHaveClass('sr-only');
            expect(draft.getByText('129 units')).toBeInTheDocument();
        });

        it('shows a product without a composition: no capacity and no bottleneck', () => {
            renderTable([unfinishedItem]);

            const unfinished = within(row(/Unfinished Parka/));

            expect(
                unfinished.getByText('PRD-NEW-008 · 0 materials'),
            ).toBeInTheDocument();
            expect(unfinished.getByText('No composition')).toBeInTheDocument();
            // Bottleneck, no price and no sales: three dashes, and no material link.
            expect(unfinished.getAllByText('—')).toHaveLength(3);
            expect(
                within(row(/Unfinished Parka/)).queryByRole('link', {
                    name: /Metal|Cotton|Rubber/,
                }),
            ).not.toBeInTheDocument();
        });

        it('says "1 material" for a single material', () => {
            renderTable([{ ...denimJacketItem, components_count: 1 }]);

            expect(
                within(row(/Denim Jacket/)).getByText(
                    'PRD-JKT-001 · 1 material',
                ),
            ).toBeInTheDocument();
        });

        it('shows a negative margin with its minus sign', () => {
            renderTable([belowCostItem]);

            const clearance = within(row(/Clearance Jacket/));

            expect(clearance.getByText('€30.00')).toBeInTheDocument();
            expect(clearance.getByText('-19.18%')).toHaveClass(
                'text-danger-foreground',
            );
        });

        it('gives each row labelled View and Edit links', () => {
            renderTable();

            const jacket = within(row(/Denim Jacket/));

            expect(
                jacket.getByRole('link', { name: 'View Denim Jacket' }),
            ).toHaveAttribute('href', '/products/1');
            expect(
                jacket.getByRole('link', { name: 'Edit Denim Jacket' }),
            ).toHaveAttribute('href', '/products/1/edit');
        });
    });

    describe('the stacked cards (below md)', () => {
        function cards(): HTMLElement {
            return screen.getByRole('list', { name: 'Products' });
        }

        function card(name: string): HTMLElement {
            const found = within(cards())
                .getAllByRole('listitem')
                .find((item) => item.textContent?.includes(name));

            if (!found) {
                throw new Error(`Expected a card for ${name}.`);
            }

            return found;
        }

        it('has one card per product', () => {
            renderTable();

            expect(within(cards()).getAllByRole('listitem')).toHaveLength(7);
        });

        it('shows the same figures, each under a label', () => {
            renderTable();

            const jacket = within(card('Denim Jacket'));

            expect(
                jacket.getByRole('link', { name: 'Denim Jacket' }),
            ).toHaveAttribute('href', '/products/1');
            expect(
                jacket.getByText('PRD-JKT-001 · 4 materials'),
            ).toBeInTheDocument();
            expect(jacket.getByText('Active')).toBeInTheDocument();
            expect(jacket.getByText('Can produce')).toBeInTheDocument();
            expect(jacket.getByText('112 units')).toBeInTheDocument();
            expect(jacket.getByText('Category')).toBeInTheDocument();
            expect(jacket.getByText('Unit cost')).toBeInTheDocument();
            expect(jacket.getByText('€35.75')).toBeInTheDocument();
            expect(jacket.getByText('Price · margin')).toBeInTheDocument();
            expect(jacket.getByText('€89.00')).toBeInTheDocument();
            expect(jacket.getByText('59.83%')).toBeInTheDocument();
            expect(jacket.getByText('Sold (30 d)')).toBeInTheDocument();
            expect(jacket.getByText('38')).toBeInTheDocument();
            expect(jacket.getByText('Bottleneck')).toBeInTheDocument();
            expect(
                jacket.getByRole('link', { name: 'Metal shank button 17mm' }),
            ).toHaveAttribute('href', '/materials/5');
        });

        it('has View and Edit buttons with accessible names', () => {
            renderTable();

            const jacket = within(card('Denim Jacket'));

            expect(
                jacket.getByRole('link', { name: 'View Denim Jacket' }),
            ).toHaveAttribute('href', '/products/1');
            expect(
                jacket.getByRole('link', { name: 'Edit Denim Jacket' }),
            ).toHaveAttribute('href', '/products/1/edit');
        });

        it('shows the draft and the zero-capacity states as text', () => {
            renderTable();

            expect(
                within(card('Canvas Chore Jacket')).getByText('Draft'),
            ).toBeInTheDocument();
            expect(
                within(card('Canvas Low Sneaker')).getByText("Can't produce"),
            ).toBeInTheDocument();
        });
    });
});
