import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    beforeAll,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { MaterialForm } from '@/components/materials/material-form';
import { resetInertiaMock, setPageProps } from '@/test/inertia';
import { defaultColours } from '@/test/fixtures/colours';
import type {
    MaterialCategory,
    MaterialFormData,
    Option,
    UnitOfMeasure,
} from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const categories: Option<MaterialCategory>[] = [
    { value: 'fabric', label: 'Fabric' },
    { value: 'thread', label: 'Thread' },
];

const units: Option<UnitOfMeasure>[] = [
    { value: 'm', label: 'Metre (m)' },
    { value: 'pc', label: 'Piece (pc)' },
];

const emptyForm: MaterialFormData = {
    sku: '',
    name: '',
    category: '',
    unit: '',
    unit_cost: '',
    quantity_on_hand: '',
    stock_mode: 'single',
    cost_mode: 'same',
    colours: [],
    reorder_level: '',
    supplier_name: '',
    supplier_reference: '',
    color: '',
    composition: '',
    notes: '',
    photo: null,
    remove_photo: false,
};

const denimForm: MaterialFormData = {
    sku: 'FAB-001',
    name: 'Denim 12oz Indigo',
    category: 'fabric',
    unit: 'm',
    unit_cost: '8.5000',
    quantity_on_hand: '250.0000',
    stock_mode: 'single',
    cost_mode: 'same',
    colours: [],
    reorder_level: '50.0000',
    supplier_name: 'Textiles SA',
    supplier_reference: 'TX-12',
    color: 'Indigo',
    composition: '100% cotton',
    notes: 'Pre-washed',
    photo: null,
    remove_photo: false,
};

// jsdom lacks the pointer-capture / scroll APIs Radix Select relies on.
beforeAll(() => {
    Element.prototype.hasPointerCapture = () => false;
    Element.prototype.setPointerCapture = () => undefined;
    Element.prototype.releasePointerCapture = () => undefined;
    Element.prototype.scrollIntoView = () => undefined;
});

type FormProps = Partial<Parameters<typeof MaterialForm>[0]>;

function renderForm(props: FormProps = {}, onSubmit = vi.fn()) {
    return {
        onSubmit,
        ...render(
            <form
                aria-label="Material"
                onSubmit={(event) => {
                    event.preventDefault();
                    onSubmit(new FormData(event.currentTarget));
                }}
            >
                <MaterialForm
                    initial={emptyForm}
                    categories={categories}
                    units={units}
                    colours={defaultColours}
                    errors={{}}
                    processing={false}
                    submitLabel="Save material"
                    {...props}
                />
            </form>,
        ),
    };
}

/** The native file input of the Photo card. */
function photoInput(): HTMLInputElement {
    const input = screen.getByLabelText('Photo');

    if (!(input instanceof HTMLInputElement)) {
        throw new Error('Expected the Photo label to name the file input.');
    }

    return input;
}

describe('<MaterialForm>', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps({ app: { currency: 'EUR', locale: 'en' } });
    });

    it('labels every field', () => {
        renderForm();

        for (const label of [
            /^SKU/,
            /^Name/,
            /^Category/,
            /^Unit of measure/,
            /^Unit cost/,
            /^Quantity in stock/,
            /^Reorder at/,
            'Supplier (optional)',
            /^Supplier reference/,
            /^Colour/,
            /^Composition/,
            /^Notes/,
            'Photo',
        ]) {
            expect(screen.getByLabelText(label)).toBeInTheDocument();
        }
    });

    it('groups the fields into the mockup sections', () => {
        renderForm();

        for (const name of [
            'Identity',
            'Stock and cost',
            'Supplier and notes',
            'Photo (optional)',
        ]) {
            expect(screen.getByRole('region', { name })).toBeInTheDocument();
        }
    });

    it('marks required fields and leaves optional ones unmarked', () => {
        renderForm();

        for (const label of [
            /^SKU/,
            /^Name/,
            /^Category/,
            /^Unit of measure/,
            /^Unit cost/,
            /^Quantity in stock/,
        ]) {
            expect(screen.getByLabelText(label)).toHaveAttribute(
                'aria-required',
                'true',
            );
        }

        for (const label of [/^Reorder at/, 'Supplier (optional)', /^Notes/]) {
            expect(screen.getByLabelText(label)).not.toHaveAttribute(
                'aria-required',
            );
        }
    });

    it('says "(optional)" in the label of every optional field', () => {
        renderForm();

        for (const label of [
            'Colour (optional)',
            'Composition (optional)',
            'Reorder at (optional)',
            'Supplier (optional)',
            'Supplier reference (optional)',
            'Notes (optional)',
        ]) {
            expect(screen.getByLabelText(label)).toBeInTheDocument();
        }
    });

    it('renders the initial values', () => {
        renderForm({ initial: denimForm });

        expect(screen.getByLabelText(/^SKU/)).toHaveValue('FAB-001');
        expect(screen.getByLabelText(/^Name/)).toHaveValue('Denim 12oz Indigo');
        expect(screen.getByLabelText(/^Category/)).toHaveTextContent('Fabric');
        expect(screen.getByLabelText(/^Unit of measure/)).toHaveTextContent(
            'Metre (m)',
        );
        expect(screen.getByLabelText(/^Unit cost/)).toHaveValue('8.5000');
        expect(screen.getByLabelText(/^Quantity in stock/)).toHaveValue(
            '250.0000',
        );
        expect(screen.getByLabelText(/^Reorder at/)).toHaveValue('50.0000');
        expect(screen.getByLabelText('Supplier (optional)')).toHaveValue(
            'Textiles SA',
        );
        expect(screen.getByLabelText(/^Supplier reference/)).toHaveValue(
            'TX-12',
        );
        expect(screen.getByLabelText(/^Colour/)).toHaveValue('Indigo');
        expect(screen.getByLabelText(/^Composition/)).toHaveValue(
            '100% cotton',
        );
        expect(screen.getByLabelText(/^Notes/)).toHaveValue('Pre-washed');
    });

    it('shows the SKU in a monospace font', () => {
        renderForm();

        expect(screen.getByLabelText(/^SKU/)).toHaveClass('font-mono');
    });

    it('uses text inputs with a decimal keypad for money and quantities', () => {
        renderForm();

        for (const label of [
            /^Unit cost/,
            /^Quantity in stock/,
            /^Reorder at/,
        ]) {
            const input = screen.getByLabelText(label);

            expect(input).toHaveAttribute('type', 'text');
            expect(input).toHaveAttribute('inputmode', 'decimal');
        }
    });

    it('keeps decimal strings exactly as typed (no number coercion)', async () => {
        const user = userEvent.setup();

        renderForm();

        await user.type(screen.getByLabelText(/^Unit cost/), '0.0040');

        expect(screen.getByLabelText(/^Unit cost/)).toHaveValue('0.0040');
    });

    describe('unit and currency affixes', () => {
        it('shows the currency only until a unit is chosen', () => {
            renderForm();

            expect(screen.getByText('€')).toBeInTheDocument();
            expect(screen.queryByText(/^\/ /)).not.toBeInTheDocument();
        });

        it('shows the initial unit next to cost, quantity and reorder level', () => {
            renderForm({ initial: denimForm });

            expect(screen.getByText('€')).toBeInTheDocument();
            expect(screen.getByText('/ m')).toBeInTheDocument();
            // Quantity in stock and Reorder at.
            expect(screen.getAllByText('m')).toHaveLength(2);
        });

        it('follows the unit select', async () => {
            const user = userEvent.setup();

            renderForm({ initial: denimForm });

            await user.click(
                screen.getByRole('combobox', { name: /^Unit of measure/ }),
            );
            await user.click(
                screen.getByRole('option', { name: 'Piece (pc)' }),
            );

            expect(screen.getByText('/ pc')).toBeInTheDocument();
            expect(screen.getAllByText('pc')).toHaveLength(2);
            expect(screen.queryByText('/ m')).not.toBeInTheDocument();
        });

        it("uses the installation currency's own symbol or code", () => {
            setPageProps({ app: { currency: 'TND', locale: 'en' } });
            renderForm({ initial: denimForm });

            expect(screen.getByText('TND')).toBeInTheDocument();
            expect(screen.queryByText('€')).not.toBeInTheDocument();
        });

        it('is announced with the field (aria-describedby)', () => {
            renderForm({ initial: denimForm });

            const input = screen.getByLabelText(/^Unit cost/);
            const described = (input.getAttribute('aria-describedby') ?? '')
                .split(' ')
                .map((id) => document.getElementById(id)?.textContent);

            expect(described).toContain('€');
            expect(described).toContain('/ m');
        });
    });

    it('explains the unit cost and the reorder level', () => {
        renderForm();

        expect(screen.getByLabelText(/^Unit cost/)).toHaveAccessibleDescription(
            expect.stringContaining(
                'Up to 4 decimals, e.g. 0.0040 for thread.',
            ),
        );
        expect(
            screen.getByLabelText(/^Reorder at/),
        ).toHaveAccessibleDescription(
            expect.stringContaining(
                'Shown as Low stock at or below this level.',
            ),
        );
    });

    describe('errors', () => {
        it('shows each error next to its field and wires aria-invalid / aria-describedby', () => {
            renderForm({
                errors: {
                    sku: 'The sku has already been taken.',
                    unit_cost: 'The unit cost must be at least 0.',
                    category: 'The category field is required.',
                },
            });

            for (const [label, message] of [
                [/^SKU/, 'The sku has already been taken.'],
                [/^Unit cost/, 'The unit cost must be at least 0.'],
                [/^Category/, 'The category field is required.'],
            ] as const) {
                const control = screen.getByLabelText(label);
                const describedBy = (
                    control.getAttribute('aria-describedby') ?? ''
                ).split(' ');
                const errorId = describedBy.find((id) => id.endsWith('-error'));

                expect(control).toHaveAttribute('aria-invalid', 'true');
                expect(errorId).toBeDefined();
                expect(
                    document.getElementById(errorId ?? ''),
                ).toHaveTextContent(message);
            }
        });

        it('leaves fields without errors valid', () => {
            renderForm({ errors: { sku: 'Taken.' } });

            expect(screen.getByLabelText(/^Name/)).not.toHaveAttribute(
                'aria-invalid',
            );
            expect(screen.getByLabelText(/^Name/)).not.toHaveAttribute(
                'aria-describedby',
            );
        });

        it('keeps the hint and adds the error to aria-describedby', () => {
            renderForm({ errors: { reorder_level: 'Must be a number.' } });

            const ids = (
                screen
                    .getByLabelText(/^Reorder at/)
                    .getAttribute('aria-describedby') ?? ''
            ).split(' ');

            expect(ids.some((id) => id.endsWith('-hint'))).toBe(true);
            expect(ids.some((id) => id.endsWith('-error'))).toBe(true);
        });

        it('shows a photo error on the photo field', () => {
            renderForm({
                errors: { photo: 'The photo must be 4 MB or smaller.' },
            });

            expect(photoInput()).toBeInvalid();
            expect(photoInput()).toHaveAccessibleDescription(
                expect.stringContaining('The photo must be 4 MB or smaller.'),
            );
        });

        it('focuses the first invalid field when errors arrive', () => {
            const { rerender } = renderForm();

            rerender(
                <form aria-label="Material">
                    <MaterialForm
                        initial={emptyForm}
                        categories={categories}
                        units={units}
                        colours={defaultColours}
                        errors={{
                            quantity_on_hand: 'Required.',
                            name: 'Required.',
                        }}
                        processing={false}
                        submitLabel="Save material"
                    />
                </form>,
            );

            // `name` comes before `quantity_on_hand` in the form.
            expect(screen.getByLabelText(/^Name/)).toHaveFocus();
        });

        it('does not steal focus while a submission is in flight', () => {
            renderForm({ errors: { name: 'Required.' }, processing: true });

            expect(screen.getByLabelText(/^Name/)).not.toHaveFocus();
        });
    });

    describe('submit button', () => {
        it('uses the given label and is enabled by default', () => {
            renderForm({ submitLabel: 'Save changes' });

            expect(
                screen.getByRole('button', { name: 'Save changes' }),
            ).toBeEnabled();
        });

        it('is disabled and shows a spinner while processing', () => {
            renderForm({ processing: true });

            const button = screen.getByRole('button', {
                name: /save material/i,
            });

            expect(button).toBeDisabled();
            expect(within(button).getByRole('status')).toBeInTheDocument();
        });
    });

    describe('cancel', () => {
        it('links back when cancelHref is given', () => {
            renderForm({ cancelHref: '/materials' });

            expect(
                screen.getByRole('link', { name: 'Cancel' }),
            ).toHaveAttribute('href', '/materials');
        });

        it('is omitted otherwise', () => {
            renderForm();

            expect(
                screen.queryByRole('link', { name: 'Cancel' }),
            ).not.toBeInTheDocument();
        });
    });

    describe('photo', () => {
        it('is a native file input named photo for JPG, PNG and WebP, in the side card', () => {
            renderForm();

            const input = photoInput();

            expect(input).toHaveAttribute('type', 'file');
            expect(input).toHaveAttribute('name', 'photo');
            expect(input).toHaveAttribute(
                'accept',
                'image/jpeg,image/png,image/webp',
            );
            expect(
                within(
                    screen.getByRole('region', { name: 'Photo (optional)' }),
                ).getByText(
                    'Shown in the materials table and on the detail page.',
                ),
            ).toBeInTheDocument();
        });

        it('offers a drop zone and no Remove button while there is no photo', () => {
            renderForm();

            expect(
                screen.getByText(/drop a photo here or/i),
            ).toBeInTheDocument();
            expect(
                screen.queryByRole('button', { name: 'Remove photo' }),
            ).not.toBeInTheDocument();
        });

        it('shows the current photo with Replace photo and Remove', () => {
            renderForm({ photoUrl: '/mock/photos/mat-btn-017.svg' });

            expect(screen.getByAltText('Current photo')).toHaveAttribute(
                'src',
                '/mock/photos/mat-btn-017.svg',
            );
            expect(screen.getByText('Replace photo')).toBeInTheDocument();
            expect(
                screen.getByRole('button', { name: 'Remove photo' }),
            ).toBeInTheDocument();
        });

        it('previews a picked photo', async () => {
            const user = userEvent.setup();

            renderForm();

            await user.upload(
                photoInput(),
                new File(['png'], 'button.png', { type: 'image/png' }),
            );

            expect(
                screen.getByAltText('New photo preview'),
            ).toBeInTheDocument();
            expect(screen.getByText('button.png')).toBeInTheDocument();
        });

        it('rejects a file that is not a JPG, PNG or WebP before it is sent', async () => {
            // `applyAccept: false`: the browser's own filter is not what is tested.
            const user = userEvent.setup({ applyAccept: false });

            renderForm();

            await user.upload(
                photoInput(),
                new File(['<svg/>'], 'logo.svg', { type: 'image/svg+xml' }),
            );

            expect(
                screen.getByText('Choose a JPG, PNG or WebP image.'),
            ).toBeInTheDocument();
            expect(
                screen.queryByAltText('New photo preview'),
            ).not.toBeInTheDocument();
        });
    });

    describe('native form submission', () => {
        it('submits every field by name, including the selects and the photo pair', async () => {
            const user = userEvent.setup();
            const { onSubmit } = renderForm({ initial: denimForm });

            await user.click(
                screen.getByRole('button', { name: 'Save material' }),
            );

            expect(onSubmit).toHaveBeenCalledOnce();

            const data: FormData = onSubmit.mock.calls[0][0];
            const {
                photo,
                remove_photo: removePhoto,
                ...fields
            } = Object.fromEntries(data.entries());

            expect(fields).toEqual(
                Object.fromEntries(
                    Object.entries(denimForm).filter(
                        ([key]) =>
                            key !== 'photo' &&
                            key !== 'remove_photo' &&
                            key !== 'colours',
                    ),
                ),
            );
            // No file chosen: an empty file entry, and "do not remove" as 0.
            expect(photo).toBeInstanceOf(File);
            expect((photo as File).size).toBe(0);
            expect(removePhoto).toBe('0');
        });

        it('sends remove_photo as 1 once Remove is pressed', async () => {
            const user = userEvent.setup();
            const { onSubmit } = renderForm({
                initial: denimForm,
                photoUrl: '/mock/photos/mat-btn-017.svg',
            });

            await user.click(
                screen.getByRole('button', { name: 'Remove photo' }),
            );
            await user.click(
                screen.getByRole('button', { name: 'Save material' }),
            );

            const data: FormData = onSubmit.mock.calls[0][0];

            expect(data.get('remove_photo')).toBe('1');
            expect(
                screen.getByText(
                    'The current photo will be removed when you save.',
                ),
            ).toBeInTheDocument();
        });

        it('submits the newly picked category and unit', async () => {
            const user = userEvent.setup();
            const { onSubmit } = renderForm({ initial: denimForm });

            await user.click(
                screen.getByRole('combobox', { name: /^Category/ }),
            );
            await user.click(screen.getByRole('option', { name: 'Thread' }));
            await user.click(
                screen.getByRole('combobox', { name: /^Unit of measure/ }),
            );
            await user.click(
                screen.getByRole('option', { name: 'Piece (pc)' }),
            );
            await user.click(
                screen.getByRole('button', { name: 'Save material' }),
            );

            const data: FormData = onSubmit.mock.calls[0][0];

            expect(data.get('category')).toBe('thread');
            expect(data.get('unit')).toBe('pc');
        });

        it('submits empty strings for blank optional fields', async () => {
            const user = userEvent.setup();
            const { onSubmit } = renderForm();

            await user.click(
                screen.getByRole('button', { name: 'Save material' }),
            );

            const data: FormData = onSubmit.mock.calls[0][0];

            expect(data.get('reorder_level')).toBe('');
            expect(data.get('notes')).toBe('');
            expect(data.get('sku')).toBe('');
        });
    });

    describe('stock and cost per colour', () => {
        it('swaps the fields with the two settings, keeps the Colour text, and submits the colours', async () => {
            const user = userEvent.setup();
            const { onSubmit } = renderForm({ initial: denimForm });

            // One stock: today's fields and no cost question.
            expect(
                screen.getByRole('radio', {
                    name: 'One stock for all colours',
                }),
            ).toBeChecked();
            expect(
                screen.queryByRole('group', { name: 'How is cost set?' }),
            ).not.toBeInTheDocument();
            await user.clear(screen.getByLabelText(/^Colour/));
            await user.type(screen.getByLabelText(/^Colour/), 'Antique brass');

            // Stock per colour, same cost: the Colours table; Unit cost stays; Colour goes.
            await user.click(
                screen.getByRole('radio', { name: 'Stock per colour' }),
            );
            expect(
                screen.getByRole('radio', {
                    name: 'Same cost for every colour',
                }),
            ).toBeChecked();

            const table = screen.getByRole('group', { name: 'Colours' });

            expect(
                within(table).getByText(
                    'No colours yet. Add the colours you buy this material in.',
                ),
            ).toBeInTheDocument();
            expect(
                screen.queryByRole('textbox', { name: /^Colour \(optional\)/ }),
            ).not.toBeInTheDocument();
            expect(
                screen.queryByRole('textbox', { name: /^Quantity in stock$/ }),
            ).not.toBeInTheDocument();
            expect(
                screen.getByRole('textbox', { name: /^Unit cost/ }),
            ).toBeInTheDocument();
            expect(
                screen.getByLabelText(/^Reorder at/),
            ).toHaveAccessibleDescription(
                expect.stringContaining(
                    'Applies to each colour. The material shows Low stock when any colour is at or under this level.',
                ),
            );

            // Add colour offers the list; once chosen a colour is no longer offered.
            await user.click(
                within(table).getByRole('button', { name: 'Add colour' }),
            );

            const list = within(
                screen.getByRole('dialog', { name: 'Choose colours' }),
            );

            await user.click(list.getByRole('option', { name: 'Indigo, IND' }));
            await user.click(list.getByRole('option', { name: 'Black, BLK' }));
            await user.click(list.getByRole('button', { name: 'Done' }));
            await user.click(
                screen.getByRole('button', { name: 'Add colour' }),
            );
            expect(
                screen.queryByRole('option', { name: 'Indigo, IND' }),
            ).not.toBeInTheDocument();
            expect(
                screen.getByRole('option', { name: 'Navy, NAV' }),
            ).toBeInTheDocument();
            await user.keyboard('{Escape}');

            for (const [name, quantity] of [
                ['Black', '40'],
                ['Indigo', '250'],
            ]) {
                const field = screen.getByRole('textbox', {
                    name: `Quantity in stock ${name}`,
                });

                await user.clear(field);
                await user.type(field, quantity);
            }

            // Cost per colour: a cost for each colour and no single Unit cost.
            await user.click(
                screen.getByRole('radio', { name: 'Cost per colour' }),
            );
            expect(
                screen.queryByRole('textbox', { name: 'Unit cost' }),
            ).not.toBeInTheDocument();
            await user.type(
                screen.getByRole('textbox', { name: 'Unit cost Black' }),
                '9.2',
            );
            await user.type(
                screen.getByRole('textbox', { name: 'Unit cost Indigo' }),
                '8.5',
            );
            await user.click(
                screen.getByRole('button', { name: 'Save material' }),
            );

            const data: FormData = onSubmit.mock.calls[0][0];

            expect(Object.fromEntries(data.entries())).toMatchObject({
                stock_mode: 'per_colour',
                cost_mode: 'per_colour',
                color: 'Antique brass',
                'colours[0][colour_id]': '8',
                'colours[0][quantity_on_hand]': '40',
                'colours[0][unit_cost]': '9.2',
                'colours[1][colour_id]': '10',
                'colours[1][quantity_on_hand]': '250',
                'colours[1][unit_cost]': '8.5',
            });
            expect(data.has('unit_cost')).toBe(false);
            expect(data.has('quantity_on_hand')).toBe(false);

            // Back to one stock: the typed Colour text is still there.
            await user.click(
                screen.getByRole('radio', {
                    name: 'One stock for all colours',
                }),
            );
            expect(screen.getByLabelText(/^Colour/)).toHaveValue(
                'Antique brass',
            );
            expect(
                screen.queryByRole('group', { name: 'Colours' }),
            ).not.toBeInTheDocument();
        });

        it('puts each colour message under its field, the colour-less one under the table, and focuses the first', () => {
            const perColour: MaterialFormData = {
                ...denimForm,
                stock_mode: 'per_colour',
                cost_mode: 'per_colour',
                colours: [
                    { colour_id: 8, quantity_on_hand: '40', unit_cost: '' },
                    {
                        colour_id: 10,
                        quantity_on_hand: 'abc',
                        unit_cost: '8.5',
                    },
                ],
            };
            const { unmount } = renderForm({
                initial: perColour,
                errors: {
                    'colours.0.unit_cost': 'Black: Enter a unit cost.',
                    'colours.1.quantity_on_hand':
                        'Indigo: Enter a number from 0, with up to 4 decimals.',
                },
            });

            const cost = screen.getByRole('textbox', {
                name: 'Unit cost Black',
            });
            const quantity = screen.getByRole('textbox', {
                name: 'Quantity in stock Indigo',
            });

            expect(cost).toHaveAttribute('aria-invalid', 'true');
            expect(cost).toHaveAccessibleDescription(
                expect.stringContaining('Black: Enter a unit cost.'),
            );
            expect(quantity).toHaveAttribute('aria-invalid', 'true');
            expect(quantity).toHaveAccessibleDescription(
                expect.stringContaining(
                    'Indigo: Enter a number from 0, with up to 4 decimals.',
                ),
            );
            // The first error in the form takes the focus; typed values stay.
            expect(cost).toHaveFocus();
            expect(quantity).toHaveValue('abc');
            unmount();

            renderForm({
                initial: { ...perColour, colours: [] },
                errors: {
                    colours:
                        'Add at least one colour, or choose One stock for all colours.',
                },
            });

            const add = screen.getByRole('button', { name: 'Add colour' });

            expect(add).toHaveAttribute('aria-invalid', 'true');
            expect(add).toHaveAccessibleDescription(
                'Add at least one colour, or choose One stock for all colours.',
            );
            expect(add).toHaveFocus();
        });
    });
});
