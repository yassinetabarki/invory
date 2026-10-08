import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { HistoryCard } from '@/components/materials/history-card';
import { button, buttonHistory } from '@/test/fixtures';
import { resetInertiaMock } from '@/test/inertia';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

describe('<HistoryCard>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('is a region named History with its explanation', () => {
        render(<HistoryCard material={button} history={buttonHistory} />);

        expect(
            screen.getByRole('region', { name: 'History' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'Restocks, stock corrections and unit price changes, newest first.',
            ),
        ).toBeInTheDocument();
    });

    it('lists every entry in the server order, in the unit of the material', () => {
        render(<HistoryCard material={button} history={buttonHistory} />);

        const items = screen.getAllByRole('listitem');

        expect(items).toHaveLength(4);
        expect(items[0]).toHaveTextContent('€0.32 → €0.35 per pc');
        expect(items[1]).toHaveTextContent('+500 pc at €0.35 · PO-2291');
        expect(items[2]).toHaveTextContent('600 pc → 400 pc (Stock count)');
        expect(items[3]).toHaveTextContent('600 pc at €0.32');
    });

    it('says so when the material has no history', () => {
        render(<HistoryCard material={button} history={[]} />);

        expect(screen.getByText('No history yet.')).toBeInTheDocument();
    });
});
