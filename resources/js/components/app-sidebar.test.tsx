import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { AppSidebar } from '@/components/app-sidebar';
import { SidebarProvider } from '@/components/ui/sidebar';
import { TooltipProvider } from '@/components/ui/tooltip';
import { resetInertiaMock, setPageProps } from '@/test/inertia';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function renderSidebarAt(url: string) {
    setPageProps({}, { url });

    return render(
        <TooltipProvider>
            <SidebarProvider>
                <AppSidebar />
            </SidebarProvider>
        </TooltipProvider>,
    );
}

/** The sidebar's own menu: the logo link and the user menu are not nav items. */
function navLinks(): HTMLElement[] {
    return screen
        .getAllByRole('link')
        .filter((link) => link.closest('[data-sidebar="content"]'));
}

describe('<AppSidebar>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('lists Dashboard, Materials, Products, Orders and Sales in that order', () => {
        renderSidebarAt('/dashboard');

        expect(navLinks().map((link) => link.textContent)).toEqual([
            'Dashboard',
            'Materials',
            'Products',
            'Orders',
            'Sales',
        ]);
    });

    it('links each item through Wayfinder', () => {
        renderSidebarAt('/dashboard');

        const links = Object.fromEntries(
            navLinks().map((link) => [link.textContent, link]),
        );

        expect(links.Dashboard).toHaveAttribute('href', '/dashboard');
        expect(links.Materials).toHaveAttribute('href', '/materials');
        expect(links.Products).toHaveAttribute('href', '/products');
        expect(links.Orders).toHaveAttribute('href', '/orders');
        expect(links.Sales).toHaveAttribute('href', '/sales');
    });

    it('gives every item an icon next to its text', () => {
        renderSidebarAt('/dashboard');

        for (const link of navLinks()) {
            expect(link.querySelector('svg')).toBeInTheDocument();
        }
    });

    it('marks Sales as the current page on /sales', () => {
        renderSidebarAt('/sales');

        expect(screen.getByRole('link', { name: 'Sales' })).toHaveAttribute(
            'aria-current',
            'page',
        );
        expect(
            screen.getByRole('link', { name: 'Dashboard' }),
        ).not.toHaveAttribute('aria-current');
    });

    it('keeps Products highlighted on a product page', () => {
        renderSidebarAt('/products/3');

        expect(screen.getByRole('link', { name: 'Products' })).toHaveAttribute(
            'data-active',
            'true',
        );
        expect(screen.getByRole('link', { name: 'Sales' })).toHaveAttribute(
            'data-active',
            'false',
        );
    });
});
