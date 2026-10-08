import {
    act,
    fireEvent,
    render,
    screen,
    waitFor,
    within,
} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import MaterialsIndex from '@/pages/materials/index';
import { inertiaMock, resetInertiaMock, setPageProps } from '@/test/inertia';
import {
    categories,
    defaultFilters,
    denim,
    paginate,
    thread,
} from '@/test/fixtures';
import type { MaterialFilters, MaterialsIndexProps } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const VISIT_OPTIONS = {
    preserveState: true,
    preserveScroll: true,
    replace: true,
};

function props(overrides: Partial<MaterialsIndexProps> = {}) {
    return {
        materials: paginate([denim, thread]),
        filters: defaultFilters,
        categories,
        ...overrides,
    } satisfies MaterialsIndexProps;
}

function filtersWith(overrides: Partial<MaterialFilters>): MaterialFilters {
    return { ...defaultFilters, ...overrides };
}

describe('materials/index page', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps({}, { url: '/materials', component: 'materials/index' });
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    describe('with materials', () => {
        it('renders a row per material with stock, cost and status', () => {
            render(<MaterialsIndex {...props()} />);

            expect(
                screen.getByRole('heading', { level: 1, name: 'Materials' }),
            ).toBeInTheDocument();

            const table = screen.getByRole('table', { name: 'Materials' });
            const row = within(table).getByRole('row', {
                name: /Denim 12oz Indigo/,
            });

            expect(within(row).getByText('FAB-001')).toBeInTheDocument();
            expect(within(row).getByText('250 m')).toBeInTheDocument();
            expect(within(row).getByText('€2,125.00')).toBeInTheDocument();
            expect(within(row).getByText('In stock')).toBeInTheDocument();
            expect(
                within(table).getByRole('row', { name: /Polyester thread/ }),
            ).toBeInTheDocument();
            expect(within(table).getByText('Out of stock')).toBeInTheDocument();
        });

        it('links Add material, and each row, through Wayfinder', () => {
            render(<MaterialsIndex {...props()} />);

            expect(
                screen.getByRole('link', { name: 'Add material' }),
            ).toHaveAttribute('href', '/materials/create');

            const table = screen.getByRole('table');

            expect(
                within(table).getByRole('link', { name: 'Denim 12oz Indigo' }),
            ).toHaveAttribute('href', '/materials/1');
            expect(
                within(table).getByRole('link', {
                    name: 'Edit Denim 12oz Indigo',
                }),
            ).toHaveAttribute('href', '/materials/1/edit');
        });

        it('shows the pagination summary only when there are several pages', () => {
            const { rerender } = render(<MaterialsIndex {...props()} />);

            expect(
                screen.queryByRole('navigation', { name: 'Pagination' }),
            ).not.toBeInTheDocument();

            rerender(
                <MaterialsIndex
                    {...props({
                        materials: paginate([denim, thread], {
                            last_page: 3,
                            total: 60,
                            from: 1,
                            to: 25,
                            links: [
                                {
                                    url: null,
                                    label: '« Previous',
                                    active: false,
                                },
                                {
                                    url: null,
                                    label: '1',
                                    active: true,
                                },
                                {
                                    url: '/materials?page=2',
                                    label: '2',
                                    active: false,
                                },
                                {
                                    url: '/materials?page=2',
                                    label: 'Next »',
                                    active: false,
                                },
                            ],
                        }),
                    })}
                />,
            );

            expect(
                screen.getByRole('navigation', { name: 'Pagination' }),
            ).toBeInTheDocument();
            expect(screen.getByText('Showing 1–25 of 60')).toBeInTheDocument();
        });

        it('defines the Materials breadcrumb', () => {
            expect(MaterialsIndex.layout.breadcrumbs).toEqual([
                expect.objectContaining({
                    title: 'Materials',
                    href: { url: '/materials', method: 'get' },
                }),
            ]);
        });
    });

    describe('empty states', () => {
        it('invites the user to add the first material when there are none', () => {
            render(<MaterialsIndex {...props({ materials: paginate([]) })} />);

            expect(
                screen.getByRole('heading', { name: 'No materials yet' }),
            ).toBeInTheDocument();
            expect(
                screen.getByRole('link', { name: 'Add your first material' }),
            ).toHaveAttribute('href', '/materials/create');
            // Nothing to filter yet.
            expect(
                screen.queryByRole('search', { name: 'Filter materials' }),
            ).not.toBeInTheDocument();
            expect(screen.queryByRole('table')).not.toBeInTheDocument();
        });

        it('explains when active filters match nothing and offers to clear them', async () => {
            const user = userEvent.setup();

            render(
                <MaterialsIndex
                    {...props({
                        materials: paginate([]),
                        filters: filtersWith({ search: 'zzz' }),
                    })}
                />,
            );

            const heading = screen.getByRole('heading', {
                name: 'No materials match your filters',
            });

            expect(
                screen.queryByRole('heading', { name: 'No materials yet' }),
            ).not.toBeInTheDocument();
            expect(screen.getByRole('searchbox')).toHaveValue('zzz');

            // The filters bar has its own "Clear filters"; use the one in the empty state.
            await user.click(
                within(heading.parentElement as HTMLElement).getByRole(
                    'button',
                    { name: 'Clear filters' },
                ),
            );

            expect(inertiaMock.router.get).toHaveBeenCalledWith(
                '/materials',
                {},
                VISIT_OPTIONS,
            );
            expect(screen.getByRole('searchbox')).toHaveValue('');
        });

        it('keeps the chosen sort when clearing filters', async () => {
            const user = userEvent.setup();

            render(
                <MaterialsIndex
                    {...props({
                        materials: paginate([]),
                        filters: filtersWith({
                            low_stock: true,
                            sort: '-unit_cost',
                        }),
                    })}
                />,
            );

            const heading = screen.getByRole('heading', {
                name: 'No materials match your filters',
            });

            await user.click(
                within(heading.parentElement as HTMLElement).getByRole(
                    'button',
                    { name: 'Clear filters' },
                ),
            );

            expect(inertiaMock.router.get).toHaveBeenCalledWith(
                '/materials',
                { sort: '-unit_cost' },
                VISIT_OPTIONS,
            );
        });
    });

    describe('filters', () => {
        it('seeds the controls from the server filters', () => {
            render(
                <MaterialsIndex
                    {...props({
                        filters: filtersWith({
                            search: 'denim',
                            category: 'fabric',
                            low_stock: true,
                            sort: '-name',
                        }),
                    })}
                />,
            );

            expect(screen.getByRole('searchbox')).toHaveValue('denim');
            expect(
                screen.getByRole('checkbox', { name: 'Needs restock' }),
            ).toBeChecked();
            expect(
                screen.getByRole('combobox', { name: 'Category' }),
            ).toHaveTextContent('Fabric');
            expect(
                screen.getByRole('combobox', { name: 'Sort by' }),
            ).toHaveTextContent('Name Z–A');
        });

        it('sends low_stock=1 for Needs restock, with no page and no default values', async () => {
            const user = userEvent.setup();

            render(<MaterialsIndex {...props()} />);

            await user.click(
                screen.getByRole('checkbox', { name: 'Needs restock' }),
            );

            expect(inertiaMock.router.get).toHaveBeenCalledOnce();
            expect(inertiaMock.router.get).toHaveBeenCalledWith(
                '/materials',
                { low_stock: 1 },
                VISIT_OPTIONS,
            );
        });

        it('removes low_stock from the query when Needs restock is switched off', async () => {
            const user = userEvent.setup();

            render(
                <MaterialsIndex
                    {...props({ filters: filtersWith({ low_stock: true }) })}
                />,
            );

            await user.click(
                screen.getByRole('checkbox', { name: 'Needs restock' }),
            );

            expect(inertiaMock.router.get).toHaveBeenCalledWith(
                '/materials',
                {},
                VISIT_OPTIONS,
            );
        });

        it('sends the category and accumulates filters picked one after another', async () => {
            const user = userEvent.setup();

            render(<MaterialsIndex {...props()} />);

            await user.click(
                screen.getByRole('combobox', { name: 'Category' }),
            );
            await user.click(screen.getByRole('option', { name: 'Fabric' }));

            expect(inertiaMock.router.get).toHaveBeenLastCalledWith(
                '/materials',
                { category: 'fabric' },
                VISIT_OPTIONS,
            );

            // The server has not answered yet (props unchanged): the page's
            // own state must not lose the category.
            await user.click(
                screen.getByRole('checkbox', { name: 'Needs restock' }),
            );

            expect(inertiaMock.router.get).toHaveBeenLastCalledWith(
                '/materials',
                { category: 'fabric', low_stock: 1 },
                VISIT_OPTIONS,
            );
        });

        it('sends a non-default sort, and drops it when the default is picked', async () => {
            const user = userEvent.setup();

            render(<MaterialsIndex {...props()} />);

            await user.click(screen.getByRole('combobox', { name: 'Sort by' }));
            await user.click(
                screen.getByRole('option', { name: 'Unit cost high→low' }),
            );

            expect(inertiaMock.router.get).toHaveBeenLastCalledWith(
                '/materials',
                { sort: '-unit_cost' },
                VISIT_OPTIONS,
            );

            await user.click(screen.getByRole('combobox', { name: 'Sort by' }));
            await user.click(screen.getByRole('option', { name: 'Name A–Z' }));

            expect(inertiaMock.router.get).toHaveBeenLastCalledWith(
                '/materials',
                {},
                VISIT_OPTIONS,
            );
        });

        it('debounces the search by 300 ms and sends the trimmed text', () => {
            vi.useFakeTimers();

            render(<MaterialsIndex {...props()} />);

            const box = screen.getByRole('searchbox');

            fireEvent.change(box, { target: { value: 'den' } });
            act(() => {
                vi.advanceTimersByTime(200);
            });
            fireEvent.change(box, { target: { value: ' denim ' } });
            act(() => {
                vi.advanceTimersByTime(299);
            });

            expect(inertiaMock.router.get).not.toHaveBeenCalled();

            act(() => {
                vi.advanceTimersByTime(1);
            });

            expect(inertiaMock.router.get).toHaveBeenCalledOnce();
            expect(inertiaMock.router.get).toHaveBeenCalledWith(
                '/materials',
                { search: 'denim' },
                VISIT_OPTIONS,
            );
        });

        it('never sends the page, even when the user is on page 3', async () => {
            const user = userEvent.setup();

            render(
                <MaterialsIndex
                    {...props({
                        materials: paginate([denim], {
                            current_page: 3,
                            last_page: 5,
                        }),
                    })}
                />,
            );

            await user.click(
                screen.getByRole('checkbox', { name: 'Needs restock' }),
            );

            const [, query] = inertiaMock.router.get.mock.calls[0] as [
                string,
                Record<string, unknown>,
            ];

            expect(query).not.toHaveProperty('page');
        });

        it('keeps what the user typed when the server echoes the request', () => {
            vi.useFakeTimers();

            const { rerender } = render(<MaterialsIndex {...props()} />);

            fireEvent.change(screen.getByRole('searchbox'), {
                target: { value: 'denim' },
            });
            act(() => {
                vi.advanceTimersByTime(300);
            });
            fireEvent.change(screen.getByRole('searchbox'), {
                target: { value: 'denim j' },
            });

            // Response to the first request arrives while the user keeps typing.
            rerender(
                <MaterialsIndex
                    {...props({ filters: filtersWith({ search: 'denim' }) })}
                />,
            );

            expect(screen.getByRole('searchbox')).toHaveValue('denim j');
            expect(inertiaMock.router.get).toHaveBeenCalledOnce();
        });

        it('adopts filters that did not come from this page (back button)', () => {
            const { rerender } = render(<MaterialsIndex {...props()} />);

            rerender(
                <MaterialsIndex
                    {...props({
                        filters: filtersWith({
                            search: 'thread',
                            low_stock: true,
                        }),
                    })}
                />,
            );

            expect(screen.getByRole('searchbox')).toHaveValue('thread');
            expect(
                screen.getByRole('checkbox', { name: 'Needs restock' }),
            ).toBeChecked();
        });
    });

    describe('delete', () => {
        async function openDialog() {
            const user = userEvent.setup();

            render(<MaterialsIndex {...props()} />);

            // The table (>= md) and the cards (< md) are both in the DOM.
            await user.click(
                screen.getAllByRole('button', {
                    name: 'Delete Denim 12oz Indigo',
                })[0],
            );

            return user;
        }

        it('asks for confirmation naming the material and its SKU', async () => {
            await openDialog();

            const dialog = screen.getByRole('alertdialog', {
                name: 'Delete material?',
            });

            expect(within(dialog).getByText(/Denim 12oz Indigo/)).toBeVisible();
            expect(within(dialog).getByText(/SKU FAB-001/)).toBeVisible();
            expect(inertiaMock.router.delete).not.toHaveBeenCalled();
        });

        it('does nothing when cancelled', async () => {
            const user = await openDialog();

            await user.click(screen.getByRole('button', { name: 'Cancel' }));

            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
            expect(inertiaMock.router.delete).not.toHaveBeenCalled();
        });

        it('gives focus back to the clicked Delete button when dismissed', async () => {
            const user = await openDialog();

            await user.click(screen.getByRole('button', { name: 'Cancel' }));

            await waitFor(() =>
                expect(
                    screen.getAllByRole('button', {
                        name: 'Delete Denim 12oz Indigo',
                    })[0],
                ).toHaveFocus(),
            );
        });

        it('closes with Escape (keyboard only)', async () => {
            const user = await openDialog();

            await user.keyboard('{Escape}');

            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
        });

        it('deletes through the destroy URL, shows progress, then closes', async () => {
            const user = await openDialog();

            await user.click(
                screen.getByRole('button', { name: 'Delete material' }),
            );

            expect(inertiaMock.router.delete).toHaveBeenCalledOnce();
            expect(inertiaMock.router.delete).toHaveBeenCalledWith(
                '/materials/1',
                expect.objectContaining({ preserveScroll: true }),
            );

            const [, options] = inertiaMock.router.delete.mock.calls[0] as [
                string,
                { onStart: () => void; onFinish: () => void },
            ];

            act(() => options.onStart());

            expect(
                screen.getByRole('button', { name: /delete material/i }),
            ).toBeDisabled();
            expect(
                screen.getByRole('button', { name: 'Cancel' }),
            ).toBeDisabled();

            act(() => options.onFinish());

            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
        });
    });
});
