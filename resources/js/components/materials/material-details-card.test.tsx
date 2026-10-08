import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { MaterialDetailsCard } from '@/components/materials/material-details-card';
import { button, denim, thread } from '@/test/fixtures';
import { resetInertiaMock } from '@/test/inertia';

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

describe('<MaterialDetailsCard>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('is a region named Details', () => {
        render(<MaterialDetailsCard material={button} />);

        expect(
            screen.getByRole('region', { name: 'Details' }),
        ).toBeInTheDocument();
    });

    it('shows the unit, supplier, reference, colour, composition and last update', () => {
        render(<MaterialDetailsCard material={button} />);

        expect(valueOf('Unit')).toHaveTextContent('Piece (pc)');
        expect(valueOf('Supplier')).toHaveTextContent('Northgate Trims');
        expect(valueOf('Supplier ref.')).toHaveTextContent('NT-17-SHK-AB');
        expect(valueOf('Supplier ref.')).toHaveClass('font-mono');
        expect(valueOf('Colour')).toHaveTextContent('Antique brass');
        expect(valueOf('Composition')).toHaveTextContent('Zinc alloy');
        expect(valueOf('Notes')).toHaveTextContent(
            'Hammer-on shank. Needs the tack-button press.',
        );
        expect(valueOf('Last updated')).toHaveTextContent('2 Oct 2026');
    });

    it('keeps note line breaks as text', () => {
        render(<MaterialDetailsCard material={denim} />);

        const notes = valueOf('Notes').querySelector('span');

        expect(notes?.textContent).toBe('First line\nSecond line');
        expect(notes).toHaveClass('whitespace-pre-line');
    });

    it('does not interpret notes as HTML', () => {
        render(
            <MaterialDetailsCard
                material={{ ...denim, notes: '<b>bold</b> <img src=x>' }}
            />,
        );

        expect(valueOf('Notes')).toHaveTextContent('<b>bold</b> <img src=x>');
        expect(valueOf('Notes').querySelector('b, img')).toBeNull();
    });

    it('renders an em dash for every empty optional field', () => {
        render(<MaterialDetailsCard material={thread} />);

        for (const label of [
            'Supplier',
            'Supplier ref.',
            'Colour',
            'Composition',
            'Notes',
        ]) {
            expect(valueOf(label)).toHaveTextContent('—');
        }
    });
});
