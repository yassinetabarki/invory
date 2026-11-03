import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import PrintTickets from '@/pages/progress/tickets';
import { ticket0147, ticket0155 } from '@/test/fixtures/tickets';
import { resetInertiaMock } from '@/test/inertia';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

describe('progress/tickets (INV-15, AC-1, AC-2)', () => {
    let print: ReturnType<typeof vi.fn>;

    beforeEach(() => {
        resetInertiaMock();
        print = vi.fn();
        vi.stubGlobal('print', print);
        vi.spyOn(window, 'requestAnimationFrame').mockImplementation((done) => {
            done(0);

            return 1;
        });
    });

    afterEach(() => {
        vi.unstubAllGlobals();
        vi.restoreAllMocks();
    });

    it('prints a ticket with the batch, its variant, units, start, SKU and path, and nothing about a client or a price', () => {
        render(<PrintTickets tickets={[ticket0147]} size="a4_8" />);

        const ticket = screen.getByRole('article', {
            name: 'Work ticket B-0147',
        });
        const text = ticket.textContent ?? '';

        for (const line of [
            'Work ticket',
            'B-0147',
            'Denim Jacket',
            'Indigo · S',
            '4 units',
            'Started 23 Oct 2026',
            'PRD-JKT-001-IND-S',
            '☐ In cutting ☑ Sewing ☐ Finishing ☐ Put in stock',
            'Printed 30 Oct 2026',
            'Scan to move it to the next phase.',
        ]) {
            expect(text).toContain(line);
        }

        expect(text).not.toMatch(/TND|price|client|phone/i);
        expect(text).not.toContain('Split from');
        expect(
            within(ticket)
                .getByRole('img', { name: 'QR code' })
                .querySelector('path'),
        ).toHaveAttribute('d', ticket0147.qr.path);
    });

    it('adds "Split from B-0149" on a split batch, and lays 8 to an A4 sheet', () => {
        const { container } = render(
            <PrintTickets tickets={[ticket0147, ticket0155]} size="a4_8" />,
        );

        expect(
            screen.getByRole('article', { name: 'Work ticket B-0155' }),
        ).toHaveTextContent('Split from B-0149');
        expect(container.querySelector('style')?.textContent).toBe(
            '@page { size: A4; margin: 0; }',
        );
        expect(
            screen.getByRole('heading', { name: '2 work tickets' }),
        ).toBeInTheDocument();
    });

    it('prints one ticket per 100 × 60 mm label when Settings › Labels says so (AC-7)', () => {
        const { container } = render(
            <PrintTickets tickets={[ticket0147]} size="label_100x60" />,
        );

        expect(container.querySelector('style')?.textContent).toBe(
            '@page { size: 100mm 60mm; margin: 0; }',
        );
    });

    it("opens the browser's print window once drawn, and Print tries again", async () => {
        render(<PrintTickets tickets={[ticket0147]} size="a4_8" />);

        expect(print).toHaveBeenCalledTimes(1);
        await userEvent
            .setup()
            .click(screen.getByRole('button', { name: 'Print' }));
        expect(print).toHaveBeenCalledTimes(2);
    });

    it('says when every batch has left Progress, and prints nothing', () => {
        render(<PrintTickets tickets={[]} size="a4_8" />);

        expect(
            screen.getByText(
                'These batches have left Progress, so there is nothing to print.',
            ),
        ).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Print' })).toBeNull();
        expect(print).not.toHaveBeenCalled();
    });
});
