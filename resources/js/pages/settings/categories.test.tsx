import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentType } from 'react';
import { toast } from 'sonner';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import MaterialCategories from '@/pages/settings/material-categories';
import ProductCategories from '@/pages/settings/product-categories';
import {
    archived,
    referenceMaterialCategories,
    referenceProductCategories,
    referenceRow,
} from '@/test/fixtures/reference';
import {
    inertiaMock,
    resetInertiaMock,
    setFormState,
    setPageProps,
} from '@/test/inertia';
import { sizeOptionsFixture } from '@/test/fixtures/products';
import type { ReferenceOption, ReferenceRow } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

vi.mock('sonner', () => ({ toast: { error: vi.fn(), success: vi.fn() } }));

/** Product categories also receive what they suggest and the workshop's sizes (INV-18). */
function ProductCategoriesPage({ categories }: { categories: ReferenceRow[] }) {
    return (
        <ProductCategories
            categories={categories.map((row) => ({
                suggested_size_ids: [16],
                suggests: 'One size',
                ...row,
            }))}
            sizes={sizeOptionsFixture}
        />
    );
}

type Suite = {
    Page: ComponentType<{ categories: ReferenceRow[] }>;
    /** The page heading and the table's name. */
    title: string;
    singular: string;
    base: string;
    options: ReferenceOption[];
    usedBy: { one: string; many: string };
    /** What an edit also puts: the suggested sizes of product categories. */
    editExtra: Record<string, unknown>;
    /** Columns between Code and Used by: only product categories have Suggests. */
    extraHeaders: string[];
};

const suites: Suite[] = [
    {
        Page: MaterialCategories,
        title: 'Material categories',
        singular: 'material category',
        base: '/settings/material-categories',
        options: referenceMaterialCategories,
        usedBy: { one: 'material', many: 'materials' },
        editExtra: {},
        extraHeaders: [],
    },
    {
        Page: ProductCategoriesPage,
        title: 'Product categories',
        singular: 'product category',
        base: '/settings/product-categories',
        options: referenceProductCategories,
        usedBy: { one: 'product', many: 'products' },
        editExtra: { suggested_sizes: [16] },
        extraHeaders: ['Suggests'],
    },
];

describe.each(suites)('settings/$title page', (suite) => {
    const {
        Page,
        title,
        singular,
        base,
        options,
        usedBy,
        editExtra,
        extraHeaders,
    } = suite;
    const first = options[0]; // id 1
    const second = options[1]; // id 2

    function table() {
        return screen.getByRole('table', { name: title });
    }

    function rowOf(name: string): HTMLElement {
        const row = within(table())
            .getAllByRole('row')
            .find((candidate) =>
                within(candidate).queryByText(name, { exact: true }),
            );

        if (!row) {
            throw new Error(`No table row for ${name}`);
        }

        return row;
    }

    beforeEach(() => {
        resetInertiaMock();
        vi.mocked(toast.error).mockClear();
        setPageProps({}, { url: base });
    });

    describe('the table', () => {
        it('has a heading, an Add button and the column headers', () => {
            render(<Page categories={[referenceRow(first)]} />);

            expect(
                screen.getByRole('heading', {
                    level: 1,
                    name: `${title} settings`,
                }),
            ).toBeInTheDocument();
            expect(
                screen.getByRole('button', { name: `Add ${singular}` }),
            ).toBeInTheDocument();
            expect(
                within(table())
                    .getAllByRole('columnheader')
                    .map((header) => header.textContent),
            ).toEqual([
                'Name',
                'Code',
                ...extraHeaders,
                'Used by',
                'Status',
                'Actions',
            ]);
        });

        it('shows code, usage and status per row', () => {
            render(
                <Page
                    categories={[
                        referenceRow(first, 3),
                        referenceRow(second, 1),
                        referenceRow(options[2]),
                    ]}
                />,
            );

            const firstRow = within(rowOf(first.name));

            expect(firstRow.getByText(first.code)).toBeInTheDocument();
            expect(firstRow.getByText(`3 ${usedBy.many}`)).toBeInTheDocument();
            expect(firstRow.getByText('Active')).toBeInTheDocument();
            expect(
                within(rowOf(second.name)).getByText(`1 ${usedBy.one}`),
            ).toBeInTheDocument();
            expect(
                within(rowOf(options[2].name)).getByText('Not used'),
            ).toBeInTheDocument();
        });

        it('lists archived rows after the active ones, marked Archived', () => {
            render(
                <Page
                    categories={[
                        referenceRow(archived(first)),
                        referenceRow(second),
                        referenceRow(options[2]),
                    ]}
                />,
            );

            expect(
                within(table())
                    .getAllByRole('row')
                    .slice(1)
                    .map(
                        (row) =>
                            within(row).getAllByRole('cell')[0].textContent,
                    ),
            ).toEqual([second.name, options[2].name, first.name]);
            expect(
                within(rowOf(first.name)).getByText('Archived'),
            ).toBeInTheDocument();
        });

        it('shows an empty state with an Add button', async () => {
            const user = userEvent.setup();

            render(<Page categories={[]} />);

            expect(
                screen.getByRole('heading', {
                    name: `No ${title.toLowerCase()} yet`,
                }),
            ).toBeInTheDocument();

            await user.click(
                screen.getAllByRole('button', { name: `Add ${singular}` })[1],
            );

            expect(
                screen.getByRole('dialog', { name: `Add ${singular}` }),
            ).toBeInTheDocument();
        });
    });

    describe('row actions', () => {
        it('offers Edit and Archive on an active row', () => {
            render(<Page categories={[referenceRow(first)]} />);

            const row = within(rowOf(first.name));

            expect(
                row.getByRole('button', { name: `Edit ${first.name}` }),
            ).toBeEnabled();
            expect(
                row.getByRole('button', { name: `Archive ${first.name}` }),
            ).toBeEnabled();
            expect(
                row.queryByRole('button', { name: `Delete ${first.name}` }),
            ).not.toBeInTheDocument();
        });

        it('archives and restores through PATCH', async () => {
            const user = userEvent.setup();

            render(
                <Page
                    categories={[
                        referenceRow(first),
                        referenceRow(archived(second)),
                    ]}
                />,
            );

            await user.click(
                within(rowOf(first.name)).getByRole('button', {
                    name: `Archive ${first.name}`,
                }),
            );
            await user.click(
                within(rowOf(second.name)).getByRole('button', {
                    name: `Restore ${second.name}`,
                }),
            );

            expect(inertiaMock.router.patch).toHaveBeenNthCalledWith(
                1,
                `${base}/1/archive`,
                {},
                expect.objectContaining({
                    preserveScroll: true,
                }),
            );
            expect(inertiaMock.router.patch).toHaveBeenNthCalledWith(
                2,
                `${base}/2/restore`,
                {},
                expect.objectContaining({
                    preserveScroll: true,
                }),
            );
        });

        it('shows the refusal of a restore as an error toast', async () => {
            const user = userEvent.setup();

            render(<Page categories={[referenceRow(archived(first))]} />);
            await user.click(
                within(rowOf(first.name)).getByRole('button', {
                    name: `Restore ${first.name}`,
                }),
            );

            const patchOptions = inertiaMock.router.patch.mock.calls[0][2] as {
                onError: (errors: Record<string, string>) => void;
            };
            const message = `An active ${singular} is already named "${first.name}". Rename one of them first.`;

            patchOptions.onError({ name: message });

            expect(toast.error).toHaveBeenCalledExactlyOnceWith(message);
        });
    });

    describe('delete', () => {
        it('is disabled while records use the row, naming how many', () => {
            render(<Page categories={[referenceRow(archived(first), 4)]} />);

            const row = within(rowOf(first.name));
            const remove = row.getByRole('button', {
                name: `Delete ${first.name}`,
            });

            expect(remove).toBeDisabled();
            expect(
                row.getByText(`Used by 4 ${usedBy.many}`),
            ).toBeInTheDocument();
            expect(remove).toHaveAccessibleDescription(
                `Used by 4 ${usedBy.many}`,
            );
        });

        it('asks for confirmation, then deletes an unused archived row', async () => {
            const user = userEvent.setup();

            render(<Page categories={[referenceRow(archived(first))]} />);
            await user.click(
                within(rowOf(first.name)).getByRole('button', {
                    name: `Delete ${first.name}`,
                }),
            );

            const dialog = screen.getByRole('alertdialog', {
                name: `Delete ${singular}?`,
            });

            expect(inertiaMock.router.delete).not.toHaveBeenCalled();

            await user.click(
                within(dialog).getByRole('button', {
                    name: `Delete ${singular}`,
                }),
            );

            expect(inertiaMock.router.delete).toHaveBeenCalledExactlyOnceWith(
                `${base}/1`,
                expect.objectContaining({
                    preserveScroll: true,
                }),
            );
        });
    });

    describe('create dialog', () => {
        async function openCreate() {
            const user = userEvent.setup();

            render(<Page categories={[referenceRow(first)]} />);
            await user.click(
                screen.getByRole('button', { name: `Add ${singular}` }),
            );

            return {
                user,
                dialog: within(
                    screen.getByRole('dialog', { name: `Add ${singular}` }),
                ),
            };
        }

        it('asks for a code (with a hint), a name and an optional position', async () => {
            const { dialog } = await openCreate();

            expect(dialog.getByLabelText(/^Code/)).toHaveAccessibleDescription(
                /Lowercase letters, digits and _ only, up to 32 characters/,
            );
            expect(dialog.getByLabelText(/^Name/)).toBeInTheDocument();
            expect(dialog.getByLabelText(/^Position/)).toBeInTheDocument();
        });

        it('posts code and name and leaves an empty position out', async () => {
            const { user, dialog } = await openCreate();

            await user.type(dialog.getByLabelText(/^Code/), 'elastic');
            await user.type(dialog.getByLabelText(/^Name/), 'Elastic');
            await user.click(
                dialog.getByRole('button', { name: `Save ${singular}` }),
            );

            expect(inertiaMock.submissions).toHaveLength(1);
            expect(inertiaMock.submissions[0]).toMatchObject({
                action: base,
                method: 'post',
                options: {
                    preserveScroll: true,
                },
            });
            expect(inertiaMock.submissions[0].data).toEqual({
                code: 'elastic',
                name: 'Elastic',
            });
        });

        it('sends the position when one is typed', async () => {
            const { user, dialog } = await openCreate();

            await user.type(dialog.getByLabelText(/^Code/), 'elastic');
            await user.type(dialog.getByLabelText(/^Name/), 'Elastic');
            await user.type(dialog.getByLabelText(/^Position/), '2');
            await user.click(
                dialog.getByRole('button', { name: `Save ${singular}` }),
            );

            expect(inertiaMock.submissions[0].data).toEqual({
                code: 'elastic',
                name: 'Elastic',
                position: '2',
            });
        });

        it('shows server errors under the fields and keeps the dialog open', async () => {
            setFormState({
                errors: {
                    code: 'The code has already been taken.',
                    name: 'The name field is required.',
                },
            });

            const { dialog } = await openCreate();

            const code = dialog.getByLabelText(/^Code/);

            expect(code).toBeInvalid();
            expect(code).toHaveAccessibleDescription(
                expect.stringContaining('The code has already been taken.'),
            );
            expect(dialog.getByLabelText(/^Name/)).toBeInvalid();
            expect(dialog.getByLabelText(/^Name/)).toHaveAccessibleDescription(
                'The name field is required.',
            );
            expect(dialog.getByLabelText(/^Position/)).toBeValid();
            expect(code).toHaveFocus();
            expect(
                screen.getByRole('dialog', { name: `Add ${singular}` }),
            ).toBeInTheDocument();
        });

        it('closes when the write succeeds', async () => {
            const { user, dialog } = await openCreate();

            await user.type(dialog.getByLabelText(/^Code/), 'elastic');
            await user.type(dialog.getByLabelText(/^Name/), 'Elastic');
            await user.click(
                dialog.getByRole('button', { name: `Save ${singular}` }),
            );

            const submitted = inertiaMock.submissions[0].options as {
                onSuccess: () => void;
            };

            act(() => submitted.onSuccess());

            expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
        });
    });

    describe('edit dialog', () => {
        async function openEdit() {
            const user = userEvent.setup();

            render(<Page categories={[referenceRow(second, 2)]} />);
            await user.click(
                within(rowOf(second.name)).getByRole('button', {
                    name: `Edit ${second.name}`,
                }),
            );

            return {
                user,
                dialog: within(
                    screen.getByRole('dialog', { name: `Edit ${second.name}` }),
                ),
            };
        }

        it('pre-fills name and position and has no code field', async () => {
            const { dialog } = await openEdit();

            expect(dialog.getByLabelText(/^Name/)).toHaveValue(second.name);
            expect(dialog.getByLabelText(/^Position/)).toHaveValue('2');
            expect(dialog.queryByLabelText(/^Code/)).not.toBeInTheDocument();
            expect(
                dialog.getByText(`Code ${second.code} can't be changed.`),
            ).toBeInTheDocument();
        });

        it('puts name and position to the row, without the code', async () => {
            const { user, dialog } = await openEdit();

            await user.clear(dialog.getByLabelText(/^Name/));
            await user.type(dialog.getByLabelText(/^Name/), 'Renamed');
            await user.click(
                dialog.getByRole('button', { name: `Save ${singular}` }),
            );

            expect(inertiaMock.submissions).toHaveLength(1);
            expect(inertiaMock.submissions[0]).toMatchObject({
                action: `${base}/2`,
                method: 'put',
                options: {
                    preserveScroll: true,
                },
            });
            expect(inertiaMock.submissions[0].data).toEqual({
                name: 'Renamed',
                position: '2',
                ...editExtra,
            });
        });
    });
});

describe('settings/Product categories: suggested sizes', () => {
    const base = '/settings/product-categories';
    const apparel = {
        ...referenceRow(referenceProductCategories[0], 2),
        suggested_size_ids: [1, 2, 3, 4],
        suggests: 'S, M, L, XL',
    };
    const other = {
        ...referenceRow(referenceProductCategories[5]),
        suggested_size_ids: [16],
        suggests: 'One size',
    };

    beforeEach(() => {
        resetInertiaMock();
        setPageProps({}, { url: base });
    });

    function renderPage() {
        render(
            <ProductCategories
                categories={[apparel, other]}
                sizes={sizeOptionsFixture}
            />,
        );
    }

    async function openEdit(name: string) {
        const user = userEvent.setup();

        renderPage();
        await user.click(
            within(
                screen.getByRole('table', { name: 'Product categories' }),
            ).getByRole('button', { name: `Edit ${name}` }),
        );

        return {
            user,
            dialog: within(
                screen.getByRole('dialog', { name: `Edit ${name}` }),
            ),
        };
    }

    it('shows the Suggests line of each category, in the table and the phone cards', () => {
        renderPage();

        const table = within(
            screen.getByRole('table', { name: 'Product categories' }),
        );
        const cards = within(
            screen.getByRole('list', { name: 'Product categories' }),
        );

        expect(
            table.getAllByRole('columnheader').map((cell) => cell.textContent),
        ).toContain('Suggests');
        expect(table.getByText('S, M, L, XL')).toBeInTheDocument();
        expect(table.getByText('One size')).toBeInTheDocument();
        expect(cards.getByText('S, M, L, XL')).toBeInTheDocument();
    });

    it('has the Suggested sizes field in Edit with the chosen sizes pressed, and not in Add', async () => {
        const { user, dialog } = await openEdit('Apparel');

        expect(
            dialog.getByRole('group', { name: /^Suggested sizes/ }),
        ).toHaveAccessibleDescription(
            'Add product pre-selects these sizes when you choose this category.',
        );
        expect(dialog.getByRole('button', { name: 'M' })).toHaveAttribute(
            'aria-pressed',
            'true',
        );
        expect(dialog.getByRole('button', { name: '36' })).toHaveAttribute(
            'aria-pressed',
            'false',
        );
        expect(dialog.getByText('4 sizes chosen')).toBeInTheDocument();

        await user.click(dialog.getByRole('button', { name: 'Cancel' }));
        await user.click(
            screen.getByRole('button', { name: 'Add product category' }),
        );

        expect(
            screen.queryByText('Suggested sizes', { exact: false }),
        ).not.toBeInTheDocument();
    });

    it('puts suggested_sizes with the name', async () => {
        const { user, dialog } = await openEdit('Apparel');

        await user.click(dialog.getByRole('button', { name: 'XL' }));
        await user.click(dialog.getByRole('button', { name: '36' }));
        await user.click(
            dialog.getByRole('button', { name: 'Save product category' }),
        );

        expect(inertiaMock.submissions[0]).toMatchObject({
            action: `${base}/1`,
            method: 'put',
        });
        expect(inertiaMock.submissions[0].data).toEqual({
            name: 'Apparel',
            position: '1',
            suggested_sizes: [1, 2, 3, 5],
        });
    });

    it('shows the suggested_sizes error under the field and focuses its first button', async () => {
        setFormState({
            errors: { suggested_sizes: 'Choose at least one size.' },
        });

        const { dialog } = await openEdit('Apparel');

        expect(
            dialog.getByText('Choose at least one size.'),
        ).toBeInTheDocument();
        expect(
            dialog.getByRole('group', { name: /^Suggested sizes/ }),
        ).toHaveAccessibleDescription(
            expect.stringContaining('Choose at least one size.'),
        );
        expect(dialog.getByRole('button', { name: 'S' })).toHaveFocus();
    });

    it('keeps the title and the buttons in view: the window is capped at the screen and only the fields scroll', async () => {
        const { dialog } = await openEdit('Apparel');

        const scrolling = dialog
            .getByLabelText(/^Name/)
            .closest('.overflow-y-auto');

        expect(screen.getByRole('dialog')).toHaveClass(
            'max-h-[calc(100dvh-2rem)]',
        );
        expect(scrolling).not.toBeNull();
        expect(scrolling).toContainElement(
            dialog.getByRole('group', { name: /^Suggested sizes/ }),
        );

        for (const outside of [
            dialog.getByRole('heading', { name: 'Edit Apparel' }),
            dialog.getByRole('button', { name: 'Cancel' }),
            dialog.getByRole('button', { name: 'Save product category' }),
        ]) {
            expect(scrolling).not.toContainElement(outside);
        }
    });
});
