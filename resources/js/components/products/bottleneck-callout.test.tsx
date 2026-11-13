import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { BottleneckCallout } from '@/components/products/bottleneck-callout';
import {
    canvasLowSneaker,
    choreJacket,
    denimBlackByVariantLine,
    denimJacket,
    denimJacketLightWash,
    denimJacketPerColour,
    denimJacketVarying,
    jacketWithoutComposition,
    materialRef,
} from '@/test/fixtures/products';
import { resetInertiaMock } from '@/test/inertia';
import type { MaterialRef, Product } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function renderCallout(product: Product) {
    return render(
        <BottleneckCallout
            productName={product.name}
            capacity={product.capacity}
            components={product.components}
            singleColour={product.colours.length === 1}
            materialHref={(material: MaterialRef) =>
                `/materials/${material.id}`
            }
            editHref={`/products/${product.id}/edit`}
        />,
    );
}

/**
 * The Denim Jacket with the button's stock at 7 pc: a unit needs 8 pc, so
 * nothing can be produced although the button is not out of stock.
 */
const buttonShort: Product = {
    ...denimJacket,
    capacity: {
        max_producible: 0,
        bottleneck: materialRef('button'),
        bottleneck_colour: null,
        bottleneck_missing: false,
        bottleneck_colour_status: null,
        bottleneck_stock: '7.0000',
        best: 0,
        by_variant: null,
    },
    components: denimJacket.components.map((line) =>
        line.is_bottleneck
            ? { ...line, stock_on_hand: '7.0000', units_possible: 0 }
            : line,
    ),
};

describe('<BottleneckCallout>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    describe('when production is limited (warning)', () => {
        it('names the bottleneck, what its stock covers and the need per unit', () => {
            renderCallout(denimJacket);

            const callout = screen.getByRole('note');

            expect(callout).toHaveAttribute('data-tone', 'warning');
            expect(callout).toHaveTextContent(
                'Bottleneck: Metal shank button 17mm.',
            );
            expect(callout).toHaveTextContent(
                '900 pc in stock covers 112 units at 8 pc each.',
            );
        });

        it('mentions that the bottleneck is also below its reorder level', () => {
            renderCallout(denimJacket);

            expect(screen.getByRole('note')).toHaveTextContent(
                "It's also below its reorder level of 1,000 pc.",
            );
        });

        it('leaves the reorder note out when the stock is above the reorder level', () => {
            renderCallout(choreJacket);

            const callout = screen.getByRole('note');

            expect(callout).toHaveTextContent(
                'Bottleneck: Cotton canvas 10oz Natural.',
            );
            expect(callout).toHaveTextContent(
                '300 m in stock covers 129 units at 2.31 m each.',
            );
            expect(callout).not.toHaveTextContent('reorder level');
        });

        it('links to the material', () => {
            renderCallout(denimJacket);

            expect(
                screen.getByRole('link', { name: 'Open material' }),
            ).toHaveAttribute('href', '/materials/5');
        });

        it('says "1 unit" when the stock covers a single unit', () => {
            renderCallout({
                ...denimJacket,
                capacity: {
                    ...denimJacket.capacity,
                    max_producible: 1,
                    best: 1,
                },
            });

            expect(screen.getByRole('note')).toHaveTextContent(
                'covers 1 unit at 8 pc each.',
            );
        });
    });

    describe('when nothing can be produced (danger)', () => {
        it('says the material is out of stock', () => {
            renderCallout(canvasLowSneaker);

            const callout = screen.getByRole('alert');

            expect(callout).toHaveAttribute('data-tone', 'danger');
            expect(callout).toHaveTextContent(
                "Canvas Low Sneaker can't be produced:",
            );
            expect(callout).toHaveTextContent(
                'Rubber cupsole 42 is out of stock.',
            );
            expect(
                screen.getByRole('link', { name: 'Open material' }),
            ).toHaveAttribute('href', '/materials/9');
        });

        it("says the stock doesn't cover one unit, with what is in stock and what a unit needs", () => {
            renderCallout(buttonShort);

            const callout = screen.getByRole('alert');

            expect(callout).toHaveTextContent(
                "Metal shank button 17mm doesn't cover one unit.",
            );
            expect(callout).toHaveTextContent(
                '7 pc in stock, and each unit needs 8 pc.',
            );
            expect(callout).not.toHaveTextContent('out of stock');
        });
    });

    describe('without a composition (info)', () => {
        it('explains it and links to the edit page', () => {
            renderCallout(jacketWithoutComposition);

            const callout = screen.getByRole('note');

            expect(callout).toHaveAttribute('data-tone', 'info');
            expect(callout).toHaveTextContent('No composition defined.');
            expect(callout).toHaveTextContent(
                'Add the materials that make one unit',
            );
            expect(
                screen.getByRole('link', { name: 'Edit composition' }),
            ).toHaveAttribute('href', '/products/6/edit');
        });
    });

    describe('with a By colour and size line (INV-16)', () => {
        const example = denimJacketVarying.capacity;
        const byVariant = example.by_variant;

        if (!byVariant) {
            throw new Error('The varying fixture needs by_variant');
        }

        const binding = byVariant.binding;

        it('names the colour and size, what the stock covers, and the best case', () => {
            renderCallout(denimJacketVarying);

            const callout = screen.getByRole('note');

            expect(callout).toHaveAttribute('data-tone', 'warning');
            expect(callout).toHaveTextContent(
                'Bottleneck: Denim 12oz Black (Black, size XL).',
            );
            expect(callout).toHaveTextContent(
                '40 m in stock covers 16 units of Black, size XL — the smallest across every colour and size.',
            );
            expect(callout).toHaveTextContent(
                'Best case: 112 units, in Indigo — limited there by Metal shank button 17mm.',
            );
            expect(
                screen.getByRole('link', { name: 'Metal shank button 17mm' }),
            ).toHaveAttribute('href', '/materials/5');
            expect(
                screen.getByRole('link', { name: 'Open material' }),
            ).toHaveAttribute('href', '/materials/13');
        });

        it.each([
            {
                name: 'out of stock: says the variant can not be made',
                max: 0,
                line: { stock_on_hand: '0.0000' },
                body: "Denim 12oz Black is out of stock, so Black, size XL can't be made.",
                absent: 'in stock covers',
            },
            {
                name: 'less than one unit: shows what is in stock',
                max: 0,
                line: { stock_on_hand: '0.5000' },
                body: "Denim 12oz Black doesn't cover one unit of Black, size XL. 0.5 m in stock.",
                absent: 'in stock covers',
            },
            {
                name: 'below its reorder level: adds the reorder sentence',
                max: 16,
                line: {
                    stock_on_hand: '40.0000',
                    material: {
                        ...denimBlackByVariantLine.material,
                        stock_status: 'low' as const,
                        reorder_level: '50.0000',
                    },
                },
                body: "40 m in stock covers 16 units of Black, size XL — the smallest across every colour and size. It's also below its reorder level of 50 m.",
                absent: "can't be made",
            },
        ])('$name', ({ max, line: change, body, absent }) => {
            renderCallout({
                ...denimJacketVarying,
                capacity: { ...example, max_producible: max },
                components: denimJacketVarying.components.map((line) =>
                    line.id === denimBlackByVariantLine.id
                        ? { ...line, ...change }
                        : line,
                ),
            });

            const callout = screen.getByRole('note');

            expect(callout).toHaveTextContent(
                'Bottleneck: Denim 12oz Black (Black, size XL).',
            );
            expect(callout).toHaveTextContent(body);
            expect(callout).not.toHaveTextContent(absent);
            expect(callout).toHaveTextContent(
                'Best case: 112 units, in Indigo — limited there by Metal shank button 17mm.',
            );
            expect(screen.queryByRole('alert')).not.toBeInTheDocument();
        });

        it('leaves the size out for a One size product', () => {
            renderCallout({
                ...denimJacketVarying,
                capacity: {
                    ...example,
                    by_variant: {
                        ...byVariant,
                        binding: { ...binding, size: null },
                    },
                },
            });

            const callout = screen.getByRole('note');

            expect(callout).toHaveTextContent(
                'Bottleneck: Denim 12oz Black (Black).',
            );
            expect(callout).toHaveTextContent(
                '40 m in stock covers 16 units of Black — the smallest across every colour.',
            );
        });

        it('leaves the colour out for a one-colour product', () => {
            renderCallout({
                ...denimJacketVarying,
                colours: [binding.colour],
            });

            const callout = screen.getByRole('note');

            expect(callout).toHaveTextContent(
                'Bottleneck: Denim 12oz Black (size XL).',
            );
            expect(callout).toHaveTextContent(
                '40 m in stock covers 16 units of size XL — the smallest across every size.',
            );
        });
    });

    it('names the colour of a material kept per colour, and the colour it lacks (INV-21)', async () => {
        const user = userEvent.setup();
        const onAddColour = vi.fn();
        const callout = (product: Product, handler?: typeof onAddColour) => (
            <BottleneckCallout
                productName={product.name}
                capacity={product.capacity}
                components={product.components}
                singleColour={false}
                materialHref={(material: MaterialRef) =>
                    `/materials/${material.id}`
                }
                editHref={`/products/${product.id}/edit`}
                onAddColour={handler}
            />
        );
        const bestCase =
            'Best case: 112 units, in Indigo — limited there by Metal shank button 17mm.';

        const { rerender } = render(callout(denimJacketPerColour));

        expect(screen.getByRole('note')).toHaveTextContent(
            'Bottleneck: Denim 12oz, Black stock (size XL).',
        );
        expect(screen.getByRole('note')).toHaveTextContent(
            '40 m of Black in stock covers 16 units of Black, size XL — the smallest across every colour and size.',
        );
        expect(screen.getByRole('note')).toHaveTextContent(bestCase);
        // Black (40 m) is itself under the reorder level of 100 m: the sentence follows.
        expect(screen.getByRole('note')).toHaveTextContent(
            "It's also below its reorder level of 100 m.",
        );

        // Indigo (250 m) limits and is fine, although the material has a colour that is low: no sentence.
        rerender(
            callout({
                ...denimJacketPerColour,
                capacity: {
                    ...denimJacketPerColour.capacity,
                    bottleneck_colour: denimJacketPerColour.colours[0],
                    bottleneck_stock: '250.0000',
                    bottleneck_colour_status: 'in_stock',
                },
            }),
        );

        expect(screen.getByRole('note')).toHaveTextContent(
            'Bottleneck: Denim 12oz, Indigo stock (size XL).',
        );
        expect(screen.getByRole('note')).not.toHaveTextContent('reorder level');

        // No Light wash on the material: guaranteed 0, the best case stays.
        rerender(callout(denimJacketLightWash));

        expect(screen.getByRole('note')).toHaveTextContent(
            'Bottleneck: Denim 12oz — no Light wash stock.',
        );
        expect(screen.getByRole('note')).toHaveTextContent(
            "Denim 12oz has no Light wash yet, so Denim Jacket can't be made in Light wash.",
        );
        expect(screen.getByRole('note')).not.toHaveTextContent('in stock');
        expect(screen.getByRole('note')).toHaveTextContent(bestCase);
        expect(
            screen.queryByRole('button', { name: /^Add Light wash/ }),
        ).not.toBeInTheDocument();

        rerender(callout(denimJacketLightWash, onAddColour));
        await user.click(
            screen.getByRole('button', {
                name: 'Add Light wash to Denim 12oz',
            }),
        );

        expect(onAddColour).toHaveBeenCalledWith(14, 11);
    });

    it('renders nothing when there is a capacity but no bottleneck to show', () => {
        const { container } = renderCallout({
            ...denimJacket,
            capacity: {
                max_producible: 5,
                bottleneck: null,
                bottleneck_colour: null,
                bottleneck_missing: false,
                bottleneck_colour_status: null,
                bottleneck_stock: null,
                best: 5,
                by_variant: null,
            },
        });

        expect(container).toBeEmptyDOMElement();
    });
});
