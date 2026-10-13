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

    it('does not prefetch any parent', () => {
        render(<Breadcrumbs breadcrumbs={trail} />);

        for (const name of ['Dashboard', 'Materials']) {
            const link = screen.getByRole('link', { name });

            expect(link).not.toHaveAttribute('data-prefetch');
            expect(link).not.toHaveAttribute('data-cache-tags');
        }
    });
});
