import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { UndoBatchDialog } from '@/components/production/undo-batch-dialog';
import { colourNamed } from '@/test/fixtures/colours';
import { b0147 } from '@/test/fixtures/progress';
import { resetInertiaMock, respondToHttp } from '@/test/inertia';
import type { UndoBatchPreview } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const denim = { id: 5, sku: 'MAT-5', name: 'Denim 12oz', unit: 'm' } as const;

const preview: UndoBatchPreview = {
    nothing_taken: false,
    rows: [
        {
            material: denim,
            colour: colourNamed('Indigo'),
            missing: false,
            quantity: '7.9200',
            in_stock: '0.0000',
            after: '224.0400',
            short_by: null,
            not_returned: null,
        },
        {
            material: denim,
            colour: colourNamed('Light wash'),
            missing: false,
            quantity: '3.0000',
            in_stock: '0.0000',
            after: null,
            short_by: null,
            not_returned: 'colour_missing',
        },
    ],
};

describe('UndoBatchDialog (INV-21-q, AC-14)', () => {
    beforeEach(() => {
        resetInertiaMock();
        respondToHttp(() => preview);
    });

    it('gives back to the colour and says which colour gets nothing back', async () => {
        render(<UndoBatchDialog batch={b0147} onClose={vi.fn()} />);

        const dialog = within(
            await screen.findByRole('alertdialog', {
                name: 'Undo batch B-0147?',
            }),
        );

        expect(
            await dialog.findByText(
                /gives back what was cut: 7\.92 m Denim 12oz · Indigo\./,
            ),
        ).toBeInTheDocument();
        expect(
            dialog.getByText('Light wash not on this material, not returned'),
        ).toBeInTheDocument();
    });
});
