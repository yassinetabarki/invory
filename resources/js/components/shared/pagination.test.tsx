import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { Pagination } from '@/components/shared/pagination';
import { resetInertiaMock } from '@/test/inertia';
import type { Paginated } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

type Meta = Paginated<unknown>['meta'];

function buildMeta(overrides: Partial<Meta> = {}): Meta {
    return {
        current_page: 2,
        from: 26,
        last_page: 3,
        links: [
            {
                url: '/materials?page=1',
                label: '&laquo; Previous',
                active: false,
            },
            { url: '/materials?page=1', label: '1', active: false },
            { url: '/materials?page=2', label: '2', active: true },
            { url: '/materials?page=3', label: '3', active: false },
            { url: '/materials?page=3', label: 'Next &raquo;', active: false },
        ],
        path: '/materials',
        per_page: 25,
        to: 50,
        total: 60,
        ...overrides,
    };
}

describe('<Pagination>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('renders nothing when there is a single page', () => {
        const { container } = render(
            <Pagination meta={buildMeta({ last_page: 1 })} />,
        );

        expect(container).toBeEmptyDOMElement();
    });

    it('renders a labelled pagination nav with links built from meta.links', () => {
        render(<Pagination meta={buildMeta()} />);

        const nav = screen.getByRole('navigation', { name: 'Pagination' });

        expect(
            within(nav).getByRole('link', { name: 'Previous page' }),
        ).toHaveAttribute('href', '/materials?page=1');
        expect(
            within(nav).getByRole('link', { name: 'Page 1' }),
        ).toHaveAttribute('href', '/materials?page=1');
        expect(
            within(nav).getByRole('link', { name: 'Page 3' }),
        ).toHaveAttribute('href', '/materials?page=3');
        expect(
            within(nav).getByRole('link', { name: 'Next page' }),
        ).toHaveAttribute('href', '/materials?page=3');
    });

    it('never renders the raw HTML-entity labels', () => {
        render(<Pagination meta={buildMeta()} />);

        expect(screen.queryByText(/&laquo;|&raquo;/)).not.toBeInTheDocument();
    });

    it('marks only the active page with aria-current', () => {
        render(<Pagination meta={buildMeta()} />);

        const current = screen
            .getAllByRole('link')
            .filter((link) => link.getAttribute('aria-current') === 'page');

        expect(current).toHaveLength(1);
        expect(current[0]).toHaveAccessibleName('Page 2');
    });

    it('renders disabled previous/next as non-interactive', () => {
        render(
            <Pagination
                meta={buildMeta({
                    current_page: 1,
                    links: [
                        { url: null, label: '&laquo; Previous', active: false },
                        { url: '/materials?page=1', label: '1', active: true },
                        { url: '/materials?page=2', label: '2', active: false },
                        {
                            url: '/materials?page=2',
                            label: 'Next &raquo;',
                            active: false,
                        },
                    ],
                    last_page: 2,
                })}
            />,
        );

        expect(
            screen.queryByRole('link', { name: 'Previous page' }),
        ).not.toBeInTheDocument();
        expect(screen.getByLabelText('Previous page')).toHaveAttribute(
            'aria-disabled',
            'true',
        );
        expect(
            screen.getByRole('link', { name: 'Next page' }),
        ).toBeInTheDocument();
    });

    it('renders gaps as non-interactive ellipses', () => {
        render(
            <Pagination
                meta={buildMeta({
                    last_page: 10,
                    links: [
                        {
                            url: '/m?page=1',
                            label: '&laquo; Previous',
                            active: false,
                        },
                        { url: '/m?page=1', label: '1', active: true },
                        { url: null, label: '...', active: false },
                        { url: '/m?page=10', label: '10', active: false },
                        {
                            url: '/m?page=2',
                            label: 'Next &raquo;',
                            active: false,
                        },
                    ],
                })}
            />,
        );

        expect(screen.getByText('…')).toBeInTheDocument();
        expect(screen.getAllByRole('link')).toHaveLength(4);
    });

    it('shows the range summary, and can hide it', () => {
        const { rerender } = render(<Pagination meta={buildMeta()} />);

        expect(screen.getByText('Showing 26–50 of 60')).toBeInTheDocument();

        rerender(<Pagination meta={buildMeta()} showSummary={false} />);

        expect(screen.queryByText(/Showing/)).not.toBeInTheDocument();
    });
});
