import { fireEvent, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import OrdersShow from '@/pages/orders/show';
import { indigo, linaTracker, sizeIds, withOneSize } from '@/test/fixtures';
import { boutiqueLina, ines, ref } from '@/test/fixtures/clients';
import {
    inertiaMock,
    resetInertiaMock,
    setFormState,
    setPageProps,
} from '@/test/inertia';
import type {
    OrderLine,
    OrderProduct,
    OrderShowProps,
    TrackerLine,
} from '@/types/domain';

const device = vi.hoisted(() => ({ mobile: false }));

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => device.mobile }));

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const jacket: OrderProduct = {
    id: 1,
    sku: 'PRD-JKT-001',
    name: 'Denim Jacket',
    status: 'active',
    deleted: false,
};
const jeans: OrderProduct = {
    id: 5,
    sku: 'PRD-JNS-005',
    name: 'Denim Jeans 5-pocket',
    status: 'active',
    deleted: false,
};

function line(
    id: number,
    product: OrderProduct,
    quantity: number,
    delivered = 0,
    cancelled = 0,
): OrderLine {
    return withOneSize({
        id,
        product,
        quantity,
        to_deliver: quantity - delivered - cancelled,
        delivered,
        cancelled,
        status: 'in_progress',
        status_label: 'In progress',
    });
}

const props: OrderShowProps = {
    order: {
        id: 7,
        client: 'Boutique Lina',
        ordered_on: '2026-10-15',
        note: 'Deliver before 30 Oct, in 2 boxes',
        lines: [line(70, jacket, 50, 5), line(71, jeans, 30, 0, 5)],
        units: 80,
        to_deliver: 70,
        delivered: 5,
        cancelled: 5,
        status: 'in_progress',
        status_label: 'In progress',
        client_id: 3,
        client_details: null,
    },
    tracker: linaTracker,
};

/** Renders the page with the tracker landed (or not, or rescued), as the mocked `<Deferred>` reads the page props. */
function renderShow(showProps: OrderShowProps = props, rescued: string[] = []) {
    setPageProps(
        { ...showProps },
        { url: '/orders/7', component: 'orders/show', rescuedProps: rescued },
    );

    return render(<OrdersShow {...showProps} />);
}

function card(name: string) {
    return within(
        screen
            .getByRole('heading', { level: 3, name })
            .closest('section') as HTMLElement,
    );
}

/** Each row of a product's table: the row header, then each cell as shown. */
function rows(name: string) {
    return within(
        screen.getByRole('table', { name: `${name} by colour and size` }),
    )
        .getAllByRole('row')
        .slice(1)
        .map((row) => [
            within(row).getByRole('rowheader').textContent,
            ...within(row)
                .getAllByRole('cell')
                .map((cell) => cell.textContent),
        ]);
}

describe('orders/show page (INV-14)', () => {
    beforeEach(() => {
        resetInertiaMock();
        device.mobile = false;
    });

    it('shows the Order tracker: status, "5 of 80 units delivered · 5 cancelled", the tiles and the grey line (AC-1)', () => {
        renderShow();

        const tracker = within(
            screen
                .getByRole('heading', { level: 2, name: 'Order tracker' })
                .closest('section') as HTMLElement,
        );

        expect(
            tracker.getByText('In progress', { selector: '[data-status]' }),
        ).toBeInTheDocument();
        expect(
            tracker.getByText('5 of 80 units delivered · 5 cancelled'),
        ).toBeVisible();
        expect(
            within(tracker.getByRole('list', { name: 'Units of this order' }))
                .getAllByRole('listitem')
                .map((tile) => tile.getAttribute('aria-label')),
        ).toEqual([
            'To deliver, 70 units',
            'Delivered, 5 units',
            'Cancelled, 5 units',
            'Total, 80 units',
        ]);
        expect(
            tracker.getByText(
                /To deliver: ordered, not delivered yet\. On the shelf now is your finished stock/,
            ),
        ).toBeVisible();
        expect(screen.queryByText(/shipped/i)).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', {
                name: /Start cutting|Mark ready|Ship/,
            }),
        ).not.toBeInTheDocument();
    });

    it('shows the frame with placeholders while the tracker loads, and Try again when it failed (AC-1)', () => {
        const { unmount } = renderShow({ order: props.order });

        expect(
            screen.getByRole('heading', { level: 2, name: 'Order tracker' }),
        ).toBeInTheDocument();
        expect(screen.getByRole('status')).toHaveTextContent(
            'Loading the tracker…',
        );
        unmount();

        renderShow({ order: props.order }, ['tracker']);
        expect(screen.getByRole('alert')).toHaveTextContent(
            "Couldn't load the tracker of this order.",
        );
        fireEvent.click(screen.getByRole('button', { name: 'Try again' }));
        expect(inertiaMock.router.reload).toHaveBeenCalledWith({
            only: ['tracker'],
        });
    });

    it('shows each product by colour and size with the shelf now, Out in amber words, and its buttons (AC-2)', () => {
        renderShow();

        expect(
            card('Denim Jacket').getByText('5 of 50 delivered'),
        ).toBeVisible();
        expect(rows('Denim Jacket')).toEqual([
            ['Indigo', '45', '5', '0', '50', '', ''],
            ['S', '10', '0', '0', '10', '', '2'],
            ['M', '20', '0', '0', '20', '', 'Out'],
            ['L', '15', '0', '0', '15', '', '3'],
            ['XL', '0', '5', '0', '5', '', '1'],
            ['Total', '45', '5', '0', '50', '', ''],
        ]);
        expect(
            card('Denim Jacket').getByRole('button', {
                name: 'Mark delivered',
            }),
        ).not.toHaveAttribute('aria-disabled');
        expect(
            card('Denim Jacket').getByRole('button', {
                name: 'More actions for Denim Jacket',
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Denim Jacket' }),
        ).toHaveAttribute('href', '/products/1');
    });

    it("says beside a button why it can't be pressed, and keeps it reachable (AC-2)", () => {
        const nothingOnShelf: TrackerLine = {
            ...linaTracker.lines[1],
            colours: linaTracker.lines[1].colours.map((colour) => ({
                ...colour,
                rows: colour.rows.map((row) => ({
                    ...row,
                    on_shelf: 0,
                    can_deliver: 0,
                })),
            })),
        };
        const allDelivered: TrackerLine = {
            ...linaTracker.lines[0],
            to_deliver: 0,
            delivered: 50,
            colours: [],
        };

        renderShow({
            ...props,
            tracker: { ...linaTracker, lines: [allDelivered, nothingOnShelf] },
        });

        const deliver = card('Denim Jeans 5-pocket').getByRole('button', {
            name: 'Mark delivered',
        });

        expect(deliver).toHaveAttribute('aria-disabled', 'true');
        expect(deliver).toHaveAccessibleDescription(
            'Nothing on the shelf covers this order yet. Units arrive from Progress or a restock.',
        );
        fireEvent.click(deliver);
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();

        expect(
            card('Denim Jacket').getByRole('button', {
                name: 'Mark delivered',
            }),
        ).toHaveAccessibleDescription('Nothing is left to deliver.');
        expect(
            card('Denim Jacket').getByRole('button', { name: 'Cancel units' }),
        ).toHaveAccessibleDescription(
            'Nothing to cancel: no unit is waiting to be delivered.',
        );
    });

    it('marks Indigo S 2 and L 3 delivered from the window: fields where the shelf covers, "Up to", the note (AC-3)', () => {
        renderShow();

        fireEvent.click(
            card('Denim Jacket').getByRole('button', {
                name: 'Mark delivered',
            }),
        );

        const dialog = within(
            screen.getByRole('dialog', {
                name: 'Mark delivered · Denim Jacket',
            }),
        );

        expect(
            dialog.getByText(
                'Boutique Lina · to deliver and on the shelf: Indigo S 2 · L 3',
            ),
        ).toBeVisible();
        expect(dialog.getAllByRole('textbox')).toHaveLength(2);
        expect(dialog.getByLabelText('Indigo S')).toHaveAccessibleDescription(
            'Up to 2',
        );
        expect(dialog.getByLabelText('Indigo L')).toHaveAccessibleDescription(
            'Up to 3',
        );
        expect(dialog.getByLabelText('Indigo S')).toHaveFocus();
        expect(
            dialog.getByText('Delivered units leave your finished stock.'),
        ).toBeVisible();

        fireEvent.change(dialog.getByLabelText('Indigo S'), {
            target: { value: '2' },
        });
        fireEvent.change(dialog.getByLabelText('Indigo L'), {
            target: { value: '3' },
        });
        fireEvent.click(dialog.getByRole('button', { name: 'Mark delivered' }));

        expect(inertiaMock.submissions.at(-1)).toMatchObject({
            action: '/orders/7/lines/70/deliveries',
            method: 'post',
            data: {
                cells: { [indigo.id]: { [sizeIds.S]: '2', [sizeIds.L]: '3' } },
            },
        });
    });

    it("shows the server's message under its field, and Close when the shelf or the order changed (AC-3)", () => {
        renderShow();
        fireEvent.click(
            card('Denim Jacket').getByRole('button', {
                name: 'Mark delivered',
            }),
        );

        setFormState({
            errors: {
                [`cells.${indigo.id}.${sizeIds.S}`]:
                    'You can mark at most 2 units of Indigo S: only 2 are on the shelf.',
            },
        });
        expect(screen.getByLabelText('Indigo S')).toBeInvalid();
        expect(screen.getByLabelText('Indigo S')).toHaveAccessibleDescription(
            'Up to 2 You can mark at most 2 units of Indigo S: only 2 are on the shelf.',
        );

        setFormState({
            errors: {
                cells: 'The shelf or this order changed since you opened this page.',
            },
        });
        expect(
            screen.getByText(
                'The shelf or this order changed since you opened this page.',
            ),
        ).toBeVisible();
        expect(
            within(screen.getByRole('dialog')).queryByRole('button', {
                name: 'Mark delivered',
            }),
        ).not.toBeInTheDocument();
        fireEvent.click(
            within(screen.getByRole('dialog')).getAllByRole('button', {
                name: 'Close',
            })[0],
        );
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('moves delivered units back from the "⋯" menu (AC-3)', async () => {
        const user = userEvent.setup();

        renderShow();
        await user.click(
            card('Denim Jacket').getByRole('button', {
                name: 'More actions for Denim Jacket',
            }),
        );
        await user.click(
            screen.getByRole('menuitem', {
                name: 'Move delivered back to To deliver',
            }),
        );

        const dialog = within(
            screen.getByRole('dialog', {
                name: 'Move back to To deliver · Denim Jacket',
            }),
        );

        expect(
            dialog.getByText('Boutique Lina · delivered: Indigo XL 5'),
        ).toBeVisible();
        expect(
            dialog.getByText('These units go back on your shelf.'),
        ).toBeVisible();

        fireEvent.change(dialog.getByLabelText('Indigo XL'), {
            target: { value: '5' },
        });
        fireEvent.click(dialog.getByRole('button', { name: 'Move back' }));

        expect(inertiaMock.submissions.at(-1)).toMatchObject({
            action: '/orders/7/lines/70/deliveries/back',
            data: { cells: { [indigo.id]: { [sizeIds.XL]: '5' } } },
        });
    });

    it('cancels units: the amber note, Keep units, and "Cancel 5 units" following the typing (AC-4)', () => {
        renderShow();

        fireEvent.click(
            card('Denim Jeans 5-pocket').getByRole('button', {
                name: 'Cancel units',
            }),
        );

        const dialog = within(
            screen.getByRole('dialog', {
                name: 'Cancel units · Denim Jeans 5-pocket',
            }),
        );

        expect(
            dialog.getByText(
                'Boutique Lina · to deliver: Indigo S 5 · M 10 · L 10',
            ),
        ).toBeVisible();
        expect(dialog.getByLabelText('Indigo M')).toHaveAccessibleDescription(
            'Up to 10',
        );
        expect(
            dialog.getByText(
                /This can't be undone\. Finished stock doesn't change\./,
            ),
        ).toBeVisible();
        expect(
            dialog.getByRole('button', { name: 'Keep units' }),
        ).toBeInTheDocument();

        fireEvent.change(dialog.getByLabelText('Indigo L'), {
            target: { value: '5' },
        });
        fireEvent.click(dialog.getByRole('button', { name: 'Cancel 5 units' }));

        expect(inertiaMock.submissions.at(-1)).toMatchObject({
            action: '/orders/7/lines/71/cancellations',
            data: { cells: { [indigo.id]: { [sizeIds.L]: '5' } } },
        });
    });

    it('reads a band per colour and one line per size on a phone, the Total first (AC-2)', () => {
        device.mobile = true;
        renderShow();

        const group = within(
            screen.getByRole('group', {
                name: 'Denim Jacket by colour and size',
            }),
        );

        expect(
            group.getByText('Total · To deliver 45 · Delivered 5 · Total 50'),
        ).toBeVisible();
        expect(
            group.getByText('M · To deliver 20 · Total 20 · On the shelf Out'),
        ).toBeVisible();
    });

    it('asks before deleting and says delivered units are not put back; never refuses (AC-6)', async () => {
        const user = userEvent.setup();

        renderShow();
        await user.click(screen.getByRole('button', { name: 'Delete' }));

        const dialog = screen.getByRole('alertdialog', {
            name: 'Delete order?',
        });

        expect(dialog).toHaveTextContent(
            "The order of Boutique Lina (2 products, 80 units) will be removed. Material stock and sales are not affected. This cannot be undone. Finished stock isn't changed: the 5 units already delivered are not put back.",
        );
        await user.click(
            within(dialog).getByRole('button', { name: 'Delete order' }),
        );
        expect(inertiaMock.router.delete).toHaveBeenCalled();
    });

    it('shows who the client is under the summary: source, city, a call link and all her orders (INV-4 AC-7)', () => {
        renderShow({
            ...props,
            order: { ...props.order, client_details: ref(boutiqueLina) },
        });

        expect(screen.getByText('Walk-in / shop')).toBeInTheDocument();
        expect(
            screen.getByRole('link', {
                name: 'Call Boutique Lina, 71 234 567',
            }),
        ).toHaveAttribute('href', 'tel:71234567');
        expect(
            screen.getByRole('link', { name: 'All orders of Boutique Lina' }),
        ).toHaveAttribute('href', '/clients/2');
    });

    it('starts the client line with the city when the client has no source (INV-4 AC-7)', () => {
        renderShow({
            ...props,
            order: {
                ...props.order,
                client: 'Ines Mrad',
                client_details: ref(ines),
            },
        });

        const line = screen.getByRole('link', {
            name: 'All orders of Ines Mrad',
        }).parentElement;

        expect(line).toHaveTextContent(
            /^Monastir·20 456 789·All orders of Ines Mrad$/,
        );
    });
});
