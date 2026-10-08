import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { MaterialKpis } from '@/components/materials/material-kpis';
import { button, thread } from '@/test/fixtures';
import { resetInertiaMock, setPageProps } from '@/test/inertia';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

/** The value (`<dd>`) next to a `<dt>` label. */
function valueOf(label: string): HTMLElement {
    const value = screen.getByText(label, {
        selector: 'dt',
    }).nextElementSibling;

    if (!(value instanceof HTMLElement)) {
        throw new Error(`No value for "${label}"`);
    }

    return value;
}

describe('<MaterialKpis>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('shows the four numbers, each with its unit or currency', () => {
        render(<MaterialKpis material={button} />);

        expect(valueOf('In stock')).toHaveTextContent('900 pc');
        expect(valueOf('Reorder at')).toHaveTextContent('1,000 pc');
        expect(valueOf('Unit cost')).toHaveTextContent('€0.35 / pc');
        expect(valueOf('Stock value')).toHaveTextContent('€315.00');
    });

    it('says "Not set" when there is no reorder level', () => {
        render(<MaterialKpis material={thread} />);

        expect(valueOf('Reorder at')).toHaveTextContent('Not set');
    });

    it('keeps 4 decimals for a unit cost below one cent', () => {
        render(<MaterialKpis material={thread} />);

        expect(valueOf('Unit cost')).toHaveTextContent('€0.0040 / m');
    });

    it('formats money in the installation currency', () => {
        setPageProps({
            app: { currency: 'TND', locale: 'en', today: '2026-10-08' },
        });
        render(<MaterialKpis material={button} />);

        // Intl puts a no-break space between "TND" and the number.
        expect(valueOf('Stock value')).toHaveTextContent('TND 315.000');
        expect(valueOf('Unit cost')).toHaveTextContent('TND 0.350 / pc');
    });
});
