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
    reorder_level: '',
    supplier_name: '',
    supplier_reference: '',
    color: '',
    composition: '',
    notes: '',
};

const denimForm: MaterialFormData = {
    sku: 'FAB-001',
    name: 'Denim 12oz Indigo',
    category: 'fabric',
    unit: 'm',
    unit_cost: '8.5000',
    quantity_on_hand: '250.0000',
    reorder_level: '50.0000',
    supplier_name: 'Textiles SA',
    supplier_reference: 'TX-12',
    color: 'Indigo',
    composition: '100% cotton',
    notes: 'Pre-washed',
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
                    errors={{}}
                    processing={false}
                    submitLabel="Create material"
                    {...props}
                />
            </form>,
        ),
    };
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
            /^Quantity on hand/,
            /^Reorder level/,
            /^Supplier$/,
            /^Supplier reference/,
            /^Colour/,
            /^Composition/,
            /^Notes/,
        ]) {
            expect(screen.getByLabelText(label)).toBeInTheDocument();
        }
    });

    it('groups the fields into sections', () => {
        renderForm();

        for (const name of [
            'Identification',
            'Stock & cost',
            'Supplier & details',
        ]) {
            expect(screen.getByRole('group', { name })).toBeInTheDocument();
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
            /^Quantity on hand/,
        ]) {
            expect(screen.getByLabelText(label)).toHaveAttribute(
                'aria-required',
                'true',
            );
        }

        for (const label of [/^Reorder level/, /^Supplier$/, /^Notes/]) {
            expect(screen.getByLabelText(label)).not.toHaveAttribute(
                'aria-required',
            );
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
        expect(screen.getByLabelText(/^Quantity on hand/)).toHaveValue(
            '250.0000',
        );
        expect(screen.getByLabelText(/^Reorder level/)).toHaveValue('50.0000');
        expect(screen.getByLabelText(/^Supplier$/)).toHaveValue('Textiles SA');
        expect(screen.getByLabelText(/^Supplier reference/)).toHaveValue(
            'TX-12',
        );
        expect(screen.getByLabelText(/^Colour/)).toHaveValue('Indigo');
        expect(screen.getByLabelText(/^Composition/)).toHaveValue(
            '100% cotton',
        );
        expect(screen.getByLabelText(/^Notes/)).toHaveValue('Pre-washed');
    });

    it('uses text inputs with a decimal keypad for money and quantities', () => {
        renderForm();

        for (const label of [
            /^Unit cost/,
            /^Quantity on hand/,
            /^Reorder level/,
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

    describe('unit suffix', () => {
        it('shows the currency only until a unit is chosen', () => {
            renderForm();

            expect(screen.getByText('EUR')).toBeInTheDocument();
        });

        it('shows the initial unit next to cost, quantity and reorder level', () => {
            renderForm({ initial: denimForm });

            expect(screen.getByText('EUR / m')).toBeInTheDocument();
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

            expect(screen.getByText('EUR / pc')).toBeInTheDocument();
            expect(screen.getAllByText('pc')).toHaveLength(2);
            expect(screen.queryByText('EUR / m')).not.toBeInTheDocument();
        });

        it('uses the currency from the shared app settings', () => {
            setPageProps({ app: { currency: 'USD', locale: 'en' } });
            renderForm({ initial: denimForm });

            expect(screen.getByText('USD / m')).toBeInTheDocument();
        });

        it('is announced with the field (aria-describedby)', () => {
            renderForm({ initial: denimForm });

            const input = screen.getByLabelText(/^Unit cost/);
            const describedBy = input.getAttribute('aria-describedby') ?? '';

            expect(describedBy).not.toBe('');
            expect(
                describedBy
                    .split(' ')
                    .map((id) => document.getElementById(id)?.textContent),
            ).toContain('EUR / m');
        });
    });

    it('explains the reorder level', () => {
        renderForm();

        const input = screen.getByLabelText(/^Reorder level/);
        const hintId = (input.getAttribute('aria-describedby') ?? '')
            .split(' ')
            .find((id) => id.endsWith('-hint'));

        expect(hintId).toBeDefined();
        expect(document.getElementById(hintId ?? '')).toHaveTextContent(
            'Shows as Low stock at or below this level',
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
                    .getByLabelText(/^Reorder level/)
                    .getAttribute('aria-describedby') ?? ''
            ).split(' ');

            expect(ids.some((id) => id.endsWith('-hint'))).toBe(true);
            expect(ids.some((id) => id.endsWith('-error'))).toBe(true);
        });

        it('focuses the first invalid field when errors arrive', () => {
            const { rerender } = renderForm();

            rerender(
                <form aria-label="Material">
                    <MaterialForm
                        initial={emptyForm}
                        categories={categories}
                        units={units}
                        errors={{
                            quantity_on_hand: 'Required.',
                            name: 'Required.',
                        }}
                        processing={false}
                        submitLabel="Create material"
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
                name: /create material/i,
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

    describe('native form submission', () => {
        it('submits every field by name, including the selects', async () => {
            const user = userEvent.setup();
            const { onSubmit } = renderForm({ initial: denimForm });

            await user.click(
                screen.getByRole('button', { name: 'Create material' }),
            );

            expect(onSubmit).toHaveBeenCalledOnce();

            const data: FormData = onSubmit.mock.calls[0][0];

            expect(Object.fromEntries(data.entries())).toEqual(denimForm);
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
                screen.getByRole('button', { name: 'Create material' }),
            );

            const data: FormData = onSubmit.mock.calls[0][0];

            expect(data.get('category')).toBe('thread');
            expect(data.get('unit')).toBe('pc');
        });

        it('submits empty strings for blank optional fields', async () => {
            const user = userEvent.setup();
            const { onSubmit } = renderForm();

            await user.click(
                screen.getByRole('button', { name: 'Create material' }),
            );

            const data: FormData = onSubmit.mock.calls[0][0];

            expect(data.get('reorder_level')).toBe('');
            expect(data.get('notes')).toBe('');
            expect(data.get('sku')).toBe('');
        });
    });
});
