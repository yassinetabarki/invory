import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import {
    RestockForm,
    RestockSummary,
} from '@/components/materials/restock-form';
import { button, thread } from '@/test/fixtures';
import {
    inertiaMock,
    resetInertiaMock,
    setFormState,
    setPageProps,
} from '@/test/inertia';

// What the page hands to Inertia's <Form>: the mock ignores `onSuccess` and
// `options`, so a thin wrapper records them for the tests.
const received = vi.hoisted(() => ({
    onSuccess: undefined as unknown,
    options: undefined as unknown,
}));

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');
    const inertia = createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
    const MockForm = inertia.Form;

    return {
        ...inertia,
        Form: (props: Parameters<typeof MockForm>[0]) => {
            received.onSuccess = props.onSuccess;
            received.options = props.options;

            return <MockForm {...props} />;
        },
    };
});

function renderForm(
    overrides: Partial<Parameters<typeof RestockForm>[0]> = {},
) {
    const onCancel = vi.fn();
    const onSuccess = vi.fn();

    const view = render(
        <RestockForm
            material={button}
            onCancel={onCancel}
            onSuccess={onSuccess}
            {...overrides}
        />,
    );

    return { ...view, onCancel, onSuccess };
}

describe('<RestockForm>', () => {
    beforeEach(() => {
        resetInertiaMock();
        received.onSuccess = undefined;
        received.options = undefined;
    });

    it('posts to the material restock route through the Wayfinder form helper', () => {
        const { container } = renderForm();

        const form = container.querySelector('form');

        expect(form).toHaveAttribute('action', '/materials/3/restocks');
        expect(form).toHaveAttribute('method', 'post');
    });

    it('has every field, with the unit and the currency next to the numbers', () => {
        renderForm();

        const quantity = screen.getByLabelText('Quantity received');

        expect(quantity).toHaveAttribute('name', 'quantity');
        expect(quantity).toHaveAttribute('inputmode', 'decimal');
        expect(quantity).toHaveAttribute('type', 'text');
        // The unit is announced with the field.
        expect(quantity).toHaveAccessibleDescription('pc');

        const price = screen.getByLabelText('Unit price paid');

        expect(price).toHaveAttribute('name', 'unit_price');
        expect(price).toHaveAttribute('inputmode', 'decimal');
        expect(price).toHaveAccessibleDescription('€');

        expect(screen.getByLabelText('Date')).toHaveAttribute(
            'name',
            'received_on',
        );
        expect(screen.getByLabelText(/^Reference/)).toHaveAttribute(
            'name',
            'reference',
        );
        expect(
            screen.getByRole('button', { name: 'Add to stock' }),
        ).toBeEnabled();
        expect(screen.getByRole('button', { name: 'Cancel' })).toBeVisible();
    });

    it('uses the installation currency for the price prefix', () => {
        setPageProps({
            app: { currency: 'TND', locale: 'en', today: '2026-10-08' },
        });
        renderForm();

        expect(
            screen.getByLabelText('Unit price paid'),
        ).toHaveAccessibleDescription('TND');
    });

    it('suggests the current unit cost as the price placeholder', () => {
        renderForm();

        expect(screen.getByLabelText('Unit price paid')).toHaveAttribute(
            'placeholder',
            '0.35',
        );
        expect(screen.getByLabelText('Unit price paid')).toHaveValue('');
    });

    it('defaults the date to today and does not allow a later one', () => {
        renderForm();

        const date = screen.getByLabelText('Date');

        expect(date).toHaveAttribute('type', 'date');
        expect(date).toHaveValue('2026-10-08');
        expect(date).toHaveAttribute('max', '2026-10-08');
    });

    it('falls back to the browser date when the server sent no today', () => {
        vi.useFakeTimers({ toFake: ['Date'] });
        // Late evening, local time: the date must not shift to another day.
        vi.setSystemTime(new Date(2027, 0, 5, 23, 30));

        try {
            setPageProps({ app: { currency: 'EUR', locale: 'en' } });
            renderForm();

            expect(screen.getByLabelText('Date')).toHaveValue('2027-01-05');
            expect(screen.getByLabelText('Date')).toHaveAttribute(
                'max',
                '2027-01-05',
            );
        } finally {
            vi.useRealTimers();
        }
    });

    it('marks the reference as optional and explains it with a placeholder', () => {
        renderForm();

        expect(screen.getByLabelText(/^Reference/)).toHaveAccessibleName(
            'Reference (optional)',
        );
        expect(screen.getByLabelText(/^Reference/)).toHaveAttribute(
            'placeholder',
            'PO or delivery note',
        );
    });

    it('marks the quantity, price and date as required', () => {
        renderForm();

        for (const label of ['Quantity received', 'Unit price paid', 'Date']) {
            expect(screen.getByLabelText(label)).toHaveAttribute(
                'aria-required',
                'true',
            );
        }
    });

    it('tells the user that the new stock is calculated when they save', () => {
        renderForm();

        expect(
            screen.getByText(
                "Logged in the material's history. The new stock is calculated when you save.",
            ),
        ).toBeInTheDocument();
    });

    describe('use this price as the new unit cost', () => {
        it('is checked by default', () => {
            renderForm();

            expect(
                screen.getByRole('checkbox', {
                    name: 'Use this price as the new unit cost',
                }),
            ).toBeChecked();
        });

        it('submits update_unit_cost as 1 with the typed values', async () => {
            const user = userEvent.setup();

            renderForm();

            await user.type(screen.getByLabelText('Quantity received'), '500');
            await user.type(screen.getByLabelText('Unit price paid'), '0.3600');
            await user.type(screen.getByLabelText(/^Reference/), 'PO-2300');
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
                        reference: 'PO-2300',
                        update_unit_cost: '1',
                    },
                },
            ]);
        });

        it('still submits update_unit_cost, as 0, when it is unchecked', async () => {
            const user = userEvent.setup();

            renderForm();

            await user.click(
                screen.getByRole('checkbox', {
                    name: 'Use this price as the new unit cost',
                }),
            );

            expect(
                screen.getByRole('checkbox', {
                    name: 'Use this price as the new unit cost',
                }),
            ).not.toBeChecked();

            await user.type(screen.getByLabelText('Quantity received'), '40');
            await user.type(screen.getByLabelText('Unit price paid'), '0.5');
            await user.click(
                screen.getByRole('button', { name: 'Add to stock' }),
            );

            expect(inertiaMock.submissions[0].data).toMatchObject({
                quantity: '40',
                unit_price: '0.5',
                update_unit_cost: '0',
            });
        });

        it('submits the changed date', async () => {
            const user = userEvent.setup();

            renderForm();

            const date = screen.getByLabelText('Date');

            await user.clear(date);
            await user.type(date, '2026-10-02');
            await user.click(
                screen.getByRole('button', { name: 'Add to stock' }),
            );

            expect(inertiaMock.submissions[0].data).toMatchObject({
                received_on: '2026-10-02',
            });
        });
    });

    describe('errors', () => {
        it('shows each error on its field with aria-invalid and aria-describedby', () => {
            setFormState({
                errors: {
                    quantity: 'The quantity must be greater than 0.',
                    unit_price: 'The unit price field is required.',
                    received_on: 'The received on must not be in the future.',
                    reference: 'The reference must not exceed 64 characters.',
                },
            });
            renderForm();

            for (const [label, message] of [
                ['Quantity received', 'The quantity must be greater than 0.'],
                ['Unit price paid', 'The unit price field is required.'],
                ['Date', 'The received on must not be in the future.'],
                [/^Reference/, 'The reference must not exceed 64 characters.'],
            ] as const) {
                const control = screen.getByLabelText(label);

                expect(control).toBeInvalid();
                expect(control).toHaveAccessibleDescription(
                    expect.stringContaining(message),
                );
            }
        });

        it('keeps the unit in the announcement next to the error', () => {
            setFormState({ errors: { quantity: 'Must be positive.' } });
            renderForm();

            expect(
                screen.getByLabelText('Quantity received'),
            ).toHaveAccessibleDescription('pc Must be positive.');
        });

        it('leaves fields without an error valid', () => {
            setFormState({ errors: { quantity: 'Must be positive.' } });
            renderForm();

            expect(screen.getByLabelText('Unit price paid')).toBeValid();
            expect(screen.getByLabelText('Date')).toBeValid();
        });

        it('moves focus to the first invalid field', () => {
            setFormState({
                errors: {
                    unit_price: 'Required.',
                    quantity: 'Required.',
                },
            });
            renderForm();

            expect(screen.getByLabelText('Quantity received')).toHaveFocus();
        });

        it('shows an error on the unit cost checkbox', () => {
            setFormState({
                errors: { update_unit_cost: 'Must be true or false.' },
            });
            renderForm();

            expect(
                screen.getByRole('checkbox', {
                    name: 'Use this price as the new unit cost',
                }),
            ).toHaveAccessibleDescription('Must be true or false.');
        });
    });

    describe('buttons', () => {
        it('disables Add to stock and shows a spinner while the request is in flight', () => {
            setFormState({ processing: true });
            renderForm();

            const submit = screen.getByRole('button', {
                name: /add to stock/i,
            });

            expect(submit).toBeDisabled();
            expect(screen.getByRole('status')).toBeInTheDocument();
        });

        it('calls onCancel from Cancel', async () => {
            const user = userEvent.setup();
            const { onCancel } = renderForm();

            await user.click(screen.getByRole('button', { name: 'Cancel' }));

            expect(onCancel).toHaveBeenCalledOnce();
            expect(inertiaMock.submissions).toHaveLength(0);
        });

        it('hands onSuccess to the form so the container can close', () => {
            const { onSuccess } = renderForm();

            expect(received.onSuccess).toBeTypeOf('function');

            act(() => {
                (received.onSuccess as () => void)();
            });

            expect(onSuccess).toHaveBeenCalledOnce();
        });

        it('keeps the scroll position and the page state after saving', () => {
            renderForm();

            expect(received.options).toEqual({
                preserveScroll: true,
                preserveState: true,
            });
        });
    });

    describe('focus', () => {
        it('focuses the quantity when asked to (inline panels)', () => {
            renderForm({ autoFocus: true });

            expect(screen.getByLabelText('Quantity received')).toHaveFocus();
        });

        it('leaves focus alone by default (a dialog manages it)', () => {
            renderForm();

            expect(
                screen.getByLabelText('Quantity received'),
            ).not.toHaveFocus();
        });
    });

    it('uses the unit of the material it restocks', () => {
        renderForm({ material: thread });

        expect(
            screen.getByLabelText('Quantity received'),
        ).toHaveAccessibleDescription('m');
        expect(screen.getByLabelText('Unit price paid')).toHaveAttribute(
            'placeholder',
            '0.004',
        );
    });
});

describe('<RestockSummary>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('shows the stock now and the unit cost, each with its unit or currency', () => {
        const { container } = render(<RestockSummary material={button} />);

        expect(container).toHaveTextContent(
            'In stock now: 900 pc · unit cost €0.35',
        );
    });

    it('keeps the 4 decimals of very small unit costs', () => {
        const { container } = render(<RestockSummary material={thread} />);

        expect(container).toHaveTextContent(
            'In stock now: 0 m · unit cost €0.0040',
        );
    });
});
