import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { AddClientDialog } from '@/components/clients/add-client-dialog';
import { amira, clientSources, ref } from '@/test/fixtures/clients';
import { inertiaMock, resetInertiaMock, setFormState } from '@/test/inertia';
import type { ClientFields } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function renderDialog(
    props: { initial?: Partial<ClientFields>; choose?: boolean } = {},
) {
    const onOpenChange = vi.fn();
    const onCreated = vi.fn();
    const onChooseExisting = vi.fn();

    render(
        <AddClientDialog
            open
            onOpenChange={onOpenChange}
            initial={props.initial}
            cities={['Sousse']}
            sources={clientSources}
            onCreated={onCreated}
            onChooseExisting={props.choose ? onChooseExisting : undefined}
        />,
    );

    return {
        user: userEvent.setup(),
        onOpenChange,
        onCreated,
        onChooseExisting,
        dialog: within(screen.getByRole('dialog', { name: 'Add client' })),
    };
}

describe('<AddClientDialog>', () => {
    beforeEach(() => resetInertiaMock());

    it('saves Ines Mrad to clients.store, then hands the new client over and closes', async () => {
        const { user, dialog, onCreated, onOpenChange } = renderDialog();

        await user.type(dialog.getByLabelText('Name'), 'Ines Mrad');
        await user.type(dialog.getByLabelText('Phone'), '20 456 789');
        await user.type(dialog.getByLabelText('City'), 'Monastir');
        await user.click(dialog.getByRole('button', { name: 'Instagram' }));
        await user.click(dialog.getByRole('button', { name: 'Save client' }));

        expect(inertiaMock.submissions[0]).toMatchObject({
            action: '/clients',
            method: 'post',
            data: {
                name: 'Ines Mrad',
                phone: '20 456 789',
                city: 'Monastir',
                source: 'instagram',
                source_other: '',
            },
        });

        const created = { ...ref(amira), id: 7, name: 'Ines Mrad' };

        act(() => {
            (
                inertiaMock.submissions[0].options as {
                    onSuccess: (page: unknown) => void;
                }
            ).onSuccess({ flash: { client_created: created } });
        });

        expect(onCreated).toHaveBeenCalledWith(created);
        expect(onOpenChange).toHaveBeenCalledWith(false);
    });

    it('starts with the name typed in the picker, the focus in Phone', () => {
        const { dialog } = renderDialog({ initial: { name: 'amira' } });

        expect(dialog.getByLabelText('Name')).toHaveValue('amira');
        expect(dialog.getByLabelText('Phone')).toHaveFocus();
    });

    it('keeps what was typed on an error, and offers the client whose phone it is', async () => {
        const { user, dialog } = renderDialog();

        await user.type(dialog.getByLabelText('Phone'), '22345678');

        setFormState({
            errors: {
                phone: '22 345 678 is already the phone of Amira Ben Salah.',
                phone_taken_by: '1',
            },
        });

        expect(dialog.getByLabelText('Phone')).toHaveValue('22345678');
        expect(
            dialog.getByText(
                '22 345 678 is already the phone of Amira Ben Salah.',
            ),
        ).toBeInTheDocument();
        expect(inertiaMock.http.requests.at(-1)).toMatchObject({
            path: '/clients/lookup',
            params: { phone: '22345678' },
        });

        await act(async () => {
            inertiaMock.http.requests.at(-1)?.resolve({ client: amira });
        });

        expect(
            dialog.getByRole('link', { name: 'Open Amira Ben Salah' }),
        ).toHaveAttribute('href', '/clients/1');
    });

    it('notes a name already used, and over an order offers to choose her instead', async () => {
        const { user, dialog, onChooseExisting } = renderDialog({
            choose: true,
        });

        await user.type(dialog.getByLabelText('Name'), 'Amira Ben Salah');
        await user.tab();

        expect(inertiaMock.http.requests.at(-1)).toMatchObject({
            path: '/clients/lookup',
            params: { name: 'Amira Ben Salah' },
        });

        await act(async () => {
            inertiaMock.http.requests.at(-1)?.resolve({ client: amira });
        });

        expect(
            dialog.getByText(
                /You already have a client named Amira Ben Salah \(Sousse · 22 345 678\)\./,
            ),
        ).toBeInTheDocument();

        await user.click(
            dialog.getByRole('button', { name: 'Choose Amira Ben Salah' }),
        );

        expect(onChooseExisting).toHaveBeenCalledWith(amira);
    });
});
