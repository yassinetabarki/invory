import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { dashboard } from '@/routes';
import { index as materialsIndex } from '@/routes/materials';
import { resetInertiaMock } from '@/test/inertia';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const trail = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Materials', href: materialsIndex() },
    { title: 'Denim 12oz Indigo', href: '/materials/1' },
];

describe('<Breadcrumbs>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('links the parents and names the current page without a link', () => {
        render(<Breadcrumbs breadcrumbs={trail} />);

        expect(screen.getByRole('link', { name: 'Materials' })).toHaveAttribute(
            'href',
            '/materials',
        );

        const current = screen.getByText('Denim 12oz Indigo');

        expect(current).toHaveAttribute('aria-current', 'page');
        expect(current).not.toHaveAttribute('href');
    });

    it('prefetches each parent under the tag of its section', () => {
        render(<Breadcrumbs breadcrumbs={trail} />);

        const dashboardLink = screen.getByRole('link', { name: 'Dashboard' });
        const materialsLink = screen.getByRole('link', { name: 'Materials' });

        expect(dashboardLink).toHaveAttribute('data-prefetch', 'true');
        expect(dashboardLink).toHaveAttribute('data-cache-tags', 'dashboard');
        expect(materialsLink).toHaveAttribute('data-prefetch', 'true');
        expect(materialsLink).toHaveAttribute('data-cache-tags', 'materials');
    });

    it('does not prefetch a parent that has no cache tag', () => {
        render(
            <Breadcrumbs
                breadcrumbs={[
                    { title: 'Settings', href: '/settings/profile' },
                    { title: 'Profile', href: '/settings/profile' },
                ]}
            />,
        );

        expect(
            screen.getByRole('link', { name: 'Settings' }),
        ).not.toHaveAttribute('data-prefetch');
    });
});
