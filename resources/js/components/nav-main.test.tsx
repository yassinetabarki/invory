import { render, screen } from '@testing-library/react';
import { Boxes, LayoutGrid, Shirt } from 'lucide-react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { NavMain } from '@/components/nav-main';
import { SidebarProvider } from '@/components/ui/sidebar';
import { TooltipProvider } from '@/components/ui/tooltip';
import { dashboard } from '@/routes';
import { index as materialsIndex } from '@/routes/materials';
import { resetInertiaMock, setPageProps } from '@/test/inertia';
import type { NavItem } from '@/types';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const items: NavItem[] = [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
    { title: 'Materials', href: materialsIndex(), icon: Boxes },
    { title: 'Products', href: '/products', icon: Shirt },
];

function renderNavAt(url: string) {
    setPageProps({}, { url });

    return render(
        <TooltipProvider>
            <SidebarProvider>
                <NavMain items={items} />
            </SidebarProvider>
        </TooltipProvider>,
    );
}

function activeLinks(): string[] {
    return screen
        .getAllByRole('link')
        .filter((link) => link.getAttribute('data-active') === 'true')
        .map((link) => link.textContent ?? '');
}

describe('<NavMain>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('lists every item as a link to its route', () => {
        renderNavAt('/dashboard');

        expect(screen.getByRole('link', { name: 'Dashboard' })).toHaveAttribute(
            'href',
            '/dashboard',
        );
        expect(screen.getByRole('link', { name: 'Materials' })).toHaveAttribute(
            'href',
            '/materials',
        );
        expect(screen.getByRole('link', { name: 'Products' })).toHaveAttribute(
            'href',
            '/products',
        );
    });

    it('prefetches every item under the tag of its section', () => {
        renderNavAt('/dashboard');

        const tags = Object.fromEntries(
            screen
                .getAllByRole('link')
                .map((link) => [
                    link.textContent,
                    [
                        link.getAttribute('data-prefetch'),
                        link.getAttribute('data-cache-tags'),
                    ],
                ]),
        );

        expect(tags).toEqual({
            Dashboard: ['true', 'dashboard'],
            Materials: ['true', 'materials'],
            Products: ['true', 'products'],
        });
    });

    it('marks Dashboard as the current page on /dashboard, and only it', () => {
        renderNavAt('/dashboard');

        expect(activeLinks()).toEqual(['Dashboard']);
        expect(screen.getByRole('link', { name: 'Dashboard' })).toHaveAttribute(
            'aria-current',
            'page',
        );
    });

    it('keeps Dashboard exact: pages below /dashboard do not highlight it', () => {
        renderNavAt('/dashboard/reports');

        expect(activeLinks()).toEqual([]);
    });

    it('highlights Materials on its own URL as the current page', () => {
        renderNavAt('/materials');

        expect(activeLinks()).toEqual(['Materials']);
        expect(screen.getByRole('link', { name: 'Materials' })).toHaveAttribute(
            'aria-current',
            'page',
        );
    });

    it.each(['/materials/create', '/materials/5', '/materials/5/edit'])(
        'highlights Materials as the parent section on %s (F-01)',
        (url) => {
            renderNavAt(url);

            expect(activeLinks()).toEqual(['Materials']);
            expect(
                screen.getByRole('link', { name: 'Materials' }),
            ).toHaveAttribute('aria-current', 'true');
        },
    );

    it('ignores the query string and a trailing slash when matching', () => {
        renderNavAt('/materials/?search=denim&page=2');

        expect(activeLinks()).toEqual(['Materials']);
    });

    it('matches whole path segments only', () => {
        renderNavAt('/materials-archive');

        expect(activeLinks()).toEqual([]);
    });

    it('highlights an item given as a plain string href on its sub-URLs', () => {
        renderNavAt('/products/12');

        expect(activeLinks()).toEqual(['Products']);
    });

    it('highlights nothing on pages outside the nav (settings)', () => {
        renderNavAt('/settings/profile');

        expect(activeLinks()).toEqual([]);
    });
});
