import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import Labels from '@/pages/settings/labels';
import {
    inertiaMock,
    resetInertiaMock,
    setFormState,
    setPageProps,
} from '@/test/inertia';
import type { LabelSettingsProps } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const props: LabelSettingsProps = {
    ticket_size: 'a4_8',
    ticket_sizes: [
        { value: 'a4_8', label: 'A4 sheet, 8 tickets (2 × 4)' },
        {
            value: 'label_100x60',
            label: '100 × 60 mm, one per label (label printer)',
        },
    ],
};

describe('settings/labels (INV-15, AC-7)', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps({}, { url: '/settings/labels' });
    });

    it('shows the ticket size, the A4 sheet of 8 at first', () => {
        render(<Labels {...props} />);

        expect(
            screen.getByRole('heading', { name: 'Labels', level: 2 }),
        ).toBeInTheDocument();
        expect(
            screen.getByText('How your work tickets print.'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('combobox', { name: 'Ticket size' }),
        ).toHaveTextContent('A4 sheet, 8 tickets (2 × 4)');
        expect(
            screen.getByRole('button', { name: 'Save label settings' }),
        ).toBeInTheDocument();
    });

    it('saves the size chosen', async () => {
        const user = userEvent.setup();
        render(<Labels {...props} />);

        await user.click(screen.getByRole('combobox', { name: 'Ticket size' }));
        await user.click(
            screen.getByRole('option', {
                name: '100 × 60 mm, one per label (label printer)',
            }),
        );
        await user.click(
            screen.getByRole('button', { name: 'Save label settings' }),
        );

        expect(inertiaMock.submissions[0]).toMatchObject({
            action: '/settings/labels',
            method: 'put',
            data: { ticket_size: 'label_100x60' },
        });
    });

    it("shows a spinner while saving, and the server's error under the list", () => {
        setFormState({
            processing: true,
            errors: { ticket_size: 'Choose a ticket size.' },
        });
        render(<Labels {...props} />);

        expect(
            screen.getByRole('button', { name: /Save label settings/ }),
        ).toBeDisabled();
        expect(
            screen.getByRole('combobox', { name: 'Ticket size' }),
        ).toHaveAccessibleDescription('Choose a ticket size.');
    });
});
