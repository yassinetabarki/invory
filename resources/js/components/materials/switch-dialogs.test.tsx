import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    afterEach,
    beforeAll,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { MaterialForm } from '@/components/materials/material-form';
import { defaultColours } from '@/test/fixtures/colours';
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
];
const units: Option<UnitOfMeasure>[] = [{ value: 'm', label: 'Metre (m)' }];

const INDIGO = 10;
const BLACK = 8;

/** Denim 12oz as saved: one stock of 250 m at 8.5000. */
const oneStock: MaterialFormData = {
    sku: 'FAB-001',
    name: 'Denim 12oz',
    category: 'fabric',
    unit: 'm',
    unit_cost: '8.5000',
    quantity_on_hand: '250.0000',
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

/** Denim 12oz as saved: Indigo 250 m and Black 40 m, the same cost or one each. */
function perColour(costMode: 'same' | 'per_colour'): MaterialFormData {
    const costs = costMode === 'per_colour';

    return {
        ...oneStock,
        quantity_on_hand: '290.0000',
        stock_mode: 'per_colour',
        cost_mode: costMode,
        colours: [
            {
                colour_id: BLACK,
                quantity_on_hand: '40.0000',
                unit_cost: costs ? '9.2000' : '',
            },
            {
                colour_id: INDIGO,
                quantity_on_hand: '250.0000',
                unit_cost: costs ? '8.5000' : '',
            },
        ],
    };
}

const SENTENCE = 'Indigo 250 m + Black 40 m become one stock of 290 m.';

beforeAll(() => {
    Element.prototype.hasPointerCapture = () => false;
    Element.prototype.setPointerCapture = () => undefined;
    Element.prototype.releasePointerCapture = () => undefined;
    Element.prototype.scrollIntoView = () => undefined;
});

function renderForm(
    initial: MaterialFormData,
    errors: Partial<Record<string, string>> = {},
) {
    const onSubmit = vi.fn();
    // `storedColours` is passed (even empty) the way Edit does: the material is saved.
    const ui = (current: typeof errors) => (
        <form
            aria-label="Material"
            onSubmit={(event) => {
                event.preventDefault();
                onSubmit(new FormData(event.currentTarget));
            }}
        >
            <MaterialForm
                initial={initial}
                categories={categories}
                units={units}
                colours={defaultColours}
                storedColours={[]}
                errors={current}
                processing={false}
                submitLabel="Save material"
            />
        </form>
    );
    const view = render(ui(errors));

    return {
        onSubmit,
        showErrors: (next: typeof errors) => view.rerender(ui(next)),
    };
}

/** The fetch the total window makes: answers with the server's sentence, or fails. */
function mockTotal(...answers: ('ok' | 'fail')[]) {
    const fetchMock = vi.fn();

    for (const answer of answers) {
        fetchMock.mockResolvedValueOnce(
            answer === 'ok'
                ? new Response(
                      JSON.stringify({
                          total: '290.0000',
                          sentence: SENTENCE,
                      }),
                      { status: 200 },
                  )
                : new Response('{}', { status: 422 }),
        );
    }

    vi.stubGlobal('fetch', fetchMock);

    return fetchMock;
}

const radio = (name: string) => screen.getByRole('radio', { name });

describe('the switch windows of the material form', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps({ app: { currency: 'TND', locale: 'en' } });
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('opens "Use the same cost for every colour?" with no cost chosen, and keeps the choice until one is', async () => {
        const user = userEvent.setup();

        renderForm(perColour('per_colour'));
        await user.click(radio('Same cost for every colour'));

        const dialog = within(
            screen.getByRole('alertdialog', {
                name: 'Use the same cost for every colour?',
            }),
        );

        expect(
            dialog.getByRole('radio', { name: /^Indigo · TND\s8\.500 \/ m$/ }),
        ).not.toBeChecked();
        expect(
            dialog.getByRole('radio', { name: /^Black · TND\s9\.200 \/ m$/ }),
        ).not.toBeChecked();
        expect(
            dialog.getByRole('button', {
                name: 'Use this cost for all colours',
            }),
        ).toBeDisabled();
        expect(dialog.getByText('Choose the cost to keep.')).toBeVisible();

        // Keep cost per colour closes it and puts the choice back.
        await user.click(
            dialog.getByRole('button', { name: 'Keep cost per colour' }),
        );
        expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
        expect(radio('Cost per colour')).toBeChecked();

        // Once a cost is chosen it works: every colour uses it.
        await user.click(radio('Same cost for every colour'));
        await user.click(
            screen.getByRole('radio', { name: /^Indigo · TND\s8\.500 \/ m$/ }),
        );
        await user.click(
            screen.getByRole('button', {
                name: 'Use this cost for all colours',
            }),
        );
        expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
        expect(radio('Same cost for every colour')).toBeChecked();
        expect(screen.getByRole('textbox', { name: /^Unit cost/ })).toHaveValue(
            '8.5000',
        );
    });

    it('opens "Switch to one stock for all colours?" with the server\'s sentence, asks for a cost, and fills the one stock', async () => {
        const user = userEvent.setup();
        const fetchMock = mockTotal('ok', 'ok');
        // The first answer waits, so the window can be seen before the total loads.
        let release: () => void = () => undefined;
        const gate = new Promise<void>((resolve) => {
            release = resolve;
        });

        vi.stubGlobal('fetch', (...args: Parameters<typeof fetch>) =>
            gate.then(() => fetchMock(...args)),
        );

        renderForm(perColour('per_colour'));
        await user.click(radio('One stock for all colours'));

        const dialog = within(
            await screen.findByRole('alertdialog', {
                name: 'Switch to one stock for all colours?',
            }),
        );

        // Keyboard focus lands inside the window as it opens.
        expect(
            dialog.getByRole('button', { name: 'Keep stock per colour' }),
        ).toHaveFocus();

        // The sentence is announced politely: its live region is there before the total loads.
        const liveRegion = screen
            .getByRole('alertdialog')
            .querySelector('[aria-live="polite"]');

        expect(liveRegion).toHaveAttribute('aria-atomic', 'true');
        expect(liveRegion).not.toHaveTextContent(SENTENCE);

        release();
        await waitFor(() => expect(dialog.getByText(SENTENCE)).toBeVisible(), {
            timeout: 1000,
        });
        expect(liveRegion).toHaveTextContent(SENTENCE);
        expect(
            dialog.getByText(/The history of each colour stays\./),
        ).toBeVisible();
        expect(
            dialog.getByText('Choose the cost to keep for every colour:'),
        ).toBeVisible();
        expect(
            dialog.getByText('The stock value changes with the cost you keep.'),
        ).toBeVisible();
        expect(dialog.getByText('Choose the cost to keep.')).toBeVisible();
        expect(
            dialog.getByRole('button', { name: 'Switch to one stock' }),
        ).toBeDisabled();

        const requested = decodeURIComponent(
            String(fetchMock.mock.calls[0][0]),
        );

        expect(requested).toContain('unit=m');
        expect(requested).toContain(`colours[${INDIGO}]=250.0000`);
        expect(requested).toContain(`colours[${BLACK}]=40.0000`);

        // Keep stock per colour closes it and puts the choice back.
        await user.click(
            dialog.getByRole('button', { name: 'Keep stock per colour' }),
        );
        expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
        expect(radio('Stock per colour')).toBeChecked();

        // Choose Black's cost: one stock of 290 m at TND 9.200, nothing saved yet.
        await user.click(radio('One stock for all colours'));
        await user.click(
            await screen.findByRole('radio', {
                name: /^Black · TND\s9\.200 \/ m$/,
            }),
        );
        await user.click(
            await screen.findByRole('button', { name: 'Switch to one stock' }),
        );
        expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
        expect(radio('One stock for all colours')).toBeChecked();
        expect(
            screen.getByRole('textbox', { name: /^Quantity in stock/ }),
        ).toHaveValue('290.0000');
        expect(screen.getByRole('textbox', { name: /^Unit cost/ })).toHaveValue(
            '9.2000',
        );
    });

    it('says "Couldn\'t work out the total." with only Switch to one stock off until Try again works', async () => {
        const user = userEvent.setup();
        const fetchMock = mockTotal('fail', 'ok');

        renderForm(perColour('same'));
        await user.click(radio('One stock for all colours'));

        const dialog = within(
            await screen.findByRole('alertdialog', {
                name: 'Switch to one stock for all colours?',
            }),
        );

        expect(
            await dialog.findByText("Couldn't work out the total."),
        ).toBeVisible();
        expect(
            dialog.getByRole('button', { name: 'Keep stock per colour' }),
        ).toBeEnabled();
        expect(
            dialog.getByRole('button', { name: 'Switch to one stock' }),
        ).toBeDisabled();

        await user.click(dialog.getByRole('button', { name: 'Try again' }));
        expect(fetchMock).toHaveBeenCalledTimes(2);
        expect(await dialog.findByText(SENTENCE)).toBeVisible();
        expect(
            dialog.getByRole('button', { name: 'Switch to one stock' }),
        ).toBeEnabled();
        expect(
            dialog.queryByText("Couldn't work out the total."),
        ).not.toBeInTheDocument();
    });

    it("turns 250 m into one row that asks for its colour, and shows the server's message under the picker", async () => {
        const user = userEvent.setup();
        const { onSubmit, showErrors } = renderForm(oneStock);

        await user.click(radio('Stock per colour'));

        const table = within(screen.getByRole('group', { name: 'Colours' }));

        expect(
            screen.getByText(
                /in stock\s+now\. Choose the colour that holds it\./,
            ),
        ).toHaveTextContent(
            'You have 250 m in stock now. Choose the colour that holds it. Colours you add start at 0.',
        );
        expect(
            table.getByRole('button', { name: 'Choose a colour' }),
        ).toBeInTheDocument();
        expect(
            table.getByRole('textbox', { name: /^Quantity in stock/ }),
        ).toHaveValue('250.0000');
        expect(
            table.queryByRole('button', { name: /^Remove/ }),
        ).not.toBeInTheDocument();

        // Saved without a colour: the server answers on colours.0.colour_id.
        showErrors({
            'colours.0.colour_id':
                'Choose the colour that holds the 250 m you have now.',
        });

        const picker = table.getByRole('button', { name: 'Choose a colour' });

        expect(picker).toHaveAccessibleDescription(
            'Choose the colour that holds the 250 m you have now.',
        );
        expect(picker).toHaveAttribute('aria-invalid', 'true');

        // Choosing Indigo makes it the first colour, holding the 250 m.
        await user.click(picker);
        await user.click(
            within(
                screen.getByRole('dialog', { name: 'Choose colours' }),
            ).getByRole('option', { name: 'Indigo, IND' }),
        );
        await user.click(screen.getByRole('button', { name: 'Save material' }));

        const data: FormData = onSubmit.mock.calls[0][0];

        expect(data.get('stock_mode')).toBe('per_colour');
        expect(data.get('colours[0][colour_id]')).toBe(String(INDIGO));
        expect(data.get('colours[0][quantity_on_hand]')).toBe('250.0000');
    });
});
