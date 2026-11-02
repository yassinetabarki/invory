import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { ClientCard } from '@/components/clients/client-card';
import { EMPTY_CLIENT } from '@/components/clients/client-fields';
import { amira, clientSources } from '@/test/fixtures/clients';
import { inertiaMock, resetInertiaMock, setPageProps } from '@/test/inertia';
import type { ClientFields, ClientSummary } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

let sent: ClientFields = EMPTY_CLIENT;

function Card({
    errors = {},
    preset = null,
}: {
    errors?: Partial<Record<keyof ClientFields, string>>;
    preset?: ClientSummary | null;
}) {
    const [values, setValues] = useState<ClientFields>({
        ...EMPTY_CLIENT,
        phone: preset?.phone ?? '',
    });

    sent = values;

    return (
        <ClientCard
            values={values}
            onChange={setValues}
            errors={errors}
            cities={['Sousse']}
            sources={clientSources}
            preset={preset}
        />
    );
}

/** Answers the last lookup, inside act. */
async function answerLookup(client: ClientSummary | null) {
    await act(async () => {
        inertiaMock.http.requests.at(-1)?.resolve({ client });
    });
}

describe('<ClientCard>', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps({
            app: { locale: 'en', currency: 'TND', today: '2026-10-30' },
        });
        sent = EMPTY_CLIENT;
    });

    it('starts with the phone first and the lines of the card', () => {
        render(<Card />);

        expect(
            screen.getByRole('heading', { name: 'Client' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'A phone number belongs to one client. If it is already one of your clients, the order goes to them.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Find a client by name' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('group', { name: 'Client details' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'The client is saved to your clients when you save the order, if the phone number is new.',
            ),
        ).toBeInTheDocument();

        // Phone comes before Name: the phone finds the client.
        expect(
            screen
                .getByLabelText('Phone')
                .compareDocumentPosition(screen.getByLabelText('Name')) &
                Node.DOCUMENT_POSITION_FOLLOWING,
        ).toBeTruthy();
    });

    it('checks the phone when it loses focus and shows the existing client, her details unchanged', async () => {
        const user = userEvent.setup();

        render(<Card />);
        await user.type(screen.getByLabelText('Phone'), '22 345 678');
        await user.tab();

        expect(inertiaMock.http.requests.at(-1)).toMatchObject({
            path: '/clients/lookup',
            params: { phone: '22 345 678' },
        });
        expect(screen.getByText('Checking 22 345 678…')).toBeInTheDocument();

        await answerLookup(amira);

        expect(screen.getByText('Amira Ben Salah')).toBeInTheDocument();
        expect(screen.getByText('Existing client')).toBeInTheDocument();
        expect(
            screen.getByText('3 orders · 1 open · last order 28 Oct 2026'),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                "The order uses the details saved for this client. Name, City and Source aren't asked again.",
            ),
        ).toBeInTheDocument();
        expect(screen.queryByLabelText('Name')).not.toBeInTheDocument();
        expect(sent.phone).toBe('22 345 678');
    });

    it('says when the number is new, and the line under the box changes', async () => {
        const user = userEvent.setup();

        render(<Card />);
        await user.type(screen.getByLabelText('Phone'), '20 456 789');
        await user.tab();
        await answerLookup(null);

        expect(screen.getByRole('status')).toHaveTextContent(
            "New client. 20 456 789 isn't one of your clients yet.",
        );
        expect(
            screen.getByText('Saved to your clients when you save the order.'),
        ).toBeInTheDocument();
    });

    it('notes details typed before a known phone', async () => {
        const user = userEvent.setup();

        render(<Card />);
        await user.type(screen.getByLabelText('Name'), 'Amina B.');
        await user.type(screen.getByLabelText('City'), 'Tunis');
        await user.type(screen.getByLabelText('Phone'), '22 345 678');
        await user.tab();
        // The name lookup ran first; answer the phone's, the last one.
        await answerLookup(amira);

        expect(
            screen.getByText(
                'You typed "Amina B." in Tunis. 22 345 678 belongs to Amira Ben Salah (Sousse), so the order goes to her. Her saved details stay as they are.',
            ),
        ).toBeInTheDocument();
    });

    it('lets the save go on when the number could not be checked, and checks it again', async () => {
        const user = userEvent.setup();

        render(<Card />);
        await user.type(screen.getByLabelText('Phone'), '22 345 678');
        await user.tab();
        await act(async () => {
            inertiaMock.http.requests.at(-1)?.reject();
        });

        expect(
            screen.getByText(
                "Couldn't check this number. Save the order and Invory checks it again.",
            ),
        ).toBeInTheDocument();

        const before = inertiaMock.http.requests.length;

        await user.click(screen.getByRole('button', { name: 'Try again' }));

        expect(inertiaMock.http.requests).toHaveLength(before + 1);
    });

    it('uses another number: the form comes back, Phone empty and focused', async () => {
        const user = userEvent.setup();

        render(<Card />);
        await user.type(screen.getByLabelText('Phone'), '22 345 678');
        await user.tab();
        await answerLookup(amira);
        await user.click(
            screen.getByRole('button', { name: 'Use another number' }),
        );

        expect(screen.getByLabelText('Phone')).toHaveValue('');
        expect(screen.getByLabelText('Phone')).toHaveFocus();
    });

    it('offers a client with the same name, and choosing her shows her as Existing client', async () => {
        const user = userEvent.setup();

        render(<Card />);
        await user.type(screen.getByLabelText('Name'), 'Amira Ben Salah');
        await user.tab();
        await answerLookup(amira);

        expect(
            screen.getByText(
                /You already have a client named Amira Ben Salah \(Sousse · 22 345 678\)\./,
            ),
        ).toBeInTheDocument();

        await user.click(
            screen.getByRole('button', { name: 'Choose Amira Ben Salah' }),
        );

        expect(screen.getByText('Existing client')).toBeInTheDocument();
        expect(sent.phone).toBe('22 345 678');
    });

    it('sums up the client fields the save refused', () => {
        render(
            <Card
                errors={{
                    phone: "Enter the client's phone.",
                    name: "Enter the client's name.",
                    city: "Enter the client's city.",
                }}
            />,
        );

        expect(screen.getByRole('alert')).toHaveTextContent(
            "Check the client: 3 fields can't be saved.",
        );
        expect(
            screen.getByText("Enter the client's phone."),
        ).toBeInTheDocument();
    });

    it('shows the client of a client page from the start', () => {
        render(<Card preset={amira} />);

        expect(screen.getByText('Existing client')).toBeInTheDocument();
        expect(sent.phone).toBe('22 345 678');
    });
});
