import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { SalePanel } from '@/components/sales/sale-panel';
import { recentSales, saleProducts } from '@/test/fixtures/sales';
import { inertiaMock, resetInertiaMock } from '@/test/inertia';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const openers: HTMLElement[] = [];

/** A control that "had focus" when the panel opened, outside the rendered tree. */
function createOpener() {
    const opener = document.createElement('button');

    opener.textContent = 'Record sale';
    document.body.append(opener);
    openers.push(opener);

    return { current: opener };
}

function renderPanel(props: Partial<ComponentProps<typeof SalePanel>> = {}) {
    const onClose = vi.fn();
    const returnFocusTo = createOpener();
    const user = userEvent.setup();

    const view = render(
        <SalePanel
            variant="aside"
            sale={null}
            products={saleProducts}
            initialProductId={null}
            returnFocusTo={returnFocusTo}
            onClose={onClose}
            {...props}
        />,
    );

    return { onClose, returnFocusTo, user, ...view };
}

describe('<SalePanel>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    afterEach(() => {
        for (const opener of openers.splice(0)) {
            opener.remove();
        }
    });

    describe('aside (wide screens)', () => {
        it('is a card beside the table, headed "Record a sale"', () => {
            renderPanel();

            const aside = screen.getByRole('complementary', {
                name: 'Record a sale',
            });

            expect(
                within(aside).getByRole('heading', {
                    level: 2,
                    name: 'Record a sale',
                }),
            ).toBeVisible();
            expect(
                within(aside).getByRole('combobox', { name: 'Product' }),
            ).toBeVisible();
            expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
        });

        it('is headed "Edit sale" for an existing sale and fills the form', () => {
            renderPanel({ sale: recentSales[3] });

            const aside = screen.getByRole('complementary', {
                name: 'Edit sale',
            });

            expect(within(aside).getByLabelText('Quantity')).toHaveValue('4');
            expect(
                within(aside).getByRole('combobox', { name: 'Product' }),
            ).toHaveTextContent('Denim Jacket');
        });

        it('pre-selects the product it was opened for', () => {
            renderPanel({ initialProductId: 2 });

            expect(
                screen.getByRole('combobox', { name: 'Product' }),
            ).toHaveTextContent('Classic White T-shirt · PRD-TSH-002');
            expect(screen.getByLabelText('Unit price')).toHaveValue('24.0000');
        });

        it('closes with the X button and hands focus back to what opened it', async () => {
            const { user, onClose, returnFocusTo } = renderPanel();

            await user.click(screen.getByRole('button', { name: 'Close' }));

            expect(onClose).toHaveBeenCalledOnce();
            expect(returnFocusTo.current).toHaveFocus();
        });

        it('closes with Cancel and hands focus back to what opened it', async () => {
            const { user, onClose, returnFocusTo } = renderPanel();

            await user.click(screen.getByRole('button', { name: 'Cancel' }));

            expect(onClose).toHaveBeenCalledOnce();
            expect(returnFocusTo.current).toHaveFocus();
        });

        it('closes after a successful save', async () => {
            const { user, onClose } = renderPanel({ initialProductId: 1 });

            await user.type(screen.getByLabelText('Quantity'), '2');
            await user.click(screen.getByRole('button', { name: 'Save sale' }));

            const options = inertiaMock.submissions[0].options as {
                onSuccess: () => void;
            };

            options.onSuccess();

            expect(onClose).toHaveBeenCalledOnce();
        });

        it('starts a fresh form when it is opened for another product', async () => {
            const { user, rerender, onClose, returnFocusTo } = renderPanel({
                initialProductId: 1,
            });

            await user.type(screen.getByLabelText('Quantity'), '9');

            rerender(
                <SalePanel
                    variant="aside"
                    sale={null}
                    products={saleProducts}
                    initialProductId={2}
                    returnFocusTo={returnFocusTo}
                    onClose={onClose}
                />,
            );

            expect(screen.getByLabelText('Quantity')).toHaveValue('');
            expect(
                screen.getByRole('combobox', { name: 'Product' }),
            ).toHaveTextContent('Classic White T-shirt');
        });
    });

    describe('sheet (below lg)', () => {
        it('is a dialog named after the form, not a card beside the table', () => {
            renderPanel({ variant: 'sheet' });

            const dialog = screen.getByRole('dialog', {
                name: 'Record a sale',
            });

            expect(
                within(dialog).getByRole('combobox', { name: 'Product' }),
            ).toBeVisible();
            expect(
                within(dialog).getByRole('button', { name: 'Save sale' }),
            ).toBeVisible();
            expect(
                screen.queryByRole('complementary', { hidden: true }),
            ).not.toBeInTheDocument();
        });

        it('describes itself to screen readers', () => {
            renderPanel({ variant: 'sheet' });

            expect(
                screen.getByRole('dialog', { name: 'Record a sale' }),
            ).toHaveAccessibleDescription(
                'Enter the product, quantity, unit price and date of the sale.',
            );
        });

        it('is titled "Edit sale" for an existing sale', () => {
            renderPanel({ variant: 'sheet', sale: recentSales[0] });

            const dialog = screen.getByRole('dialog', { name: 'Edit sale' });

            expect(
                within(dialog).getByRole('combobox', { name: 'Product' }),
            ).toHaveTextContent('Denim Jeans 5-pocket');
        });

        it('pre-selects the product it was opened for', () => {
            renderPanel({ variant: 'sheet', initialProductId: 1 });

            expect(
                screen.getByRole('combobox', { name: 'Product' }),
            ).toHaveTextContent('Denim Jacket · PRD-JKT-001');
        });

        it('closes with Escape', async () => {
            const { user, onClose } = renderPanel({ variant: 'sheet' });

            await user.keyboard('{Escape}');

            expect(onClose).toHaveBeenCalledOnce();
        });

        it("closes with the sheet's own Close button", async () => {
            const { user, onClose } = renderPanel({ variant: 'sheet' });

            await user.click(screen.getByRole('button', { name: 'Close' }));

            expect(onClose).toHaveBeenCalledOnce();
        });

        it('closes with Cancel', async () => {
            const { user, onClose } = renderPanel({ variant: 'sheet' });

            await user.click(screen.getByRole('button', { name: 'Cancel' }));

            expect(onClose).toHaveBeenCalledOnce();
        });
    });

    it('moves from the card to the sheet when the screen narrows, with the same form', () => {
        const { rerender, onClose, returnFocusTo } = renderPanel({
            initialProductId: 1,
        });

        expect(screen.getByRole('complementary')).toBeVisible();

        rerender(
            <SalePanel
                variant="sheet"
                sale={null}
                products={saleProducts}
                initialProductId={1}
                returnFocusTo={returnFocusTo}
                onClose={onClose}
            />,
        );

        expect(
            screen.queryByRole('complementary', { hidden: true }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByRole('dialog', { name: 'Record a sale' }),
        ).toBeVisible();
        expect(
            screen.getByRole('combobox', { name: 'Product' }),
        ).toHaveTextContent('Denim Jacket');
    });
});
