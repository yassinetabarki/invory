import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { toast } from 'sonner';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import Units from '@/pages/settings/units';
import {
    archived,
    customUnit,
    dimensionOptions,
    referenceUnits,
    unitRow,
} from '@/test/fixtures/reference';
import {
    inertiaMock,
    resetInertiaMock,
    setFormState,
    setPageProps,
} from '@/test/inertia';
import type { UnitRow, UnitsSettingsProps } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

vi.mock('sonner', () => ({ toast: { error: vi.fn(), success: vi.fn() } }));

function system(code: string): UnitRow {
    const found = referenceUnits.find((unit) => unit.code === code);

    if (!found) {
        throw new Error(`No fixture unit ${code}`);
    }

    return unitRow(found);
}

const metre = { ...system('m'), usage_count: 7 };
const yard = system('yd');
const roll = system('roll');
const inch = unitRow(customUnit({ id: 20, code: 'in', name: 'Inch' }));
const usedFoot = unitRow(
    customUnit({
        id: 21,
        code: 'ft',
        name: 'Foot',
        factor_to_base: '0.304800000000',
    }),
    2,
);

function props(units: UnitRow[]): UnitsSettingsProps {
    return { units, dimensions: dimensionOptions };
}

function table() {
    return screen.getByRole('table', { name: 'Units' });
}

function tableRow(name: string) {
    return within(table())
        .getAllByRole('row')
        .find((row) => within(row).queryByText(name, { exact: true }));
}

function rowOf(name: string): HTMLElement {
    const row = tableRow(name);

    if (!row) {
        throw new Error(`No table row for ${name}`);
    }

    return row;
}

describe('settings/units page', () => {
    beforeEach(() => {
        resetInertiaMock();
        vi.mocked(toast.error).mockClear();
        setPageProps(
            {},
            { url: '/settings/units', component: 'settings/units' },
        );
    });

    describe('the table', () => {
        it('has a heading, an Add unit button and the column headers', () => {
            render(<Units {...props([metre])} />);

            expect(
                screen.getByRole('heading', {
                    level: 1,
                    name: 'Units settings',
                }),
            ).toBeInTheDocument();
            expect(
                screen.getByRole('heading', { level: 2, name: 'Units' }),
            ).toBeInTheDocument();
            expect(
                screen.getByRole('button', { name: 'Add unit' }),
            ).toBeInTheDocument();

            expect(
                within(table())
                    .getAllByRole('columnheader')
                    .map((header) => header.textContent),
            ).toEqual([
                'Name',
                'Code',
                'Dimension',
                'Factor',
                'Used by',
                'Status',
                'Actions',
            ]);
        });

        it('shows code, dimension, factor against the base unit, usage and status', () => {
            render(<Units {...props([metre, yard, roll])} />);

            const yardRow = within(rowOf('Yard'));

            expect(yardRow.getByText('yd')).toBeInTheDocument();
            expect(yardRow.getByText('Length')).toBeInTheDocument();
            // Trimmed from "0.914400000000", as text.
            expect(yardRow.getByText('1 yd = 0.9144 m')).toBeInTheDocument();
            expect(yardRow.getByText('Not used')).toBeInTheDocument();
            expect(yardRow.getByText('Active')).toBeInTheDocument();

            const metreRow = within(rowOf('Metre'));

            expect(metreRow.getByText('1 m = 1 m')).toBeInTheDocument();
            expect(metreRow.getByText('7 materials')).toBeInTheDocument();

            expect(
                within(rowOf('Roll')).getByText('Varies per material'),
            ).toBeInTheDocument();
        });

        it('says "1 material" in the singular', () => {
            render(<Units {...props([{ ...yard, usage_count: 1 }])} />);

            expect(
                within(rowOf('Yard')).getByText('1 material'),
            ).toBeInTheDocument();
        });

        it('lists active units first and archived ones after, with an Archived badge', () => {
            render(
                <Units
                    {...props([
                        { ...unitRow(archived(referenceUnits[1])) },
                        metre,
                        yard,
                        { ...unitRow(archived(referenceUnits[3])) },
                    ])}
                />,
            );

            const names = within(table())
                .getAllByRole('row')
                .slice(1)
                .map((row) => within(row).getAllByRole('cell')[0].textContent);

            expect(names).toEqual([
                'Metre',
                'Yard',
                'Centimetre',
                'Square metre',
            ]);
            expect(
                within(rowOf('Centimetre')).getByText('Archived'),
            ).toBeInTheDocument();
            expect(
                within(rowOf('Metre')).queryByText('Archived'),
            ).not.toBeInTheDocument();
        });

        it('repeats the rows as cards for narrow screens', () => {
            render(<Units {...props([metre, archived(yard)])} />);

            const cards = within(screen.getByRole('list', { name: 'Units' }));

            expect(cards.getAllByRole('listitem')).toHaveLength(2);
            expect(cards.getByText('1 m = 1 m')).toBeInTheDocument();
            expect(
                cards.getByRole('button', { name: 'Edit Metre' }),
            ).toBeInTheDocument();
            expect(
                cards.getByRole('button', { name: 'Restore Yard' }),
            ).toBeInTheDocument();
        });

        it('shows an empty state with a way to add the first unit', async () => {
            const user = userEvent.setup();

            render(<Units {...props([])} />);

            expect(
                screen.getByRole('heading', { name: 'No units yet' }),
            ).toBeInTheDocument();

            await user.click(
                screen.getAllByRole('button', { name: 'Add unit' })[1],
            );

            expect(
                screen.getByRole('dialog', { name: 'Add unit' }),
            ).toBeInTheDocument();
        });
    });

    describe('row actions', () => {
        it('offers Edit and Archive on an active row, and no Delete or Restore', () => {
            render(<Units {...props([yard])} />);

            const row = within(rowOf('Yard'));

            expect(
                row.getByRole('button', { name: 'Edit Yard' }),
            ).toBeEnabled();
            expect(
                row.getByRole('button', { name: 'Archive Yard' }),
            ).toBeEnabled();
            expect(
                row.queryByRole('button', { name: 'Delete Yard' }),
            ).not.toBeInTheDocument();
            expect(
                row.queryByRole('button', { name: 'Restore Yard' }),
            ).not.toBeInTheDocument();
        });

        it('offers Restore and Delete on an archived row', () => {
            render(<Units {...props([unitRow(archived(inch))])} />);

            const row = within(rowOf('Inch'));

            expect(
                row.getByRole('button', { name: 'Restore Inch' }),
            ).toBeEnabled();
            expect(
                row.getByRole('button', { name: 'Delete Inch' }),
            ).toBeEnabled();
            expect(
                row.queryByRole('button', { name: 'Edit Inch' }),
            ).not.toBeInTheDocument();
        });

        it('archives straight away', async () => {
            const user = userEvent.setup();

            render(<Units {...props([yard])} />);
            await user.click(
                within(rowOf('Yard')).getByRole('button', {
                    name: 'Archive Yard',
                }),
            );

            expect(inertiaMock.router.patch).toHaveBeenCalledExactlyOnceWith(
                '/settings/units/3/archive',
                {},
                expect.objectContaining({
                    preserveScroll: true,
                }),
            );
        });

        it('restores', async () => {
            const user = userEvent.setup();

            render(<Units {...props([archived(yard)])} />);
            await user.click(
                within(rowOf('Yard')).getByRole('button', {
                    name: 'Restore Yard',
                }),
            );

            expect(inertiaMock.router.patch).toHaveBeenCalledExactlyOnceWith(
                '/settings/units/3/restore',
                {},
                expect.objectContaining({
                    preserveScroll: true,
                }),
            );
        });

        it('shows the name clash of a refused restore as an error toast', async () => {
            const user = userEvent.setup();

            render(<Units {...props([archived(yard)])} />);
            await user.click(
                within(rowOf('Yard')).getByRole('button', {
                    name: 'Restore Yard',
                }),
            );

            const options = inertiaMock.router.patch.mock.calls[0][2] as {
                onError: (errors: Record<string, string>) => void;
            };

            options.onError({
                name: 'An active unit is already named "Yard". Rename one of them first.',
            });

            expect(toast.error).toHaveBeenCalledExactlyOnceWith(
                'An active unit is already named "Yard". Rename one of them first.',
            );
        });
    });

    describe('delete', () => {
        it('is disabled for an archived system unit, with the reason beside it', () => {
            render(<Units {...props([archived(yard)])} />);

            const row = within(rowOf('Yard'));
            const remove = row.getByRole('button', { name: 'Delete Yard' });

            expect(remove).toBeDisabled();
            expect(
                row.getByText('System units can be archived, not deleted'),
            ).toBeInTheDocument();
            expect(remove).toHaveAccessibleDescription(
                'System units can be archived, not deleted',
            );
        });

        it('is disabled for an archived unit that materials use, naming how many', () => {
            render(<Units {...props([archived(usedFoot)])} />);

            const row = within(rowOf('Foot'));

            expect(
                row.getByRole('button', { name: 'Delete Foot' }),
            ).toBeDisabled();
            expect(row.getByText('Used by 2 materials')).toBeInTheDocument();
            expect(
                row.getByRole('button', { name: 'Delete Foot' }),
            ).toHaveAccessibleDescription('Used by 2 materials');
        });

        it('asks for confirmation, then deletes an unused custom unit', async () => {
            const user = userEvent.setup();

            render(<Units {...props([archived(inch)])} />);
            await user.click(
                within(rowOf('Inch')).getByRole('button', {
                    name: 'Delete Inch',
                }),
            );

            const dialog = screen.getByRole('alertdialog', {
                name: 'Delete unit?',
            });

            expect(within(dialog).getByText('Inch')).toBeInTheDocument();
            expect(inertiaMock.router.delete).not.toHaveBeenCalled();

            await user.click(
                within(dialog).getByRole('button', { name: 'Delete unit' }),
            );

            expect(inertiaMock.router.delete).toHaveBeenCalledExactlyOnceWith(
                '/settings/units/20',
                expect.objectContaining({
                    preserveScroll: true,
                }),
            );
        });

        it('does nothing when the confirmation is cancelled', async () => {
            const user = userEvent.setup();

            render(<Units {...props([archived(inch)])} />);
            await user.click(
                within(rowOf('Inch')).getByRole('button', {
                    name: 'Delete Inch',
                }),
            );
            await user.click(screen.getByRole('button', { name: 'Cancel' }));

            expect(inertiaMock.router.delete).not.toHaveBeenCalled();
            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
        });
    });

    describe('create dialog', () => {
        async function openCreate() {
            const user = userEvent.setup();

            render(<Units {...props([metre])} />);
            await user.click(screen.getByRole('button', { name: 'Add unit' }));

            return {
                user,
                dialog: within(
                    screen.getByRole('dialog', { name: 'Add unit' }),
                ),
            };
        }

        it('has code (with a hint), name, dimension, factor and position', async () => {
            const { dialog } = await openCreate();

            expect(dialog.getByLabelText(/^Code/)).toHaveAccessibleDescription(
                /Lowercase letters, digits and _/,
            );
            expect(dialog.getByLabelText(/^Name/)).toBeInTheDocument();
            expect(
                dialog.getByRole('combobox', { name: /^Dimension/ }),
            ).toBeInTheDocument();
            expect(
                dialog.getByLabelText(/^Factor to base unit/),
            ).toBeInTheDocument();
            expect(dialog.getByLabelText(/^Position/)).toBeInTheDocument();
        });

        it('posts the new unit with the code', async () => {
            const { user, dialog } = await openCreate();

            await user.type(dialog.getByLabelText(/^Code/), 'in');
            await user.type(dialog.getByLabelText(/^Name/), 'Inch');
            await user.click(
                dialog.getByRole('combobox', { name: /^Dimension/ }),
            );
            await user.click(screen.getByRole('option', { name: 'Length' }));
            await user.type(
                dialog.getByLabelText(/^Factor to base unit/),
                '0.0254',
            );

            // The factor says which base unit it is in.
            expect(
                dialog.getByLabelText(/^Factor to base unit/),
            ).toHaveAccessibleDescription(
                expect.stringContaining('this many m'),
            );

            await user.click(dialog.getByRole('button', { name: 'Save unit' }));

            expect(inertiaMock.submissions).toHaveLength(1);
            expect(inertiaMock.submissions[0]).toMatchObject({
                action: '/settings/units',
                method: 'post',
                data: {
                    code: 'in',
                    name: 'Inch',
                    dimension: 'length',
                    factor_to_base: '0.0254',
                },
                options: {
                    preserveScroll: true,
                },
            });
            // An empty position is left out, so the server puts the unit last.
            expect(inertiaMock.submissions[0].data).not.toHaveProperty(
                'position',
            );
        });

        it('sends the position when one is typed', async () => {
            const { user, dialog } = await openCreate();

            await user.type(dialog.getByLabelText(/^Code/), 'in');
            await user.type(dialog.getByLabelText(/^Name/), 'Inch');
            await user.type(dialog.getByLabelText(/^Position/), '3');
            await user.click(
                dialog.getByRole('combobox', { name: /^Dimension/ }),
            );
            await user.click(screen.getByRole('option', { name: 'Length' }));
            await user.type(
                dialog.getByLabelText(/^Factor to base unit/),
                '0.0254',
            );
            await user.click(dialog.getByRole('button', { name: 'Save unit' }));

            expect(inertiaMock.submissions[0].data).toMatchObject({
                position: '3',
            });
        });

        it('hides the factor for a package unit and does not send one', async () => {
            const { user, dialog } = await openCreate();

            await user.type(dialog.getByLabelText(/^Code/), 'bobbin');
            await user.type(dialog.getByLabelText(/^Name/), 'Bobbin');
            await user.click(
                dialog.getByRole('combobox', { name: /^Dimension/ }),
            );
            await user.click(screen.getByRole('option', { name: 'Length' }));
            await user.type(
                dialog.getByLabelText(/^Factor to base unit/),
                '12',
            );
            await user.click(
                dialog.getByRole('combobox', { name: /^Dimension/ }),
            );
            await user.click(screen.getByRole('option', { name: 'Package' }));

            expect(
                dialog.queryByLabelText(/^Factor to base unit/),
            ).not.toBeInTheDocument();
            expect(dialog.getByText(/so it has no factor/)).toBeInTheDocument();

            await user.click(dialog.getByRole('button', { name: 'Save unit' }));

            expect(inertiaMock.submissions[0].data).toEqual({
                code: 'bobbin',
                name: 'Bobbin',
                dimension: 'package',
            });
        });

        it('shows server errors under the fields and keeps the dialog open', async () => {
            setFormState({
                errors: {
                    code: 'The code has already been taken.',
                    factor_to_base:
                        'The factor to base must be greater than 0.',
                },
            });

            const { dialog } = await openCreate();

            const code = dialog.getByLabelText(/^Code/);

            expect(code).toBeInvalid();
            expect(code).toHaveAccessibleDescription(
                expect.stringContaining('The code has already been taken.'),
            );
            expect(
                dialog.getByLabelText(/^Factor to base unit/),
            ).toHaveAccessibleDescription(
                expect.stringContaining('must be greater than 0'),
            );
            expect(dialog.getByLabelText(/^Name/)).toBeValid();
            expect(code).toHaveFocus();
            expect(
                screen.getByRole('dialog', { name: 'Add unit' }),
            ).toBeInTheDocument();
        });

        it('closes when the write succeeds', async () => {
            const { user, dialog } = await openCreate();

            await user.type(dialog.getByLabelText(/^Code/), 'in');
            await user.type(dialog.getByLabelText(/^Name/), 'Inch');
            await user.click(dialog.getByRole('button', { name: 'Save unit' }));

            const options = inertiaMock.submissions[0].options as {
                onSuccess: () => void;
            };

            act(() => options.onSuccess());

            expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
        });

        it('disables Save while the request is in flight', async () => {
            setFormState({ processing: true });

            const { dialog } = await openCreate();

            // The spinner is part of the button's name while it works.
            expect(
                dialog.getByRole('button', { name: /Save unit$/ }),
            ).toBeDisabled();
        });

        it('starts empty every time it opens', async () => {
            const { user, dialog } = await openCreate();

            await user.type(dialog.getByLabelText(/^Name/), 'Half typed');
            await user.click(dialog.getByRole('button', { name: 'Cancel' }));
            await user.click(screen.getByRole('button', { name: 'Add unit' }));

            expect(
                within(
                    screen.getByRole('dialog', { name: 'Add unit' }),
                ).getByLabelText(/^Name/),
            ).toHaveValue('');
        });
    });

    describe('edit dialog', () => {
        async function openEdit(unit: UnitRow) {
            const user = userEvent.setup();

            render(<Units {...props([unit])} />);
            await user.click(
                within(rowOf(unit.name)).getByRole('button', {
                    name: `Edit ${unit.name}`,
                }),
            );

            return {
                user,
                dialog: within(
                    screen.getByRole('dialog', { name: `Edit ${unit.name}` }),
                ),
            };
        }

        it('pre-fills a custom unit, the factor trimmed as text, and has no code field', async () => {
            const { dialog } = await openEdit(inch);

            expect(dialog.getByLabelText(/^Name/)).toHaveValue('Inch');
            expect(dialog.getByLabelText(/^Factor to base unit/)).toHaveValue(
                '0.0254',
            );
            expect(dialog.getByLabelText(/^Position/)).toHaveValue('14');
            expect(
                dialog.getByRole('combobox', { name: /^Dimension/ }),
            ).toHaveTextContent('Length');
            expect(dialog.queryByLabelText(/^Code/)).not.toBeInTheDocument();
            expect(
                dialog.getByText("Code in can't be changed."),
            ).toBeInTheDocument();
        });

        it('puts the unit without the code, with dimension and factor, for an unused custom unit', async () => {
            const { user, dialog } = await openEdit(inch);

            await user.clear(dialog.getByLabelText(/^Name/));
            await user.type(dialog.getByLabelText(/^Name/), 'Inches');
            await user.click(dialog.getByRole('button', { name: 'Save unit' }));

            expect(inertiaMock.submissions).toHaveLength(1);
            expect(inertiaMock.submissions[0]).toMatchObject({
                action: '/settings/units/20',
                method: 'put',
                options: {
                    preserveScroll: true,
                },
            });
            expect(inertiaMock.submissions[0].data).toEqual({
                name: 'Inches',
                dimension: 'length',
                factor_to_base: '0.0254',
                position: '14',
            });
        });

        it('shows a system unit read-only and sends only name and position', async () => {
            const { user, dialog } = await openEdit(yard);

            expect(
                dialog.queryByRole('combobox', { name: /^Dimension/ }),
            ).not.toBeInTheDocument();
            expect(
                dialog.queryByLabelText(/^Factor to base unit/),
            ).not.toBeInTheDocument();
            expect(dialog.getByText('Length')).toBeInTheDocument();
            expect(dialog.getByText('1 yd = 0.9144 m')).toBeInTheDocument();
            expect(
                dialog.getByText(
                    'System units keep their dimension and factor.',
                ),
            ).toBeInTheDocument();

            await user.clear(dialog.getByLabelText(/^Name/));
            await user.type(dialog.getByLabelText(/^Name/), 'Yards');
            await user.click(dialog.getByRole('button', { name: 'Save unit' }));

            expect(inertiaMock.submissions[0]).toMatchObject({
                action: '/settings/units/3',
                method: 'put',
            });
            // Not even unchanged dimension / factor: the server refuses them.
            expect(inertiaMock.submissions[0].data).toEqual({
                name: 'Yards',
                position: '3',
            });
        });

        it('locks the dimension and factor of a custom unit that materials use', async () => {
            const { user, dialog } = await openEdit(usedFoot);

            expect(
                dialog.queryByRole('combobox', { name: /^Dimension/ }),
            ).not.toBeInTheDocument();
            expect(dialog.getByText('1 ft = 0.3048 m')).toBeInTheDocument();
            expect(
                dialog.getByText(
                    "Used by 2 materials, so its dimension and factor can't change.",
                ),
            ).toBeInTheDocument();

            await user.click(dialog.getByRole('button', { name: 'Save unit' }));

            expect(Object.keys(inertiaMock.submissions[0].data)).toEqual([
                'name',
                'position',
            ]);
        });
    });
});
