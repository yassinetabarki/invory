import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import {
    inertiaMock,
    resetInertiaMock,
    setFormState,
    setPageProps,
} from '@/test/inertia';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function Probe() {
    const { props } = usePage();

    return (
        <>
            <Head title="Probe">
                <meta name="description" content="probe" />
            </Head>
            <p>
                {props.app.currency} / {props.app.locale}
            </p>
            <Link href={{ url: '/materials', method: 'get' }} prefetch>
                Materials
            </Link>
            <button type="button" onClick={() => router.get('/materials')}>
                Go
            </button>
        </>
    );
}

describe('inertia test helper', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('serves default shared props, including app settings', () => {
        render(<Probe />);

        expect(screen.getByText('EUR / en')).toBeInTheDocument();
    });

    it('lets a test override the shared props', () => {
        setPageProps({ app: { currency: 'USD', locale: 'fr' } });
        render(<Probe />);

        expect(screen.getByText('USD / fr')).toBeInTheDocument();
    });

    it('renders Link as an anchor from a Wayfinder-style href object', () => {
        render(<Probe />);

        const link = screen.getByRole('link', { name: 'Materials' });
        expect(link).toHaveAttribute('href', '/materials');
        expect(link).not.toHaveAttribute('prefetch');
    });

    it('records router calls on spies and clears them on reset', () => {
        render(<Probe />);
        screen.getByRole('button', { name: 'Go' }).click();

        expect(inertiaMock.router.get).toHaveBeenCalledWith('/materials');

        resetInertiaMock();
        expect(inertiaMock.router.get).not.toHaveBeenCalled();
    });

    describe('Form', () => {
        function Probe2() {
            return (
                <Form action="/materials" method="post">
                    {({ errors, processing }) => (
                        <>
                            <input name="sku" aria-label="SKU" />
                            {errors.sku && <p>{errors.sku}</p>}
                            <button type="submit" disabled={processing}>
                                Save
                            </button>
                        </>
                    )}
                </Form>
            );
        }

        it('renders a <form> with action and method and hands errors/processing to the render prop', () => {
            setFormState({ errors: { sku: 'Taken.' }, processing: true });

            const { container } = render(<Probe2 />);

            expect(container.querySelector('form')).toHaveAttribute(
                'action',
                '/materials',
            );
            expect(container.querySelector('form')).toHaveAttribute(
                'method',
                'post',
            );
            expect(screen.getByText('Taken.')).toBeInTheDocument();
            expect(screen.getByRole('button', { name: 'Save' })).toBeDisabled();
        });

        it('records a submit instead of sending it, and clears it on reset', async () => {
            const user = userEvent.setup();

            render(<Probe2 />);

            await user.type(screen.getByLabelText('SKU'), 'FAB-1');
            await user.click(screen.getByRole('button', { name: 'Save' }));

            expect(inertiaMock.submissions).toEqual([
                {
                    action: '/materials',
                    method: 'post',
                    data: { sku: 'FAB-1' },
                },
            ]);

            resetInertiaMock();
            expect(inertiaMock.submissions).toEqual([]);
        });
    });
});
