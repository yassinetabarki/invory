import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { MaterialUsageCard } from '@/components/materials/material-usage';
import {
    black,
    button,
    buttonUsage,
    denim,
    indigo,
    lightWash,
} from '@/test/fixtures';
import { resetInertiaMock, setPageProps } from '@/test/inertia';
import type { MaterialUsage } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const [jacket, choreJacket, jeans] = buttonUsage;

const INTRO_MANY =
    "3 products use this material. Here's how far your 900 pc go, and how many units you can make right now with all their materials.";

function renderUsage(usage: MaterialUsage[] = buttonUsage) {
    return render(<MaterialUsageCard material={button} usage={usage} />);
}

/** The table (>= md). Both views are in the DOM; CSS shows one of them. */
function table() {
    return within(
        screen.getByRole('table', {
            name: 'Products that use Metal shank button 17mm',
        }),
    );
}

/** The cards (< md). */
function cards() {
    return within(
        screen.getByRole('list', {
            name: 'Products that use Metal shank button 17mm',
        }),
    );
}

describe('<MaterialUsageCard>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('is a region named "What you can make with it"', () => {
        renderUsage();

        expect(
            screen.getByRole('region', { name: 'What you can make with it' }),
        ).toBeInTheDocument();
    });

    describe('intro', () => {
        it('counts the products and says how far the stock goes (plural)', () => {
            renderUsage();

            expect(screen.getByText(INTRO_MANY)).toBeInTheDocument();
        });

        it('uses the singular for one product', () => {
            renderUsage([jacket]);

            expect(
                screen.getByText(
                    "1 product uses this material. Here's how far your 900 pc go, and how many units you can make right now with all its materials.",
                ),
            ).toBeInTheDocument();
        });

        it('says so when no product uses the material, and shows no table', () => {
            renderUsage([]);

            expect(
                screen.getByText('Not used in any product yet.'),
            ).toBeInTheDocument();
            expect(screen.queryByRole('table')).not.toBeInTheDocument();
            expect(screen.queryByRole('list')).not.toBeInTheDocument();
            expect(screen.queryByText(/Suggestion/)).not.toBeInTheDocument();
        });
    });

    describe('table (>= md)', () => {
        it('has the mockup columns', () => {
            renderUsage();

            for (const header of [
                'Product',
                'Needs per unit',
                'This stock covers',
                'Can make now',
                'Limited by',
            ]) {
                expect(
                    table().getByRole('columnheader', { name: header }),
                ).toBeInTheDocument();
            }

            // header + 3 products
            expect(table().getAllByRole('row')).toHaveLength(4);
        });

        it('keeps the server order: bottleneck rows first', () => {
            renderUsage();

            const names = table()
                .getAllByRole('row')
                .slice(1)
                .map((row) => within(row).getAllByRole('link')[0].textContent);

            expect(names).toEqual([
                'Denim Jacket',
                'Canvas Chore Jacket',
                'Denim Jeans 5-pocket',
            ]);
        });

        it('shows the product as a link with its SKU, and the figures with their units', () => {
            renderUsage();

            const row = table().getByRole('row', { name: /Denim Jacket/ });

            expect(
                within(row).getByRole('link', { name: 'Denim Jacket' }),
            ).toHaveAttribute('href', '/products/1');
            expect(within(row).getByText('PRD-JKT-001')).toHaveClass(
                'font-mono',
            );
            // Needs per unit, in the material's unit; stock covers; can make now.
            expect(within(row).getByText('8 pc')).toBeInTheDocument();
            expect(within(row).getAllByText('112 units')).toHaveLength(2);
        });

        it('shows the photo of a product that has one', () => {
            renderUsage();

            const [withPhoto, withoutPhoto] = table()
                .getAllByRole('row')
                .slice(1);

            expect(withPhoto.querySelector('img')).toHaveAttribute(
                'src',
                '/mock/photos/prd-jkt-001.svg',
            );
            expect(withoutPhoto.querySelector('img')).toBeNull();
        });

        it('shows a warning badge, not a link, when this material is the limit', () => {
            renderUsage();

            const row = table().getByRole('row', { name: /Denim Jacket/ });

            expect(
                within(row).getByText('This material (bottleneck)'),
            ).toBeInTheDocument();
            expect(
                within(row).getByText('This material (bottleneck)'),
            ).toHaveClass('bg-warning');
            // Only the product links: no link to a limiting material.
            expect(within(row).getAllByRole('link')).toHaveLength(1);
        });

        it('links to the material that limits the other products', () => {
            renderUsage();

            const chore = table().getByRole('row', {
                name: /Canvas Chore Jacket/,
            });
            const jeansRow = table().getByRole('row', {
                name: /Denim Jeans 5-pocket/,
            });

            expect(
                within(chore).getByRole('link', {
                    name: 'Cotton canvas 10oz Natural',
                }),
            ).toHaveAttribute('href', '/materials/4');
            expect(
                within(jeansRow).getByRole('link', {
                    name: 'Polyester thread',
                }),
            ).toHaveAttribute('href', '/materials/5');
            expect(
                within(chore).queryByText('This material (bottleneck)'),
            ).not.toBeInTheDocument();
        });

        it('marks only the bottleneck rows', () => {
            renderUsage();

            const [first, second, third] = table().getAllByRole('row').slice(1);

            expect(first).toHaveAttribute('data-bottleneck', 'true');
            expect(first).toHaveClass('bg-warning/40');
            expect(second).not.toHaveAttribute('data-bottleneck');
            expect(third).not.toHaveAttribute('data-bottleneck');
        });

        it('adds a status badge for a product that is not active', () => {
            renderUsage();

            const chore = table().getByRole('row', {
                name: /Canvas Chore Jacket/,
            });

            expect(within(chore).getByText('Draft')).toHaveAttribute(
                'data-status',
                'draft',
            );
            // Active products carry no badge.
            expect(table().queryByText('Active')).not.toBeInTheDocument();
            expect(table().getAllByText('Draft')).toHaveLength(1);
        });

        it('shows an archived product with the server label', () => {
            renderUsage([
                {
                    ...jeans,
                    product: {
                        ...jeans.product,
                        status: 'archived',
                        status_label: 'Archived',
                    },
                },
            ]);

            expect(table().getByText('Archived')).toBeInTheDocument();
        });

        it('uses the singular for a single unit', () => {
            renderUsage([
                {
                    ...jacket,
                    units_covered: { min: 1, max: 1 },
                    max_producible: 1,
                },
            ]);

            expect(table().getAllByText('1 unit')).toHaveLength(2);
            expect(table().queryByText('1 units')).not.toBeInTheDocument();
        });

        it('formats numbers in the installation locale', () => {
            setPageProps({
                app: { currency: 'EUR', locale: 'de', today: '2026-10-08' },
            });
            renderUsage([
                { ...jeans, units_covered: { min: 12345, max: 12345 } },
            ]);

            expect(table().getByText('12.345 units')).toBeInTheDocument();
        });
    });

    describe('suggestions', () => {
        it('suggests the next limit for a product this material limits', () => {
            renderUsage();

            expect(
                screen.getByText(
                    /this material limits Denim Jacket to 112 units\. If you restock it, the next limit is Denim 12oz Indigo at 126 units\./,
                ),
            ).toBeInTheDocument();
            expect(screen.getByText('Suggestion:')).toBeInTheDocument();
        });

        it('has one suggestion per bottleneck row and none for the other products', () => {
            renderUsage();

            expect(screen.getAllByText('Suggestion:')).toHaveLength(1);
            expect(
                screen.queryByText(/limits Canvas Chore Jacket/),
            ).not.toBeInTheDocument();
            expect(
                screen.queryByText(/limits Denim Jeans/),
            ).not.toBeInTheDocument();
        });

        it('says restocking raises the capacity directly when it is the only material', () => {
            renderUsage([{ ...jacket, next_limit: null }]);

            expect(
                screen.getByText(
                    /this is Denim Jacket's only material, so restocking it raises its capacity directly\./,
                ),
            ).toBeInTheDocument();
            expect(
                screen.queryByText(/the next limit is/),
            ).not.toBeInTheDocument();
        });

        it('suggests once for each product the material limits', () => {
            renderUsage([
                jacket,
                {
                    ...jeans,
                    bottleneck: { ...jacket.bottleneck },
                    is_bottleneck: true,
                    max_producible: 105,
                    next_limit: {
                        material: jeans.bottleneck,
                        max_producible: 140,
                    },
                },
            ]);

            expect(screen.getAllByText('Suggestion:')).toHaveLength(2);
            expect(
                screen.getByText(
                    /limits Denim Jeans 5-pocket to 105 units\. If you restock it, the next limit is Polyester thread at 140 units\./,
                ),
            ).toBeInTheDocument();
        });

        it('shows no suggestion when no product is limited by this material', () => {
            renderUsage([choreJacket, jeans]);

            expect(screen.queryByText('Suggestion:')).not.toBeInTheDocument();
        });

        it('uses the singular for a single unit in the suggestion', () => {
            renderUsage([
                {
                    ...jacket,
                    max_producible: 1,
                    next_limit: {
                        material: jacket.bottleneck,
                        max_producible: 1,
                    },
                },
            ]);

            expect(
                screen.getByText(
                    /limits Denim Jacket to 1 unit\. If you restock it, the next limit is Metal shank button 17mm at 1 unit\./,
                ),
            ).toBeInTheDocument();
        });
    });

    describe('cards (< md)', () => {
        it('renders one card per product with the same figures', () => {
            renderUsage();

            const items = cards().getAllByRole('listitem');

            expect(items).toHaveLength(3);

            const card = within(items[0]);

            expect(
                card.getByRole('link', { name: 'Denim Jacket' }),
            ).toHaveAttribute('href', '/products/1');
            expect(card.getByText('PRD-JKT-001')).toBeInTheDocument();
            expect(card.getByText('Needs per unit')).toBeInTheDocument();
            expect(card.getByText('8 pc')).toBeInTheDocument();
            expect(card.getByText('This stock covers')).toBeInTheDocument();
            expect(card.getByText('Can make now')).toBeInTheDocument();
            expect(card.getAllByText('112 units')).toHaveLength(2);
            expect(card.getByText('Limited by')).toBeInTheDocument();
            expect(
                card.getByText('This material (bottleneck)'),
            ).toBeInTheDocument();
        });

        it('links the limiting material and marks the draft', () => {
            renderUsage();

            const chore = within(cards().getAllByRole('listitem')[1]);

            expect(chore.getByText('Draft')).toBeInTheDocument();
            expect(
                chore.getByRole('link', { name: 'Cotton canvas 10oz Natural' }),
            ).toHaveAttribute('href', '/materials/4');
        });

        it('highlights the bottleneck card only', () => {
            renderUsage();

            const items = cards().getAllByRole('listitem');

            expect(items[0]).toHaveAttribute('data-bottleneck', 'true');
            expect(items[1]).not.toHaveAttribute('data-bottleneck');
        });
    });

    describe('a product that varies by colour and size', () => {
        const varying = (overrides: Partial<MaterialUsage>): MaterialUsage => ({
            ...jacket,
            effective_quantity: null,
            max_producible: 16,
            next_limit: null,
            product_varies: true,
            ...overrides,
        });
        const blackDenim = varying({
            units_covered: { min: 16, max: 22 },
            bottleneck: jacket.bottleneck,
            is_bottleneck: true,
        });
        const indigoDenim = varying({
            product: { ...jacket.product, id: 2, name: 'Denim Jacket' },
            units_covered: { min: 103, max: 142 },
            bottleneck: jeans.bottleneck,
            is_bottleneck: false,
        });

        it('reads Varies and a range on the Black denim, with the guaranteed figure and the bottleneck badge', () => {
            renderUsage([blackDenim]);

            for (const view of [
                within(table().getAllByRole('row')[1]),
                within(cards().getAllByRole('listitem')[0]),
            ]) {
                expect(view.getByText('Varies')).toBeInTheDocument();
                expect(view.getByText('16–22 units')).toBeInTheDocument();
                expect(view.getByText('16 units')).toBeInTheDocument();
                expect(
                    view.getByText('This material (bottleneck)'),
                ).toBeInTheDocument();
            }
        });

        it('suggests the next limit of a varying product this material limits, keyed on the bottleneck, not on product_varies', () => {
            renderUsage([
                varying({
                    effective_quantity: '8.0000',
                    max_producible: 112,
                    units_covered: { min: 112, max: 112 },
                    is_bottleneck: true,
                    next_limit: {
                        material: jeans.bottleneck,
                        max_producible: 126,
                    },
                }),
            ]);

            expect(
                screen.getByText(
                    /this material limits Denim Jacket to 112 units\. If you restock it, the next limit is Polyester thread at 126 units\./,
                ),
            ).toBeInTheDocument();
        });

        it('reads the range on the Indigo denim, which is not the bottleneck', () => {
            renderUsage([indigoDenim]);

            expect(table().getByText('Varies')).toBeInTheDocument();
            expect(table().getByText('103–142 units')).toBeInTheDocument();
            expect(
                table().queryByText('This material (bottleneck)'),
            ).not.toBeInTheDocument();
        });

        it('keeps reading as today for a product with no By colour and size line', () => {
            renderUsage([jacket]);

            const row = within(table().getAllByRole('row')[1]);

            expect(row.getByText('8 pc')).toBeInTheDocument();
            expect(row.queryByText('Varies')).not.toBeInTheDocument();
            expect(row.getAllByText('112 units')).toHaveLength(2);
            expect(screen.getByText('Suggestion:')).toBeInTheDocument();
        });
    });

    describe('a material kept per colour', () => {
        const denimByColour = {
            ...denim,
            name: 'Denim 12oz',
            stock_mode: 'per_colour' as const,
        };
        const reading = (
            colour: typeof indigo,
            stock: string,
            min: number,
            max: number,
            missing = false,
        ) => ({
            colour,
            missing,
            stock_on_hand: stock,
            units_possible: { min, max },
        });
        const denimRow: MaterialUsage = {
            ...jacket,
            effective_quantity: '1.9800',
            units_covered: { min: 20, max: 126 },
            max_producible: 20,
            bottleneck: {
                id: denim.id,
                sku: denim.sku,
                name: 'Denim 12oz',
                unit: 'm',
            },
            next_limit: { material: jacket.bottleneck, max_producible: 112 },
            by_colour: [
                reading(indigo, '250.0000', 126, 126),
                reading(black, '40.0000', 20, 20),
                reading(lightWash, '0.0000', 0, 0, true),
            ],
            bottleneck_colour: black,
            limit_colour: black,
        };

        it('reads the range, opens By colour with the Add link, and names the colour in the limit and the suggestion', async () => {
            const user = userEvent.setup();

            render(
                <MaterialUsageCard
                    material={denimByColour}
                    usage={[denimRow]}
                />,
            );

            expect(
                screen.getByText(
                    "1 product uses this material. Here's how far your stock goes, colour by colour, and how many units you can make right now with all its materials.",
                ),
            ).toBeInTheDocument();
            expect(
                screen.getByText(
                    /this material limits Denim Jacket to 20 units, in Black\. If you restock Black, the next limit is Metal shank button 17mm at 112 units\./,
                ),
            ).toBeInTheDocument();

            const row = within(
                screen.getByRole('table', {
                    name: 'Products that use Denim 12oz',
                }),
            ).getAllByRole('row')[1];

            expect(within(row).getByText('20–126 units')).toBeInTheDocument();
            expect(
                within(row).getByText(
                    'This material (bottleneck), Black stock',
                ),
            ).toBeInTheDocument();

            await user.click(
                within(row).getByRole('button', { name: 'By colour' }),
            );

            expect(
                screen
                    .getAllByRole('row')[2]
                    .textContent?.startsWith(
                        'Indigo 126 units · Black 20 units · Light wash no stock',
                    ),
            ).toBe(true);

            await user.click(
                screen.getAllByRole('button', {
                    name: 'Add Light wash to Denim 12oz',
                })[0],
            );

            expect(
                await screen.findByRole('alertdialog', {
                    name: 'Add Light wash to Denim 12oz?',
                }),
            ).toBeInTheDocument();
        });
    });
});
