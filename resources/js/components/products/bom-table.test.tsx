import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { BomTable } from '@/components/products/bom-table';
import {
    canvasLowSneaker,
    denimBlackByVariantLine,
    denimJacket,
    denimJacketVarying,
    sizeOptionsFixture,
} from '@/test/fixtures/products';
import { resetInertiaMock, setPageProps } from '@/test/inertia';
import type { MaterialRef, Product } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const materialHref = (material: MaterialRef) => `/materials/${material.id}`;
const sizes = sizeOptionsFixture.slice(0, 4); // S, M, L, XL

function renderTable(product: Product = denimJacket) {
    return render(
        <BomTable
            lines={product.components}
            materialsTotal={product.cost.material_cost}
            capacity={product.capacity.max_producible}
            materialHref={materialHref}
            colours={product.colours}
            sizes={sizes}
        />,
    );
}

function table(): HTMLElement {
    return screen.getByRole('table', { name: 'Composition' });
}

function row(name: RegExp): HTMLElement {
    return within(table()).getByRole('row', { name });
}

describe('<BomTable>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    describe('the table (md and up)', () => {
        it('has a labelled column per figure', () => {
            renderTable();

            const headers = within(table())
                .getAllByRole('columnheader')
                .map((header) => header.textContent);

            expect(headers).toEqual([
                'Material',
                'Qty per unit',
                'Waste',
                'Effective qty',
                'Unit cost',
                'Line cost',
                'In stock',
                'Units possible',
            ]);

            for (const header of within(table()).getAllByRole('columnheader')) {
                expect(header).toHaveAttribute('scope', 'col');
            }
        });

        it('shows each line of the worked example with units next to every quantity', () => {
            renderTable();

            const denim = within(row(/Denim 12oz Indigo/));

            expect(denim.getByText('MAT-DEN-012')).toBeInTheDocument();
            expect(
                denim.getByText('Body, sleeves, collar'),
            ).toBeInTheDocument();
            expect(denim.getByText('1.8 m')).toBeInTheDocument();
            expect(denim.getByText('10%')).toBeInTheDocument();
            expect(denim.getByText('1.98 m')).toBeInTheDocument();
            expect(denim.getByText('€8.50')).toBeInTheDocument();
            expect(denim.getByText('€16.83')).toBeInTheDocument();
            expect(denim.getByText('250 m')).toBeInTheDocument();
            expect(denim.getByText('126')).toBeInTheDocument();

            const button = within(row(/Metal shank button/));

            // Quantity per unit and effective quantity are both 8 pc (no waste).
            expect(button.getAllByText('8 pc')).toHaveLength(2);
            expect(button.getByText('0%')).toBeInTheDocument();
            expect(button.getByText('€0.35')).toBeInTheDocument();
            expect(button.getByText('€2.80')).toBeInTheDocument();
            expect(button.getByText('900 pc')).toBeInTheDocument();
            expect(button.getByText('112')).toBeInTheDocument();

            const label = within(row(/Woven brand label/));

            // Unit cost and line cost are both €0.12.
            expect(label.getAllByText('€0.12')).toHaveLength(2);
            expect(label.getAllByText('1 pc')).toHaveLength(2);
            expect(label.getByText('400 pc')).toBeInTheDocument();
            expect(label.getByText('400')).toBeInTheDocument();
        });

        it('shows a unit cost below one cent with 4 decimals (precise)', () => {
            renderTable();

            const thread = within(row(/Polyester thread/));

            expect(thread.getByText('€0.0040')).toBeInTheDocument();
            expect(thread.getByText('126 m')).toBeInTheDocument();
            expect(thread.getByText('€0.50')).toBeInTheDocument();
            expect(thread.getByText('20,000 m')).toBeInTheDocument();
            expect(thread.getByText('158')).toBeInTheDocument();
        });

        it('adds up to the materials total and the capacity in the footer', () => {
            renderTable();

            const footer = within(within(table()).getAllByRole('rowgroup')[2]);

            expect(footer.getByText('Materials total')).toBeInTheDocument();
            expect(footer.getByText('€20.25')).toBeInTheDocument();
            expect(footer.getByText('112 units')).toBeInTheDocument();
        });

        it('formats the same worked example with the installation currency (TND, 3 decimals)', () => {
            setPageProps({
                app: { currency: 'TND', locale: 'en', today: '2026-10-08' },
            });
            renderTable();

            const denim = within(row(/Denim 12oz Indigo/));

            expect(denim.getByText('TND 8.500')).toBeInTheDocument();
            expect(denim.getByText('TND 16.830')).toBeInTheDocument();

            // 0.0040 is above one TND minor unit (0.001): three decimals are enough.
            expect(
                within(row(/Polyester thread/)).getByText('TND 0.004'),
            ).toBeInTheDocument();
            expect(
                within(row(/Polyester thread/)).getByText('TND 0.504'),
            ).toBeInTheDocument();

            const footer = within(within(table()).getAllByRole('rowgroup')[2]);

            expect(footer.getByText('TND 20.254')).toBeInTheDocument();
        });

        it('links each material to its page', () => {
            renderTable();

            expect(
                within(table()).getByRole('link', {
                    name: 'Metal shank button 17mm',
                }),
            ).toHaveAttribute('href', '/materials/5');
            expect(
                within(table()).getByRole('link', {
                    name: 'Denim 12oz Indigo',
                }),
            ).toHaveAttribute('href', '/materials/4');
        });
    });

    describe('a line by colour and size', () => {
        const mixed = [denimBlackByVariantLine, denimJacket.components[1]];

        function renderMixed() {
            return render(
                <BomTable
                    lines={mixed}
                    materialsTotal={denimJacket.cost.material_cost}
                    capacity={denimJacket.capacity.max_producible}
                    materialHref={materialHref}
                    colours={denimJacketVarying.colours}
                    sizes={sizes}
                />,
            );
        }

        it('reads Varies under Quantity, Effective qty, Line cost and Units possible, on the table and the cards', () => {
            renderMixed();

            const denim = within(row(/Denim 12oz Black/)).getAllByRole('cell');

            // Material, Qty, Waste, Effective qty, Unit cost, Line cost, In stock, Units possible.
            for (const index of [1, 3, 5, 7]) {
                expect(denim[index]).toHaveTextContent('Varies');
            }

            expect(denim[2]).toHaveTextContent('10%');
            expect(denim[6]).toHaveTextContent('40 m');

            // The same line beside it keeps its figures.
            const button = within(row(/Metal shank button/));

            expect(button.getAllByText('8 pc')).toHaveLength(2);
            expect(button.getByText('€2.80')).toBeInTheDocument();
            expect(button.getByText('112')).toBeInTheDocument();
            expect(button.queryByText('Varies')).not.toBeInTheDocument();

            const card = within(
                within(
                    screen.getByRole('list', { name: 'Composition' }),
                ).getAllByRole('listitem')[0],
            );

            for (const label of [
                'Qty per unit',
                'Effective qty',
                'Line cost',
                'Units possible',
            ]) {
                expect(card.getByText(label).parentElement).toHaveTextContent(
                    'Varies',
                );
            }

            expect(card.getByText('Waste').parentElement).toHaveTextContent(
                '10%',
            );
        });
    });

    describe('View grid', () => {
        it("opens the line's cells read-only, with no field to edit", async () => {
            const user = userEvent.setup();

            render(
                <BomTable
                    lines={[
                        denimBlackByVariantLine,
                        ...denimJacket.components.slice(0, 1),
                    ]}
                    materialsTotal={denimJacket.cost.material_cost}
                    capacity={denimJacket.capacity.max_producible}
                    materialHref={materialHref}
                    colours={denimJacketVarying.colours}
                    sizes={sizes}
                />,
            );

            // Only the by-colour-and-size line has the link (the table and the cards each draw it).
            expect(
                screen.getAllByRole('button', { name: /^View grid for/ }),
            ).toHaveLength(2);

            await user.click(
                within(table()).getByRole('button', {
                    name: 'View grid for Denim 12oz Black',
                }),
            );

            const dialog = within(
                screen.getByRole('dialog', { name: 'Denim 12oz Black' }),
            );

            expect(dialog.getByText('1.6000 m')).toBeInTheDocument();
            expect(dialog.getByText('2.2000 m')).toBeInTheDocument();
            expect(dialog.getByText('Indigo')).toBeInTheDocument();
            expect(
                dialog.queryByLabelText(/^Black, size/),
            ).not.toBeInTheDocument();
            expect(dialog.getByText('4 of 8 cells filled')).toBeInTheDocument();
        });
    });

    describe('the bottleneck', () => {
        it('highlights the limiting row and labels it with text, not colour alone', () => {
            renderTable();

            const bottlenecks = within(table())
                .getAllByRole('row')
                .filter((candidate) =>
                    candidate.hasAttribute('data-bottleneck'),
                );

            expect(bottlenecks).toHaveLength(1);
            expect(bottlenecks[0]).toHaveTextContent('Metal shank button 17mm');
            expect(
                within(bottlenecks[0]).getByText('Bottleneck'),
            ).toBeVisible();
            // The other rows carry no badge.
            expect(within(table()).getAllByText('Bottleneck')).toHaveLength(1);
        });

        it('marks the out-of-stock material of a product that cannot be produced', () => {
            renderTable(canvasLowSneaker);

            const cupsole = within(row(/Rubber cupsole 42/));

            expect(cupsole.getByText('Bottleneck')).toBeInTheDocument();
            expect(cupsole.getByText('0')).toBeInTheDocument();
            expect(cupsole.getByText('0 pair')).toBeInTheDocument();

            const footer = within(within(table()).getAllByRole('rowgroup')[2]);

            expect(footer.getByText("Can't produce")).toBeInTheDocument();
        });
    });

    describe('the stacked cards (below md)', () => {
        function cards(): HTMLElement {
            return screen.getByRole('list', { name: 'Composition' });
        }

        it('has one card per material with the same figures', () => {
            renderTable();

            const items = within(cards()).getAllByRole('listitem');

            expect(items).toHaveLength(4);

            const denim = within(items[0]);

            expect(denim.getByText('Denim 12oz Indigo')).toBeInTheDocument();
            expect(denim.getByText('Qty per unit')).toBeInTheDocument();
            expect(denim.getByText('1.8 m')).toBeInTheDocument();
            expect(denim.getByText('1.98 m')).toBeInTheDocument();
            expect(denim.getByText('€16.83')).toBeInTheDocument();
            expect(denim.getByText('250 m')).toBeInTheDocument();
            expect(denim.getByText('126')).toBeInTheDocument();
        });

        it('badges the bottleneck card', () => {
            renderTable();

            const items = within(cards()).getAllByRole('listitem');
            const flagged = items.filter((item) =>
                item.hasAttribute('data-bottleneck'),
            );

            expect(flagged).toHaveLength(1);
            expect(within(flagged[0]).getByText('Bottleneck')).toBeVisible();
            expect(flagged[0]).toHaveTextContent('Metal shank button 17mm');
        });

        it('ends with the materials total and what can be produced', () => {
            renderTable();

            const summary = cards().nextElementSibling as HTMLElement;

            expect(within(summary).getByText('Materials total')).toBeVisible();
            expect(within(summary).getByText('€20.25')).toBeVisible();
            expect(within(summary).getByText('112 units')).toBeVisible();
        });
    });
});
