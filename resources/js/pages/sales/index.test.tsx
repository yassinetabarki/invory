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
import { WIDE_SCREEN_QUERY } from '@/components/sales/use-wide-screen';
import SalesIndex from '@/pages/sales/index';
import { paginate } from '@/test/fixtures';
import {
    defaultSalesFilters,
    noSalesTotals,
    recentSales,
    saleOfInactiveProduct,
    salesIndexProps,
    salesPage,
} from '@/test/fixtures/sales';
import { inertiaMock, resetInertiaMock, setPageProps } from '@/test/inertia';
import type { SalesFilters, SalesIndexProps } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const VISIT_OPTIONS = {
    only: ['sales', 'filters', 'totals'],
    preserveState: true,
    preserveScroll: true,
    replace: true,
};

// ---------------------------------------------------------------------------
// Viewport: `useIsWideScreen` asks `matchMedia`, which jsdom answers with "no".
// ---------------------------------------------------------------------------

const originalMatchMedia = window.matchMedia;
const mediaListeners = new Set<EventListenerOrEventListenerObject>();
let wideScreen = false;

function stubViewport(wide: boolean) {
    wideScreen = wide;
    window.matchMedia = (query: string): MediaQueryList => ({
        get matches() {
            return query === WIDE_SCREEN_QUERY ? wideScreen : false;
        },
        media: query,
        onchange: null,
        addEventListener: (
            _type: string,
            listener: EventListenerOrEventListenerObject,
        ) => {
            mediaListeners.add(listener);
        },
        removeEventListener: (
            _type: string,
            listener: EventListenerOrEventListenerObject,
        ) => {
            mediaListeners.delete(listener);
        },
        addListener: () => undefined,
        removeListener: () => undefined,
        dispatchEvent: () => false,
    });
}

/** The window is resized across the `lg` breakpoint. */
function resizeTo(wide: boolean) {
    wideScreen = wide;

    act(() => {
        const event = new Event('change');

        for (const listener of mediaListeners) {
            if (typeof listener === 'function') {
                listener(event);
            } else {
                listener.handleEvent(event);
            }
        }
    });
}

// ---------------------------------------------------------------------------

function renderPage(overrides: Partial<SalesIndexProps> = {}) {
    const user = userEvent.setup();
    const props = salesIndexProps(overrides);
    const view = render(<SalesIndex {...props} />);

    return { user, props, ...view };
}

function filtersWith(overrides: Partial<SalesFilters>): SalesFilters {
    return { ...defaultSalesFilters, ...overrides };
}

/** The sales table (>= md); the stacked cards repeat its buttons below md. */
function table() {
    return screen.getByRole('table', { name: 'Sales' });
}

function emptyPage(filters: SalesFilters = defaultSalesFilters) {
    return {
        sales: paginate([], {
            path: '/sales',
            from: null,
            to: null,
            per_page: 10,
            total: 0,
            last_page: 1,
            links: [
                { url: null, label: '&laquo; Previous', active: false },
                { url: null, label: '1', active: true },
                { url: null, label: 'Next &raquo;', active: false },
            ],
        }),
        filters,
        totals: noSalesTotals,
    } satisfies Partial<SalesIndexProps>;
}

describe('sales/index page', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps({}, { url: '/sales', component: 'sales/index' });
        stubViewport(false);
    });

    afterEach(() => {
        vi.useRealTimers();
        window.matchMedia = originalMatchMedia;
        mediaListeners.clear();
    });

    describe('with sales (mockup values)', () => {
        it('has the mockup header', () => {
            renderPage();

            expect(
                screen.getByRole('heading', { level: 1, name: 'Sales' }),
            ).toBeInTheDocument();
            expect(
                screen.getByText(
                    'Every sale you record feeds the dashboard totals and the top sellers.',
                ),
            ).toBeVisible();
            expect(
                screen.getByRole('button', { name: 'Record sale' }),
            ).toBeEnabled();
        });

        it('shows the four totals of the filtered set', () => {
            renderPage();

            const totals = screen.getByRole('region', {
                name: 'Totals for the selected period',
            });

            expect(within(totals).getByText('€16,850.00')).toBeVisible();
            expect(within(totals).getByText('296')).toBeVisible();
            expect(within(totals).getByText('48')).toBeVisible();
            expect(within(totals).getByText('€351.04')).toBeVisible();
        });

        it('lists the sales newest first with formatted date, price and total', () => {
            renderPage();

            const rows = within(table()).getAllByRole('row');

            expect(rows).toHaveLength(11);

            const first = within(rows[1]);

            expect(first.getByText('7 Oct 2026')).toBeVisible();
            expect(
                first.getByRole('link', { name: 'Denim Jeans 5-pocket' }),
            ).toHaveAttribute('href', '/products/5');
            expect(first.getByText('PRD-JNS-005')).toBeVisible();
            expect(first.getByText('€79.00')).toBeVisible();
            expect(first.getByText('€474.00')).toBeVisible();
            expect(first.getByText('Boutique Lyon')).toBeVisible();
        });

        it('paginates the log', () => {
            renderPage();

            expect(screen.getByText('Showing 1–10 of 48')).toBeVisible();
            expect(
                screen.getByRole('link', { name: 'Next page' }),
            ).toHaveAttribute('href', '/sales?page=2');
        });

        it('reloads only the log when the page changes', () => {
            renderPage();

            expect(
                screen.getByRole('link', { name: 'Next page' }),
            ).toHaveAttribute('data-only', 'sales');
        });

        it('has no pagination for a single page', () => {
            renderPage({
                sales: paginate(recentSales, {
                    path: '/sales',
                    from: 1,
                    to: 5,
                    per_page: 10,
                    total: 5,
                    last_page: 1,
                }),
            });

            expect(
                screen.queryByRole('navigation', { name: 'Pagination' }),
            ).not.toBeInTheDocument();
        });

        it('has the filter bar with the server filters', () => {
            renderPage({
                filters: filtersWith({
                    search: 'lyon',
                    product_id: 5,
                    period: 'this_month',
                }),
            });

            expect(screen.getByRole('searchbox')).toHaveValue('lyon');
            expect(
                screen.getByRole('combobox', { name: 'Period' }),
            ).toHaveTextContent('This month');
            expect(
                screen.getByRole('combobox', { name: 'Product' }),
            ).toHaveTextContent('Denim Jeans 5-pocket');
        });
    });

    describe('filters in the URL', () => {
        it('requests the search 300 ms after the last keystroke', () => {
            vi.useFakeTimers();
            renderPage();

            fireEvent.change(screen.getByRole('searchbox'), {
                target: { value: 'web' },
            });

            expect(inertiaMock.router.get).not.toHaveBeenCalled();

            act(() => {
                vi.advanceTimersByTime(300);
            });

            expect(inertiaMock.router.get).toHaveBeenCalledOnce();
            expect(inertiaMock.router.get).toHaveBeenCalledWith(
                '/sales',
                { search: 'web' },
                VISIT_OPTIONS,
            );
        });

        it('requests the chosen period', async () => {
            const { user } = renderPage();

            await user.click(screen.getByRole('combobox', { name: 'Period' }));
            await user.click(screen.getByRole('option', { name: 'This year' }));

            expect(inertiaMock.router.get).toHaveBeenCalledExactlyOnceWith(
                '/sales',
                { period: 'this_year' },
                VISIT_OPTIONS,
            );
            // The select answers at once, before the server does.
            expect(
                screen.getByRole('combobox', { name: 'Period' }),
            ).toHaveTextContent('This year');
        });

        it('reloads only the log, the filters and the totals, never the record panel', async () => {
            const { user } = renderPage();

            await user.click(screen.getByRole('combobox', { name: 'Period' }));
            await user.click(screen.getByRole('option', { name: 'This year' }));

            // `record` and the picker lists are not requested: the form
            // someone is filling in keeps its state.
            expect(inertiaMock.router.get).toHaveBeenCalledWith(
                '/sales',
                expect.anything(),
                expect.objectContaining({
                    only: ['sales', 'filters', 'totals'],
                }),
            );
        });

        it('omits the default period from the query', async () => {
            const { user } = renderPage({
                filters: filtersWith({ period: 'last_7_days' }),
            });

            await user.click(screen.getByRole('combobox', { name: 'Period' }));
            await user.click(
                screen.getByRole('option', { name: 'Last 30 days' }),
            );

            expect(inertiaMock.router.get).toHaveBeenCalledExactlyOnceWith(
                '/sales',
                {},
                VISIT_OPTIONS,
            );
        });

        it('requests the chosen product by id', async () => {
            const { user } = renderPage();

            await user.click(screen.getByRole('combobox', { name: 'Product' }));
            await user.click(
                screen.getByRole('option', { name: 'Denim Jacket' }),
            );

            expect(inertiaMock.router.get).toHaveBeenCalledExactlyOnceWith(
                '/sales',
                { product_id: 1 },
                VISIT_OPTIONS,
            );
        });

        it('keeps the other filters and never sends a page', async () => {
            const { user } = renderPage({
                filters: filtersWith({ search: 'web', product_id: 2 }),
            });

            await user.click(screen.getByRole('combobox', { name: 'Period' }));
            await user.click(
                screen.getByRole('option', { name: 'This month' }),
            );

            expect(inertiaMock.router.get).toHaveBeenCalledExactlyOnceWith(
                '/sales',
                { search: 'web', product_id: 2, period: 'this_month' },
                VISIT_OPTIONS,
            );
        });

        it('clears every filter with Clear filters', async () => {
            const { user } = renderPage({
                filters: filtersWith({
                    search: 'web',
                    product_id: 2,
                    period: 'this_year',
                }),
            });

            await user.click(
                screen.getByRole('button', { name: 'Clear filters' }),
            );

            expect(inertiaMock.router.get).toHaveBeenCalledExactlyOnceWith(
                '/sales',
                {},
                VISIT_OPTIONS,
            );
            expect(screen.getByRole('searchbox')).toHaveValue('');
        });

        it('adopts filters that did not come from the controls (back button)', () => {
            const { rerender, props } = renderPage();

            rerender(
                <SalesIndex
                    {...props}
                    filters={filtersWith({
                        period: 'last_7_days',
                        product_id: 4,
                    })}
                />,
            );

            expect(
                screen.getByRole('combobox', { name: 'Period' }),
            ).toHaveTextContent('Last 7 days');
            expect(
                screen.getByRole('combobox', { name: 'Product' }),
            ).toHaveTextContent('Leather Tote');
        });
    });

    describe('Record a sale', () => {
        it('is closed until asked for', () => {
            renderPage();

            expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
            expect(
                screen.queryByRole('complementary', { hidden: true }),
            ).not.toBeInTheDocument();
        });

        it('opens in a sheet on a narrow screen', async () => {
            const { user } = renderPage();

            await user.click(
                screen.getByRole('button', { name: 'Record sale' }),
            );

            const dialog = screen.getByRole('dialog', {
                name: 'Record a sale',
            });

            expect(
                within(dialog).getByRole('combobox', { name: 'Product' }),
            ).toBeVisible();
            expect(
                screen.queryByRole('complementary', { hidden: true }),
            ).not.toBeInTheDocument();
        });

        it('opens beside the table on a wide screen', async () => {
            stubViewport(true);

            const { user } = renderPage();

            await user.click(
                screen.getByRole('button', { name: 'Record sale' }),
            );

            const aside = screen.getByRole('complementary', {
                name: 'Record a sale',
            });

            expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
            expect(aside.parentElement).toContainElement(
                screen.getByRole('region', { name: 'Sales list' }),
            );
            expect(
                within(aside).getByRole('combobox', { name: 'Product' }),
            ).toBeVisible();
        });

        it('keeps the list in view while the card is open', async () => {
            stubViewport(true);

            const { user } = renderPage();

            await user.click(
                screen.getByRole('button', { name: 'Record sale' }),
            );

            expect(table()).toBeVisible();
            expect(within(table()).getAllByRole('row')).toHaveLength(11);
        });

        it('follows a window resize between card and sheet', () => {
            stubViewport(true);

            renderPage({ record: { product_id: 1 } });

            expect(screen.getByRole('complementary')).toBeVisible();

            resizeTo(false);

            expect(
                screen.queryByRole('complementary', { hidden: true }),
            ).not.toBeInTheDocument();
            expect(
                screen.getByRole('dialog', { name: 'Record a sale' }),
            ).toBeVisible();

            resizeTo(true);

            expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
            expect(screen.getByRole('complementary')).toBeVisible();
        });

        it('opens on load when the page was asked to record a sale', () => {
            stubViewport(true);

            renderPage({ record: { product_id: null } });

            const aside = screen.getByRole('complementary', {
                name: 'Record a sale',
            });

            expect(
                within(aside).getByRole('combobox', { name: 'Product' }),
            ).toHaveTextContent('Choose a product…');
        });

        it('pre-selects the product of record.product_id and its price', () => {
            stubViewport(true);

            renderPage({ record: { product_id: 1 } });

            const aside = screen.getByRole('complementary', {
                name: 'Record a sale',
            });

            expect(
                within(aside).getByRole('combobox', { name: 'Product' }),
            ).toHaveTextContent('Denim Jacket · PRD-JKT-001');
            expect(within(aside).getByLabelText('Unit price')).toHaveValue(
                '89.0000',
            );
            expect(within(aside).getByLabelText('Date')).toHaveValue(
                '2026-10-08',
            );
        });

        it('opens in the sheet on load on a narrow screen', () => {
            renderPage({ record: { product_id: 2 } });

            expect(
                within(
                    screen.getByRole('dialog', { name: 'Record a sale' }),
                ).getByRole('combobox', { name: 'Product' }),
            ).toHaveTextContent('Classic White T-shirt');
        });

        it('opens when a visit asks for it while the page is already showing', () => {
            stubViewport(true);

            const { rerender, props } = renderPage();

            expect(
                screen.queryByRole('complementary', { hidden: true }),
            ).not.toBeInTheDocument();

            rerender(<SalesIndex {...props} record={{ product_id: 3 }} />);

            expect(
                within(
                    screen.getByRole('complementary', {
                        name: 'Record a sale',
                    }),
                ).getByRole('combobox', { name: 'Product' }),
            ).toHaveTextContent('Canvas Low Sneaker');
        });

        it('stays open when a filter change drops `record` from the URL', () => {
            stubViewport(true);

            const { rerender, props } = renderPage({
                record: { product_id: 1 },
            });

            rerender(<SalesIndex {...props} record={null} />);

            expect(
                screen.getByRole('complementary', { name: 'Record a sale' }),
            ).toBeVisible();
        });

        it('closes with Cancel and puts focus back on Record sale', async () => {
            stubViewport(true);

            const { user } = renderPage();
            const open = screen.getByRole('button', { name: 'Record sale' });

            await user.click(open);
            await user.click(screen.getByRole('button', { name: 'Cancel' }));

            expect(
                screen.queryByRole('complementary', { hidden: true }),
            ).not.toBeInTheDocument();
            expect(open).toHaveFocus();
        });

        it('closes the sheet with Escape', async () => {
            const { user } = renderPage({ record: { product_id: null } });

            await user.keyboard('{Escape}');

            await waitFor(() =>
                expect(screen.queryByRole('dialog')).not.toBeInTheDocument(),
            );
        });

        it('posts the sale to the sales route and closes after success', async () => {
            stubViewport(true);

            const { user } = renderPage({ record: { product_id: 1 } });

            await user.type(screen.getByLabelText('Quantity'), '6');
            await user.click(screen.getByRole('button', { name: 'Save sale' }));

            expect(inertiaMock.submissions).toHaveLength(1);
            expect(inertiaMock.submissions[0]).toMatchObject({
                action: '/sales',
                method: 'post',
                data: {
                    product_id: 1,
                    quantity: '6',
                    unit_price: '89.0000',
                    sold_on: '2026-10-08',
                    note: '',
                },
                options: {
                    preserveScroll: true,
                },
            });

            const options = inertiaMock.submissions[0].options as {
                onSuccess: () => void;
            };

            act(() => {
                options.onSuccess();
            });

            expect(
                screen.queryByRole('complementary', { hidden: true }),
            ).not.toBeInTheDocument();
        });

        it('does not add a second form for a second click on Record sale', async () => {
            stubViewport(true);

            const { user } = renderPage();

            await user.click(
                screen.getByRole('button', { name: 'Record sale' }),
            );
            await user.type(screen.getByLabelText('Quantity'), '7');
            await user.click(
                screen.getByRole('button', { name: 'Record sale' }),
            );

            expect(screen.getAllByRole('complementary')).toHaveLength(1);
            expect(screen.getByLabelText('Quantity')).toHaveValue('7');
        });
    });

    describe('Edit a sale', () => {
        const editDenimJacket = 'Edit sale of Denim Jacket on 5 Oct 2026';

        it('opens the form pre-filled from the sale', async () => {
            stubViewport(true);

            const { user } = renderPage();

            await user.click(
                within(table()).getByRole('button', { name: editDenimJacket }),
            );

            const aside = screen.getByRole('complementary', {
                name: 'Edit sale',
            });

            expect(
                within(aside).getByRole('combobox', { name: 'Product' }),
            ).toHaveTextContent('Denim Jacket · PRD-JKT-001');
            expect(within(aside).getByLabelText('Quantity')).toHaveValue('4');
            expect(within(aside).getByLabelText('Unit price')).toHaveValue(
                '89.0000',
            );
            expect(within(aside).getByLabelText('Date')).toHaveValue(
                '2026-10-05',
            );
            expect(within(aside).getByLabelText(/^Note/)).toHaveValue(
                'Web shop',
            );
        });

        it('puts the changes to the sale', async () => {
            stubViewport(true);

            const { user } = renderPage();

            await user.click(
                within(table()).getByRole('button', { name: editDenimJacket }),
            );
            await user.clear(screen.getByLabelText('Quantity'));
            await user.type(screen.getByLabelText('Quantity'), '5');
            await user.click(screen.getByRole('button', { name: 'Save sale' }));

            expect(inertiaMock.submissions).toHaveLength(1);
            expect(inertiaMock.submissions[0]).toMatchObject({
                action: '/sales/57',
                method: 'put',
                data: { product_id: 1, quantity: '5' },
                options: {
                    preserveScroll: true,
                },
            });
        });

        it('switches to another sale when Edit is clicked on it', async () => {
            stubViewport(true);

            const { user } = renderPage();

            await user.click(
                within(table()).getByRole('button', { name: editDenimJacket }),
            );
            await user.click(
                within(table()).getByRole('button', {
                    name: 'Edit sale of Leather Tote on 6 Oct 2026',
                }),
            );

            const aside = screen.getByRole('complementary', {
                name: 'Edit sale',
            });

            expect(
                within(aside).getByRole('combobox', { name: 'Product' }),
            ).toHaveTextContent('Leather Tote');
            expect(within(aside).getByLabelText('Quantity')).toHaveValue('2');
        });

        it('opens in the sheet on a narrow screen', async () => {
            const { user } = renderPage();

            await user.click(
                within(table()).getByRole('button', { name: editDenimJacket }),
            );

            expect(
                screen.getByRole('dialog', { name: 'Edit sale' }),
            ).toBeVisible();
        });

        it('keeps a sale of an inactive product editable', async () => {
            stubViewport(true);

            const { user } = renderPage({
                sales: paginate(
                    [saleOfInactiveProduct, ...salesPage.slice(0, 4)],
                    {
                        path: '/sales',
                        from: 1,
                        to: 5,
                        per_page: 10,
                        total: 5,
                        last_page: 1,
                    },
                ),
            });

            await user.click(
                within(table()).getByRole('button', {
                    name: 'Edit sale of Canvas Chore Jacket on 9 Sep 2026',
                }),
            );

            expect(
                within(
                    screen.getByRole('complementary', { name: 'Edit sale' }),
                ).getByRole('combobox', { name: 'Product' }),
            ).toHaveTextContent(
                'Canvas Chore Jacket · PRD-JKT-006 (not active)',
            );
        });
    });

    describe('Delete a sale', () => {
        const deleteDenimJacket = 'Delete sale of Denim Jacket on 5 Oct 2026';

        it('asks for confirmation, naming the sale', async () => {
            const { user } = renderPage();

            await user.click(
                within(table()).getByRole('button', {
                    name: deleteDenimJacket,
                }),
            );

            const dialog = screen.getByRole('alertdialog', {
                name: 'Delete sale?',
            });

            expect(dialog).toHaveTextContent(
                'The sale of 4 × Denim Jacket on 5 Oct 2026 will be removed',
            );
            expect(inertiaMock.router.delete).not.toHaveBeenCalled();
        });

        it('deletes nothing on Cancel', async () => {
            const { user } = renderPage();

            await user.click(
                within(table()).getByRole('button', {
                    name: deleteDenimJacket,
                }),
            );
            await user.click(screen.getByRole('button', { name: 'Cancel' }));

            expect(inertiaMock.router.delete).not.toHaveBeenCalled();
            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
        });

        it('deletes the sale by id, keeping the scroll position', async () => {
            const { user } = renderPage();

            await user.click(
                within(table()).getByRole('button', {
                    name: deleteDenimJacket,
                }),
            );
            await user.click(
                screen.getByRole('button', { name: 'Delete sale' }),
            );

            expect(inertiaMock.router.delete).toHaveBeenCalledOnce();
            expect(inertiaMock.router.delete).toHaveBeenCalledWith(
                '/sales/57',
                expect.objectContaining({ preserveScroll: true }),
            );
        });

        it('shows progress while deleting and closes when the request finishes', async () => {
            const { user } = renderPage();

            await user.click(
                within(table()).getByRole('button', {
                    name: deleteDenimJacket,
                }),
            );
            await user.click(
                screen.getByRole('button', { name: 'Delete sale' }),
            );

            const options = inertiaMock.router.delete.mock.calls[0][1] as {
                onStart: () => void;
                onFinish: () => void;
            };

            act(() => {
                options.onStart();
            });

            expect(
                screen.getByRole('button', { name: 'Cancel' }),
            ).toBeDisabled();
            expect(
                screen.getByRole('button', { name: /Delete sale/ }),
            ).toBeDisabled();

            act(() => {
                options.onFinish();
            });

            await waitFor(() =>
                expect(
                    screen.queryByRole('alertdialog'),
                ).not.toBeInTheDocument(),
            );
        });

        it('hands focus back to the Delete button afterwards', async () => {
            const { user } = renderPage();
            const trigger = within(table()).getByRole('button', {
                name: deleteDenimJacket,
            });

            await user.click(trigger);
            await user.click(screen.getByRole('button', { name: 'Cancel' }));

            await waitFor(() => expect(trigger).toHaveFocus());
        });

        it('closes the form that was editing the deleted sale', async () => {
            stubViewport(true);

            const { user } = renderPage();

            await user.click(
                within(table()).getByRole('button', {
                    name: 'Edit sale of Denim Jacket on 5 Oct 2026',
                }),
            );
            await user.click(
                within(table()).getByRole('button', {
                    name: deleteDenimJacket,
                }),
            );
            await user.click(
                screen.getByRole('button', { name: 'Delete sale' }),
            );

            const options = inertiaMock.router.delete.mock.calls[0][1] as {
                onSuccess: () => void;
                onFinish: () => void;
            };

            // Inertia runs onSuccess, then onFinish.
            act(() => {
                options.onSuccess();
                options.onFinish();
            });

            await waitFor(() =>
                expect(
                    screen.queryByRole('alertdialog'),
                ).not.toBeInTheDocument(),
            );
            expect(
                screen.queryByRole('complementary', { hidden: true }),
            ).not.toBeInTheDocument();
        });

        it('keeps a form that is editing another sale', async () => {
            stubViewport(true);

            const { user } = renderPage();

            await user.click(
                within(table()).getByRole('button', {
                    name: 'Edit sale of Leather Tote on 6 Oct 2026',
                }),
            );
            await user.click(
                within(table()).getByRole('button', {
                    name: deleteDenimJacket,
                }),
            );
            await user.click(
                screen.getByRole('button', { name: 'Delete sale' }),
            );

            const options = inertiaMock.router.delete.mock.calls[0][1] as {
                onSuccess: () => void;
                onFinish: () => void;
            };

            act(() => {
                options.onSuccess();
                options.onFinish();
            });

            await waitFor(() =>
                expect(
                    screen.queryByRole('alertdialog'),
                ).not.toBeInTheDocument(),
            );
            expect(
                screen.getByRole('complementary', { name: 'Edit sale' }),
            ).toBeVisible();
        });
    });

    describe('empty states', () => {
        it('invites the first sale when nothing was recorded', async () => {
            const { user } = renderPage(emptyPage());

            expect(
                screen.getByRole('heading', { name: 'No sales yet' }),
            ).toBeVisible();
            expect(
                screen.getByText(
                    'Record a sale to see it here and in the dashboard totals.',
                ),
            ).toBeVisible();
            expect(screen.queryByRole('table')).not.toBeInTheDocument();
            expect(
                screen.queryByRole('navigation', { name: 'Pagination' }),
            ).not.toBeInTheDocument();

            const buttons = screen.getAllByRole('button', {
                name: 'Record sale',
            });

            // The header button and the call to action open the same form.
            expect(buttons).toHaveLength(2);

            await user.click(buttons[1]);

            expect(
                screen.getByRole('dialog', { name: 'Record a sale' }),
            ).toBeVisible();
        });

        it('shows the average as an em dash without sales', () => {
            renderPage(emptyPage());

            const totals = screen.getByRole('region', {
                name: 'Totals for the selected period',
            });

            expect(within(totals).getByText('—')).toBeVisible();
            expect(within(totals).getByText('€0.00')).toBeVisible();
        });

        it('explains a filter that matches nothing and offers to clear it', async () => {
            const { user } = renderPage(
                emptyPage(filtersWith({ search: 'zzz' })),
            );

            expect(
                screen.getByRole('heading', {
                    name: 'No sales match your filters',
                }),
            ).toBeVisible();
            expect(screen.queryByRole('table')).not.toBeInTheDocument();
            // The filter bar stays, so the filter can be changed.
            expect(screen.getByRole('searchbox')).toHaveValue('zzz');

            const clear = screen.getAllByRole('button', {
                name: 'Clear filters',
            });

            expect(clear).toHaveLength(2);

            await user.click(clear[1]);

            expect(inertiaMock.router.get).toHaveBeenCalledExactlyOnceWith(
                '/sales',
                {},
                VISIT_OPTIONS,
            );
        });

        it('treats another period as a filter too', () => {
            renderPage(emptyPage(filtersWith({ period: 'last_7_days' })));

            expect(
                screen.getByRole('heading', {
                    name: 'No sales match your filters',
                }),
            ).toBeVisible();
        });
    });
});
