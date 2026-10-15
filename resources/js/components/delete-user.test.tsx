import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import DeleteUser from '@/components/delete-user';
import { resetInertiaMock } from '@/test/inertia';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

describe('<DeleteUser>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it("says the workshop's data stays, on the page and in the confirmation", async () => {
        const user = userEvent.setup();

        render(<DeleteUser />);

        expect(
            screen.getByText(
                "Delete your account. Your workshop's data stays.",
            ),
        ).toBeInTheDocument();

        await user.click(
            screen.getByRole('button', { name: 'Delete account' }),
        );

        expect(
            await screen.findByText(
                "Once your account is deleted, you can no longer sign in with it. Your workshop's materials, products and sales are not deleted. Please enter your password to confirm you would like to permanently delete your account.",
            ),
        ).toBeInTheDocument();
    });
});
