import { act, fireEvent, render, screen, within } from '@testing-library/react';
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
    button,
    buttonHistory,
    defaultFilters,
    denim,
    paginate,
    thread,
} from '@/test/fixtures';
import {
    archived,
    referenceData,
    referenceMaterialCategories,
} from '@/test/fixtures/reference';
import type {
    MaterialFilters,
    MaterialsIndexProps,
    StockMovement,
} from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const VISIT_OPTIONS = {
    only: ['materials', 'filters', 'recent_history'],
    preserveState: true,
    preserveScroll: true,
    replace: true,
};

function props(overrides: Partial<MaterialsIndexProps> = {}) {
    return {
        materials: paginate([denim, thread]),
        filters: defaultFilters,
        summary: { materials_count: 2, inventory_value: '2125.0000' },
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

        it('describes the catalogue from the summary, not from this page', () => {
            render(
                <MaterialsIndex
                    {...props({
                        materials: paginate([denim]),
                        summary: {
                            materials_count: 12,
                            inventory_value: '10544.3000',
                        },
                    })}
                />,
            );

            expect(
                screen.getByText('12 materials · inventory value €10,544.30'),
            ).toBeInTheDocument();
        });

        it('uses the singular for a single material', () => {
            render(
                <MaterialsIndex
                    {...props({
                        materials: paginate([denim]),
                        summary: {
                            materials_count: 1,
                            inventory_value: '2125.0000',
                        },
                    })}
                />,
            );

            expect(
                screen.getByText('1 material · inventory value €2,125.00'),
            ).toBeInTheDocument();
        });

        it('formats the inventory value in the installation currency', () => {
            setPageProps({
                app: { currency: 'TND', locale: 'en', today: '2026-10-08' },
            });
            render(<MaterialsIndex {...props()} />);

            // Intl separates "TND" and the number with a no-break space.
            expect(
                screen.getByText('2 materials · inventory value TND 2,125.000'),
            ).toBeInTheDocument();
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

        it('does not prefetch Add material or the row links', () => {
            render(<MaterialsIndex {...props()} />);

            const table = screen.getByRole('table');
            const links = [
                screen.getByRole('link', { name: 'Add material' }),
                within(table).getByRole('link', { name: 'Denim 12oz Indigo' }),
                within(table).getByRole('link', {
                    name: 'Edit Denim 12oz Indigo',
                }),
            ];

            for (const link of links) {
                expect(link).not.toHaveAttribute('data-prefetch');
                expect(link).not.toHaveAttribute('data-cache-tags');
            }
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
                    {
                        name: 'Clear filters',
                    },
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
                    {
                        name: 'Clear filters',
                    },
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

        it('reloads only the rows, the filters and the history on a filter change', async () => {
            const user = userEvent.setup();

            render(<MaterialsIndex {...props()} />);

            await user.click(
                screen.getByRole('checkbox', { name: 'Needs restock' }),
            );

            // `summary` (all materials) and the option lists are not requested.
            expect(inertiaMock.router.get).toHaveBeenCalledWith(
                '/materials',
                expect.anything(),
                expect.objectContaining({
                    only: ['materials', 'filters', 'recent_history'],
                }),
            );
        });

        it('reloads only the rows and their history when the page changes', () => {
            render(
                <MaterialsIndex
                    {...props({
                        materials: paginate([denim, thread], {
                            last_page: 2,
                            total: 30,
                            from: 1,
                            to: 25,
                            links: [
                                {
                                    url: null,
                                    label: '« Previous',
                                    active: false,
                                },
                                { url: null, label: '1', active: true },
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
                screen.getByRole('link', { name: 'Page 2' }),
            ).toHaveAttribute('data-only', 'materials,recent_history');
            expect(
                screen.getByRole('link', { name: 'Next page' }),
            ).toHaveAttribute('data-only', 'materials,recent_history');
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

    describe('restock and history', () => {
        const buttonRestock = 'Restock Metal shank button 17mm';
        const buttonHistoryName = 'History of Metal shank button 17mm';

        function tableView() {
            return within(screen.getByRole('table', { name: 'Materials' }));
        }

        it('has no delete action: it moved to the detail page', () => {
            render(<MaterialsIndex {...props()} />);

            expect(
                screen.queryByRole('button', { name: /delete/i }),
            ).not.toBeInTheDocument();
            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
        });

        it('opens the restock form of a row, posting to that material', async () => {
            const user = userEvent.setup();

            render(
                <MaterialsIndex
                    {...props({ materials: paginate([button]) })}
                />,
            );

            await user.click(
                tableView().getByRole('button', { name: buttonRestock }),
            );

            const panel = tableView().getByRole('group', {
                name: 'Restock Metal shank button 17mm',
            });

            expect(panel.querySelector('form')).toHaveAttribute(
                'action',
                '/materials/3/restocks',
            );
            expect(panel).toHaveTextContent('In stock now: 900 pc');
            expect(
                within(panel).getByLabelText('Quantity received'),
            ).toHaveFocus();
        });

        it('submits the restock fields typed in the panel', async () => {
            const user = userEvent.setup();

            render(
                <MaterialsIndex
                    {...props({ materials: paginate([button]) })}
                />,
            );

            await user.click(
                tableView().getByRole('button', { name: buttonRestock }),
            );
            await user.type(screen.getByLabelText('Quantity received'), '500');
            await user.type(screen.getByLabelText('Unit price paid'), '0.3600');
            await user.click(
                screen.getByRole('button', { name: 'Add to stock' }),
            );

            expect(inertiaMock.submissions).toEqual([
                {
                    action: '/materials/3/restocks',
                    method: 'post',
                    data: {
                        quantity: '500',
                        unit_price: '0.3600',
                        received_on: '2026-10-08',
                        reference: '',
                        update_unit_cost: '1',
                    },
                    options: {
                        invalidateCacheTags: [
                            'materials',
                            'products',
                            'dashboard',
                        ],
                    },
                },
            ]);
        });

        it('shows the history skeleton until the deferred recent_history lands, then only that material', async () => {
            const user = userEvent.setup();
            const denimEntry: StockMovement = {
                ...buttonHistory[1],
                id: 99,
                material_id: denim.id,
                reference: 'PO-DENIM',
            };
            const loaded = {
                ...props({ materials: paginate([button, denim]) }),
                recent_history: [denimEntry, ...buttonHistory],
            };

            // The deferred prop is not in the page yet.
            const { rerender } = render(
                <MaterialsIndex
                    {...props({ materials: paginate([button, denim]) })}
                />,
            );

            await user.click(
                tableView().getByRole('button', { name: buttonHistoryName }),
            );

            expect(screen.getByRole('status')).toHaveTextContent(
                'Loading history…',
            );

            setPageProps({ recent_history: loaded.recent_history });
            rerender(<MaterialsIndex {...loaded} />);

            const panel = tableView().getByRole('group', {
                name: 'History · Metal shank button 17mm',
            });

            expect(screen.queryByRole('status')).not.toBeInTheDocument();
            expect(within(panel).getAllByRole('listitem')).toHaveLength(4);
            expect(
                within(panel).getByText('+500 pc at €0.35 · PO-2291'),
            ).toBeInTheDocument();
            expect(within(panel).queryByText(/PO-DENIM/)).toBeNull();
            expect(
                within(panel).getByRole('link', { name: 'Open material page' }),
            ).toHaveAttribute('href', '/materials/3');
        });

        it('returns focus to the row button when a panel is closed with Escape', async () => {
            const user = userEvent.setup();

            render(
                <MaterialsIndex
                    {...props({ materials: paginate([button]) })}
                />,
            );

            const trigger = tableView().getByRole('button', {
                name: buttonRestock,
            });

            await user.click(trigger);
            await user.keyboard('{Escape}');

            expect(screen.queryByRole('group')).not.toBeInTheDocument();
            expect(trigger).toHaveFocus();
        });
    });

    describe('what a row tells about the material', () => {
        it('shows how many products use each material', () => {
            render(
                <MaterialsIndex
                    {...props({ materials: paginate([button, denim]) })}
                />,
            );

            const table = screen.getByRole('table', { name: 'Materials' });

            expect(within(table).getByText('3 products')).toBeInTheDocument();
            expect(
                within(table).getByRole('columnheader', { name: 'Used in' }),
            ).toBeInTheDocument();
        });

        it('shows the photo thumbnail of a material that has one', () => {
            render(
                <MaterialsIndex
                    {...props({ materials: paginate([button]) })}
                />,
            );

            const row = within(
                screen.getByRole('table', { name: 'Materials' }),
            ).getByRole('row', {
                name: /Metal shank button 17mm/,
            });

            expect(row.querySelector('img')).toHaveAttribute(
                'src',
                '/mock/photos/mat-btn-017.svg',
            );
        });
    });
});

describe('materials/index page category filter', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps({}, { url: '/materials', component: 'materials/index' });
    });

    function filterOptions() {
        return within(screen.getByRole('listbox'))
            .getAllByRole('option')
            .map((option) => option.textContent);
    }

    it('offers the categories from the shared reference prop, archived ones hidden', async () => {
        const user = userEvent.setup();

        setPageProps({
            reference: referenceData({
                material_categories: [
                    referenceMaterialCategories[0],
                    archived(referenceMaterialCategories[1]),
                    referenceMaterialCategories[2],
                ],
            }),
        });
        render(<MaterialsIndex {...props()} />);

        await user.click(screen.getByRole('combobox', { name: 'Category' }));

        expect(filterOptions()).toEqual([
            'All categories',
            'Fabric',
            'Interlining',
        ]);
    });

    it('keeps an archived category that is the active filter', async () => {
        const user = userEvent.setup();

        setPageProps({
            reference: referenceData({
                material_categories: [
                    referenceMaterialCategories[0],
                    archived(referenceMaterialCategories[1]),
                    archived(referenceMaterialCategories[2]),
                ],
            }),
        });
        render(
            <MaterialsIndex
                {...props({ filters: filtersWith({ category: 'lining' }) })}
            />,
        );

        expect(
            screen.getByRole('combobox', { name: 'Category' }),
        ).toHaveTextContent('Lining (archived)');

        await user.click(screen.getByRole('combobox', { name: 'Category' }));

        expect(filterOptions()).toEqual([
            'All categories',
            'Fabric',
            'Lining (archived)',
        ]);
    });
});
