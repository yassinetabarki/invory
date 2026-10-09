import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import SettingsLayout from '@/layouts/settings/layout';
import { resetInertiaMock, setPageProps } from '@/test/inertia';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function renderAt(url: string) {
    setPageProps({}, { url });

    return render(
        <SettingsLayout>
            <p>Page content</p>
        </SettingsLayout>,
    );
}

describe('settings layout navigation', () => {
    beforeEach(() => resetInertiaMock());

    it('keeps Profile, Security and Appearance first', () => {
        renderAt('/settings/profile');

        const nav = screen.getByRole('navigation', { name: 'Settings' });

        expect(
            within(nav)
                .getAllByRole('link')
                .map((link) => link.textContent)
                .slice(0, 3),
        ).toEqual(['Profile', 'Security', 'Appearance']);
    });

    it('adds a Catalogue group with Units and both category lists', () => {
        renderAt('/settings/profile');

        const group = screen.getByRole('group', { name: 'Catalogue' });

        expect(
            screen.getByRole('heading', { level: 3, name: 'Catalogue' }),
        ).toBeInTheDocument();
        expect(
            within(group)
                .getAllByRole('link')
                .map((link) => [link.textContent, link.getAttribute('href')]),
        ).toEqual([
            ['Units', '/settings/units'],
            ['Material categories', '/settings/material-categories'],
            ['Product categories', '/settings/product-categories'],
        ]);
    });

    it('comes after the account items', () => {
        renderAt('/settings/profile');

        const links = within(
            screen.getByRole('navigation', { name: 'Settings' }),
        ).getAllByRole('link');

        expect(links.map((link) => link.textContent)).toEqual([
            'Profile',
            'Security',
            'Appearance',
            'Units',
            'Material categories',
            'Product categories',
        ]);
    });

    it.each([
        ['/settings/units', 'Units'],
        ['/settings/material-categories', 'Material categories'],
        ['/settings/product-categories', 'Product categories'],
        ['/settings/profile', 'Profile'],
    ])('marks the entry of %s as the current page', (url, title) => {
        renderAt(url);

        const nav = screen.getByRole('navigation', { name: 'Settings' });

        expect(
            within(nav).getByRole('link', { current: 'page' }),
        ).toHaveTextContent(title);
        expect(
            within(nav).getAllByRole('link', { current: 'page' }),
        ).toHaveLength(1);
    });

    it('highlights a catalogue entry from a deeper URL too', () => {
        renderAt('/settings/units/5');

        expect(screen.getByRole('link', { current: 'page' })).toHaveTextContent(
            'Units',
        );
    });

    it('gives the catalogue tables the full width, and keeps the profile forms narrow', () => {
        const { unmount } = renderAt('/settings/units');

        expect(
            screen.getByText('Page content').closest('section'),
        ).not.toHaveClass('max-w-xl');

        unmount();
        renderAt('/settings/profile');

        expect(screen.getByText('Page content').closest('section')).toHaveClass(
            'max-w-xl',
        );
    });
});
