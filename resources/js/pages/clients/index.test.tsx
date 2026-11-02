import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import ClientsIndex from '@/pages/clients/index';
import { paginate } from '@/test/fixtures';
import {
    amira,
    boutiqueLina,
    clientSources,
    ines,
    mehdi,
} from '@/test/fixtures/clients';
import { inertiaMock, resetInertiaMock, setPageProps } from '@/test/inertia';
import type {
    ClientSource,
    ClientSummary,
    ClientsIndexProps,
} from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function props(
    clients: ClientSummary[],
    {
        search = null,
        source = null,
        hasClients = clients.length > 0,
    }: {
        search?: string | null;
        source?: ClientSource | null;
        hasClients?: boolean;
    } = {},
): ClientsIndexProps {
    return {
        clients: paginate(clients, { path: '/clients' }),
        filters: { search, source },
        client_sources: clientSources,
        cities: ['Sousse', 'Tunis'],
        has_clients: hasClients,
    };
}

describe('clients/index page', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps({}, { url: '/clients', component: 'clients/index' });
    });

    it('has the title, the description, Add client and the search', () => {
        render(<ClientsIndex {...props([amira])} />);

        expect(
            screen.getByRole('heading', { level: 1, name: 'Clients' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'The people and shops who order from you, and where they found you. Each order belongs to one client.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getAllByRole('button', { name: 'Add client' }),
        ).not.toHaveLength(0);
        expect(screen.getByRole('searchbox')).toHaveAttribute(
            'placeholder',
            'Search name, phone or city',
        );
    });

    it('shows each client: a link to her page, the source label or —, city, phone, open orders and last order', () => {
        render(<ClientsIndex {...props([amira, boutiqueLina, mehdi, ines])} />);

        const table = within(screen.getByRole('table', { name: 'Clients' }));
        const rows = table
            .getAllByRole('row')
            .slice(1)
            .map((row) => row.textContent);

        expect(rows).toEqual([
            'Amira Ben SalahInstagramSousse22 345 678128 Oct 2026',
            'Boutique LinaWalk-in / shopTunis71 234 567115 Oct 2026',
            'Mehdi JaziriWhatsAppAriana29 111 222012 Oct 2026',
            'Ines Mrad—Monastir20 456 7890No orders yet',
        ]);
        expect(
            table.getByRole('link', { name: 'Amira Ben Salah' }),
        ).toHaveAttribute('href', '/clients/1');
        expect(table.getByLabelText('No source')).toBeInTheDocument();
    });

    it('shows one card per client on a phone', () => {
        render(<ClientsIndex {...props([amira, ines])} />);

        const cards = within(
            screen.getByRole('list', { name: 'Clients' }),
        ).getAllByRole('listitem');

        expect(cards[0]).toHaveTextContent(
            'Amira Ben SalahInstagramSousse · 22 345 6781 open order · last 28 Oct 2026',
        );
        expect(cards[1]).toHaveTextContent(
            'Ines MradMonastir · 20 456 789No orders yet',
        );
    });

    it('reloads only the list with the chosen source, kept in the address', async () => {
        const user = userEvent.setup();

        render(<ClientsIndex {...props([amira])} />);

        await user.click(screen.getByRole('combobox', { name: 'Source' }));

        expect(
            screen.getAllByRole('option').map((option) => option.textContent),
        ).toEqual([
            'All sources',
            'Instagram',
            'Facebook',
            'TikTok',
            'WhatsApp',
            'Website',
            'Walk-in / shop',
            'Word of mouth',
            'Other',
        ]);

        await user.click(screen.getByRole('option', { name: 'Instagram' }));

        expect(inertiaMock.router.get).toHaveBeenCalledWith(
            '/clients',
            { source: 'instagram' },
            expect.objectContaining({
                only: ['clients', 'filters'],
                preserveState: true,
                replace: true,
            }),
        );
        expect(
            screen.getByRole('button', { name: 'Filters · 1' }),
        ).toBeInTheDocument();
    });

    it('tells a workshop with no client yet, a search with no match, and a source nobody came from', () => {
        const { unmount } = render(
            <ClientsIndex {...props([], { hasClients: false })} />,
        );

        expect(screen.getByText('No clients yet')).toBeInTheDocument();
        expect(
            screen.getByText(
                'Your clients appear here when you record their first order, or add one now.',
            ),
        ).toBeInTheDocument();
        expect(screen.queryByRole('searchbox')).not.toBeInTheDocument();
        unmount();

        const second = render(
            <ClientsIndex
                {...props([], { search: 'Amina', hasClients: true })}
            />,
        );

        expect(
            screen.getByText('No client matches "Amina".'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Check the spelling, or search by phone or city.'),
        ).toBeInTheDocument();
        second.unmount();

        render(
            <ClientsIndex
                {...props([], { source: 'tiktok', hasClients: true })}
            />,
        );

        expect(
            screen.getByText('No client came from TikTok yet.'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Clear filters' }),
        ).toBeInTheDocument();
    });

    it('opens the Add client window', async () => {
        const user = userEvent.setup();

        render(<ClientsIndex {...props([amira])} />);
        await user.click(
            screen.getAllByRole('button', { name: 'Add client' })[0],
        );

        expect(
            screen.getByRole('dialog', { name: 'Add client' }),
        ).toBeInTheDocument();
    });
});
