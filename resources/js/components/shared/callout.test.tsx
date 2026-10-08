import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import { Callout } from '@/components/shared/callout';

type Tone = 'info' | 'warning' | 'danger' | 'success';

const tones: Tone[] = ['info', 'warning', 'danger', 'success'];

describe('<Callout>', () => {
    it('shows the message', () => {
        render(<Callout tone="info">Stock is counted in each unit.</Callout>);

        expect(
            screen.getByText('Stock is counted in each unit.'),
        ).toBeInTheDocument();
    });

    it('leads with the title in bold, then the text', () => {
        render(
            <Callout
                tone="danger"
                title="Canvas Low Sneaker can't be produced."
            >
                Rubber cupsole 42 is out of stock.
            </Callout>,
        );

        const title = screen.getByText("Canvas Low Sneaker can't be produced.");

        expect(title.tagName).toBe('STRONG');
        expect(screen.getByRole('alert')).toHaveTextContent(
            "Canvas Low Sneaker can't be produced. Rubber cupsole 42 is out of stock.",
        );
    });

    it('renders an action beside the message', () => {
        render(
            <Callout
                tone="danger"
                action={<a href="/products/3">See product</a>}
            >
                Out of stock.
            </Callout>,
        );

        expect(
            screen.getByRole('link', { name: 'See product' }),
        ).toHaveAttribute('href', '/products/3');
    });

    it('omits the action area when there is none', () => {
        const { container } = render(
            <Callout tone="info">Just a note.</Callout>,
        );

        expect(container.querySelector('a, button')).not.toBeInTheDocument();
    });

    it.each(tones)('has a decorative icon and the %s status tokens', (tone) => {
        const { container } = render(<Callout tone={tone}>Message</Callout>);

        const box = container.querySelector('[data-slot="callout"]');

        expect(box).toHaveAttribute('data-tone', tone);
        expect(box).toHaveClass(`bg-${tone}`, `text-${tone}-foreground`);
        expect(box?.querySelector('svg')).toHaveAttribute(
            'aria-hidden',
            'true',
        );
    });

    it('uses a different icon for every tone', () => {
        const icons = tones.map((tone) => {
            const { container, unmount } = render(
                <Callout tone={tone}>Message</Callout>,
            );
            const markup = container.querySelector('svg')?.innerHTML;

            unmount();

            return markup;
        });

        expect(new Set(icons).size).toBe(4);
    });

    describe('role', () => {
        it('interrupts for danger by default (role alert)', () => {
            render(<Callout tone="danger">Out of stock.</Callout>);

            expect(screen.getByRole('alert')).toBeInTheDocument();
        });

        it('is a note for warning and info by default', () => {
            render(
                <>
                    <Callout tone="warning">Careful.</Callout>
                    <Callout tone="info">Good to know.</Callout>
                </>,
            );

            expect(screen.getAllByRole('note')).toHaveLength(2);
            expect(screen.queryByRole('alert')).not.toBeInTheDocument();
        });

        it('is a polite status for success by default', () => {
            render(<Callout tone="success">Saved.</Callout>);

            expect(screen.getByRole('status')).toHaveTextContent('Saved.');
        });

        it.each(['alert', 'note', 'status'] as const)(
            'can be forced to role %s',
            (role) => {
                render(
                    <Callout tone="info" role={role}>
                        Message
                    </Callout>,
                );

                expect(screen.getByRole(role)).toBeInTheDocument();
            },
        );
    });

    it('accepts an extra class name', () => {
        const { container } = render(
            <Callout tone="info" className="mt-4">
                Message
            </Callout>,
        );

        expect(container.querySelector('[data-slot="callout"]')).toHaveClass(
            'mt-4',
        );
    });
});
