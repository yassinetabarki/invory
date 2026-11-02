import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import {
    ClientFieldsBlock,
    EMPTY_CLIENT,
} from '@/components/clients/client-fields';
import { ClientPicker, clientLine } from '@/components/clients/client-picker';
import { SourceChips } from '@/components/clients/source-chips';
import { SourceLabel } from '@/components/clients/source-label';
import {
    amira,
    boutiqueLina,
    clientSources,
    ines,
    mehdi,
    ref,
} from '@/test/fixtures/clients';
import { inertiaMock, resetInertiaMock } from '@/test/inertia';
import type { ClientFields, ClientRef, ClientSource } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

describe('<SourceLabel>', () => {
    it('shows the word with an icon screen readers skip, "Other · …", and nothing without a source', () => {
        const { container, rerender } = render(<SourceLabel client={amira} />);

        expect(container).toHaveTextContent('Instagram');
        expect(container.querySelector('svg')).toHaveAttribute(
            'aria-hidden',
            'true',
        );

        rerender(
            <SourceLabel
                client={{
                    source: 'other',
                    source_label: 'Other',
                    source_other: 'Fashion fair Sousse',
                }}
            />,
        );
        expect(container).toHaveTextContent('Other · Fashion fair Sousse');

        rerender(<SourceLabel client={ines} />);
        expect(container).toBeEmptyDOMElement();
    });

    it('writes the grey line of a client: source, city and phone', () => {
        expect(clientLine(ref(amira))).toBe('Instagram · Sousse · 22 345 678');
        expect(clientLine(ref(ines))).toBe('Monastir · 20 456 789');
    });
});

function Chips() {
    const [value, setValue] = useState<ClientSource | ''>('');

    return (
        <>
            <p id="source">Source</p>
            <SourceChips
                labelledBy="source"
                options={clientSources}
                value={value}
                onChange={setValue}
            />
        </>
    );
}

describe('<SourceChips>', () => {
    it('is one group named Source: one choice, and a second tap un-chooses it', async () => {
        const user = userEvent.setup();

        render(<Chips />);

        const group = within(screen.getByRole('group', { name: 'Source' }));
        const chips = group.getAllByRole('button');

        expect(chips.map((chip) => chip.textContent)).toEqual([
            'Instagram',
            'Facebook',
            'TikTok',
            'WhatsApp',
            'Website',
            'Walk-in / shop',
            'Word of mouth',
            'Other',
        ]);
        expect(
            chips.every(
                (chip) => chip.getAttribute('aria-pressed') === 'false',
            ),
        ).toBe(true);

        await user.click(group.getByRole('button', { name: 'Instagram' }));
        expect(
            group.getByRole('button', { name: 'Instagram' }),
        ).toHaveAttribute('aria-pressed', 'true');

        await user.click(group.getByRole('button', { name: 'TikTok' }));
        expect(
            group.getByRole('button', { name: 'Instagram' }),
        ).toHaveAttribute('aria-pressed', 'false');
        expect(group.getByRole('button', { name: 'TikTok' })).toHaveAttribute(
            'aria-pressed',
            'true',
        );

        await user.click(group.getByRole('button', { name: 'TikTok' }));
        expect(group.getByRole('button', { name: 'TikTok' })).toHaveAttribute(
            'aria-pressed',
            'false',
        );
    });
});

function Fields({
    errors = {},
}: {
    errors?: Partial<Record<keyof ClientFields, string>>;
}) {
    const [values, setValues] = useState<ClientFields>(EMPTY_CLIENT);

    return (
        <ClientFieldsBlock
            values={values}
            onChange={(field, value) =>
                setValues((current) => ({ ...current, [field]: value }))
            }
            errors={errors}
            cities={['Sousse', 'Tunis']}
            sources={clientSources}
        />
    );
}

describe('<ClientFieldsBlock>', () => {
    it('has the hints of the card, the number keypad on Phone, the cities to suggest, and Other source only for Other', async () => {
        const user = userEvent.setup();

        render(<Fields />);

        expect(screen.getByLabelText('Name')).toHaveAccessibleDescription(
            'The person or the shop, as you call them.',
        );
        expect(screen.getByLabelText('Phone')).toHaveAccessibleDescription(
            '8 digits, such as 22 345 678. For a number abroad, start with + and the country code.',
        );
        expect(screen.getByLabelText('Phone')).toHaveAttribute(
            'inputmode',
            'tel',
        );
        expect(
            screen.getByRole('group', { name: 'Source (optional)' }),
        ).toHaveAccessibleDescription('Where did this client find you?');
        expect(document.querySelectorAll('datalist option')).toHaveLength(2);
        expect(screen.queryByLabelText(/Other source/)).not.toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Other' }));

        expect(
            screen.getByLabelText('Other source (optional)'),
        ).toHaveAccessibleDescription(
            'In a few words, such as a fashion fair.',
        );
    });

    it('shows each server message under its field', () => {
        render(
            <Fields
                errors={{
                    name: 'Keep the name to 100 characters or fewer.',
                    phone: 'Enter 8 digits, such as 22 345 678, or a number abroad starting with +.',
                    city: "Enter the client's city.",
                }}
            />,
        );

        expect(screen.getByLabelText('Name')).toHaveAccessibleDescription(
            /Keep the name to 100 characters or fewer\./,
        );
        expect(screen.getByLabelText('Phone')).toBeInvalid();
        expect(
            screen.getByText("Enter the client's city."),
        ).toBeInTheDocument();
    });
});

function Picker({ onAdd }: { onAdd?: (typed: string) => void }) {
    const [value, setValue] = useState<ClientRef | null>(null);

    return (
        <>
            <p id="client">Client</p>
            <ClientPicker
                id="picker"
                labelledBy="client"
                value={value}
                onSelect={setValue}
                onAdd={onAdd}
            />
        </>
    );
}

/** Answers the picker's request, inside act. */
async function answer(index: number, data: unknown) {
    await act(async () => {
        inertiaMock.http.requests[index].resolve(data);
    });
}

describe('<ClientPicker>', () => {
    beforeEach(() => resetInertiaMock());

    it('loads the clients A → Z with their grey line, and chooses one', async () => {
        const user = userEvent.setup();

        render(<Picker />);
        await user.click(
            screen.getByRole('combobox', { name: 'Client Choose a client…' }),
        );

        expect(inertiaMock.http.requests[0]).toMatchObject({
            method: 'get',
            path: '/clients/options',
        });

        await answer(0, [ref(amira), ref(boutiqueLina), ref(mehdi)]);

        expect(
            screen.getByRole('option', { name: /Amira Ben Salah/ }),
        ).toHaveTextContent('Instagram · Sousse · 22 345 678');

        await user.click(screen.getByRole('option', { name: /Boutique Lina/ }));

        expect(
            screen.getByRole('combobox', { name: 'Client Boutique Lina' }),
        ).toBeInTheDocument();
    });

    it('offers to add what was typed as a new client, and says when nothing matches or the clients could not load', async () => {
        const user = userEvent.setup();
        const onAdd = vi.fn();

        render(<Picker onAdd={onAdd} />);
        await user.click(screen.getByRole('combobox'));
        await answer(0, []);

        expect(screen.getByText('No clients yet.')).toBeInTheDocument();
        expect(
            screen.getByRole('option', { name: 'New client' }),
        ).toBeInTheDocument();

        await user.type(
            screen.getByPlaceholderText('Search clients'),
            'Ines Mrad',
        );
        await act(async () => {
            await new Promise((resolve) => setTimeout(resolve, 300));
        });
        await answer(inertiaMock.http.requests.length - 1, []);

        expect(
            screen.getByText('No client matches "Ines Mrad".'),
        ).toBeInTheDocument();

        await user.click(
            screen.getByRole('option', {
                name: 'Add "Ines Mrad" as a new client',
            }),
        );

        expect(onAdd).toHaveBeenCalledWith('Ines Mrad');
    });

    it('says when the clients could not load, and asks again', async () => {
        const user = userEvent.setup();

        render(<Picker />);
        await user.click(screen.getByRole('combobox'));
        await act(async () => {
            inertiaMock.http.requests[0].reject();
        });

        expect(screen.getByRole('alert')).toHaveTextContent(
            "Couldn't load the clients. Try again.",
        );

        await user.click(screen.getByRole('button', { name: 'Try again' }));

        expect(inertiaMock.http.requests).toHaveLength(2);
    });
});
