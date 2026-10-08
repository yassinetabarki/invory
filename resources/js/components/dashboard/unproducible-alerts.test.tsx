import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { UnproducibleAlerts } from '@/components/dashboard/unproducible-alerts';
import { resetInertiaMock } from '@/test/inertia';
import { choreAlert, sneakerAlert } from '@/test/fixtures/dashboard';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

describe('<UnproducibleAlerts>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('renders nothing when every product can be produced', () => {
        const { container } = render(<UnproducibleAlerts alerts={[]} />);

        expect(container).toBeEmptyDOMElement();
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it('says the bottleneck material is out of stock', () => {
        render(<UnproducibleAlerts alerts={[sneakerAlert]} />);

        const alert = screen.getByRole('alert');

        expect(alert).toHaveTextContent(
            "Canvas Low Sneaker can't be produced. Rubber cupsole 42 is out of stock.",
        );
        expect(alert).toHaveAttribute('data-tone', 'danger');
    });

    it("says a material that is in stock doesn't cover one unit", () => {
        render(<UnproducibleAlerts alerts={[choreAlert]} />);

        expect(screen.getByRole('alert')).toHaveTextContent(
            "Canvas Chore Jacket can't be produced. Metal shank button 17mm doesn't cover one unit.",
        );
        expect(screen.getByRole('alert')).not.toHaveTextContent('out of stock');
    });

    it('links See product to the product, naming it for screen readers', () => {
        render(<UnproducibleAlerts alerts={[sneakerAlert]} />);

        const link = within(screen.getByRole('alert')).getByRole('link', {
            name: 'See product Canvas Low Sneaker',
        });

        expect(link).toHaveAttribute('href', '/products/3');
        expect(link).toHaveTextContent('See product');
    });

    it('shows one alert per product, each with its own link', () => {
        render(<UnproducibleAlerts alerts={[sneakerAlert, choreAlert]} />);

        const alerts = screen.getAllByRole('alert');

        expect(alerts).toHaveLength(2);
        expect(
            within(alerts[0]).getByRole('link', { name: /See product/ }),
        ).toHaveAttribute('href', '/products/3');
        expect(
            within(alerts[1]).getByRole('link', { name: /See product/ }),
        ).toHaveAttribute('href', '/products/6');
    });
});
