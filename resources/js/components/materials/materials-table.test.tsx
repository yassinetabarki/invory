import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { MaterialsTable } from '@/components/materials/materials-table';
import { resetInertiaMock, setPageProps } from '@/test/inertia';
import type { Material } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const denim: Material = {
    id: 1,
    sku: 'FAB-001',
    name: 'Denim 12oz Indigo',
    category: 'fabric',
    category_label: 'Fabric',
    unit: 'm',
    unit_label: 'Metre (m)',
    unit_cost: '8.5000',
    quantity_on_hand: '250.0000',
    reorder_level: '50.0000',
    stock_value: '2125.0000',
    stock_status: 'in_stock',
    supplier_name: null,
    supplier_reference: null,
    color: null,
    composition: null,
    notes: null,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
};

const thread: Material = {
    ...denim,
    id: 2,
    sku: 'THR-001',
    name: 'Polyester thread',
    category: 'thread',
    category_label: 'Thread',
    unit_cost: '0.0040',
    quantity_on_hand: '0.0000',
    stock_value: '0.0000',
    stock_status: 'out',
};

const button: Material = {
    ...denim,
    id: 3,
    sku: 'TRM-001',
    name: 'Metal shank button 17mm',
    category: 'trim',
    category_label: 'Trim',
    unit: 'pc',
    unit_cost: '0.3500',
    quantity_on_hand: '40.0000',
    stock_value: '14.0000',
    stock_status: 'low',
};

const hrefs = {
    show: (m: Material) => `/materials/${m.id}`,
    edit: (m: Material) => `/materials/${m.id}/edit`,
};

describe('<MaterialsTable>', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps({ app: { currency: 'EUR', locale: 'en' } });
    });

    describe('table (>= md)', () => {
        it('renders one row per material with every column', () => {
            render(
                <MaterialsTable
                    materials={[denim, thread]}
                    hrefs={hrefs}
                    onDelete={vi.fn()}
                />,
            );

            const table = screen.getByRole('table', { name: 'Materials' });

            for (const header of [
                'SKU',
                'Name',
                'Category',
                'Stock',
                'Unit cost',
                'Stock value',
                'Status',
                'Actions',
            ]) {
                expect(
                    within(table).getByRole('columnheader', { name: header }),
                ).toBeInTheDocument();
            }

            // header row + 2 data rows
            expect(within(table).getAllByRole('row')).toHaveLength(3);

            const row = within(table).getByRole('row', {
                name: /Denim 12oz Indigo/,
            });

            expect(within(row).getByText('FAB-001')).toBeInTheDocument();
            expect(within(row).getByText('Fabric')).toBeInTheDocument();
            // Quantity with its unit, unit cost per unit, stock value.
            expect(within(row).getByText('250 m')).toBeInTheDocument();
            expect(within(row).getByText('€8.50')).toBeInTheDocument();
            expect(within(row).getByText('/ m')).toBeInTheDocument();
            expect(within(row).getByText('€2,125.00')).toBeInTheDocument();
            expect(within(row).getByText('In stock')).toBeInTheDocument();
        });

        it('shows 4 decimals for tiny per-unit costs and the stock status per row', () => {
            render(
                <MaterialsTable
                    materials={[thread, button]}
                    hrefs={hrefs}
                    onDelete={vi.fn()}
                />,
            );

            const table = screen.getByRole('table');

            expect(within(table).getByText('€0.0040')).toBeInTheDocument();
            expect(within(table).getByText('Out of stock')).toBeInTheDocument();
            expect(within(table).getByText('Low stock')).toBeInTheDocument();
        });

        it('links the name, View and Edit through the hrefs builders', () => {
            render(
                <MaterialsTable
                    materials={[denim]}
                    hrefs={hrefs}
                    onDelete={vi.fn()}
                />,
            );

            const table = screen.getByRole('table');

            expect(
                within(table).getByRole('link', { name: 'Denim 12oz Indigo' }),
            ).toHaveAttribute('href', '/materials/1');
            expect(
                within(table).getByRole('link', {
                    name: 'View Denim 12oz Indigo',
                }),
            ).toHaveAttribute('href', '/materials/1');
            expect(
                within(table).getByRole('link', {
                    name: 'Edit Denim 12oz Indigo',
                }),
            ).toHaveAttribute('href', '/materials/1/edit');
        });

        it('calls onDelete with the material (and does not delete itself)', async () => {
            const user = userEvent.setup();
            const onDelete = vi.fn();

            render(
                <MaterialsTable
                    materials={[denim, thread]}
                    hrefs={hrefs}
                    onDelete={onDelete}
                />,
            );

            const table = screen.getByRole('table');

            await user.click(
                within(table).getByRole('button', {
                    name: 'Delete Polyester thread',
                }),
            );

            expect(onDelete).toHaveBeenCalledOnce();
            expect(onDelete).toHaveBeenCalledWith(thread);
        });
    });

    describe('cards (< md)', () => {
        it('renders one card per material with the same information', () => {
            render(
                <MaterialsTable
                    materials={[denim, thread]}
                    hrefs={hrefs}
                    onDelete={vi.fn()}
                />,
            );

            const list = screen.getByRole('list', { name: 'Materials' });
            const cards = within(list).getAllByRole('listitem');

            expect(cards).toHaveLength(2);

            const card = cards[0];

            expect(
                within(card).getByText('Denim 12oz Indigo'),
            ).toBeInTheDocument();
            expect(within(card).getByText('FAB-001')).toBeInTheDocument();
            expect(within(card).getByText(/Fabric/)).toBeInTheDocument();
            expect(within(card).getByText('250 m')).toBeInTheDocument();
            expect(within(card).getByText('€8.50')).toBeInTheDocument();
            expect(within(card).getByText('€2,125.00')).toBeInTheDocument();
            expect(within(card).getByText('In stock')).toBeInTheDocument();
        });

        it('uses the hrefs builders and the delete callback in cards too', async () => {
            const user = userEvent.setup();
            const onDelete = vi.fn();

            render(
                <MaterialsTable
                    materials={[button]}
                    hrefs={hrefs}
                    onDelete={onDelete}
                />,
            );

            const list = screen.getByRole('list', { name: 'Materials' });

            expect(
                within(list).getByRole('link', {
                    name: 'View Metal shank button 17mm',
                }),
            ).toHaveAttribute('href', '/materials/3');
            expect(
                within(list).getByRole('link', {
                    name: 'Edit Metal shank button 17mm',
                }),
            ).toHaveAttribute('href', '/materials/3/edit');

            await user.click(
                within(list).getByRole('button', {
                    name: 'Delete Metal shank button 17mm',
                }),
            );

            expect(onDelete).toHaveBeenCalledWith(button);
        });
    });
});
