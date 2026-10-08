import {
    Deferred,
    Form,
    Head,
    Link,
    router,
    useForm,
    usePage,
} from '@inertiajs/react';
import { act, render, screen } from '@testing-library/react';
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

    it("includes today's date in the default app settings", () => {
        function Today() {
            return <p>{usePage().props.app.today}</p>;
        }

        render(<Today />);

        expect(screen.getByText('2026-10-08')).toBeInTheDocument();
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

        it('records a chosen file as a File, and an empty string when none was chosen', async () => {
            const user = userEvent.setup();
            const photo = new File(['png'], 'jacket.png', {
                type: 'image/png',
            });

            render(
                <Form action="/materials" method="post">
                    <input name="name" aria-label="Name" />
                    <input type="file" name="photo" aria-label="Photo" />
                    <input
                        type="file"
                        name="attachment"
                        aria-label="Attachment"
                    />
                    <button type="submit">Save</button>
                </Form>,
            );

            await user.type(screen.getByLabelText('Name'), 'Denim');
            await user.upload(screen.getByLabelText('Photo'), photo);
            await user.click(screen.getByRole('button', { name: 'Save' }));

            expect(inertiaMock.submissions).toEqual([
                {
                    action: '/materials',
                    method: 'post',
                    data: { name: 'Denim', photo, attachment: '' },
                },
            ]);
        });
    });

    describe('Deferred', () => {
        function Page() {
            return (
                <Deferred
                    data="recent_history"
                    fallback={<p>Loading history…</p>}
                >
                    <p>History loaded</p>
                </Deferred>
            );
        }

        it('shows the fallback while the prop is absent', () => {
            setPageProps({ materials: [] });
            render(<Page />);

            expect(screen.getByText('Loading history…')).toBeInTheDocument();
            expect(
                screen.queryByText('History loaded'),
            ).not.toBeInTheDocument();
        });

        it('shows the children once the prop is present', () => {
            setPageProps({ recent_history: [] });
            render(<Page />);

            expect(screen.getByText('History loaded')).toBeInTheDocument();
            expect(
                screen.queryByText('Loading history…'),
            ).not.toBeInTheDocument();
        });

        it('switches from the fallback to the children when the prop lands', () => {
            setPageProps({ materials: [] });
            const { rerender } = render(<Page />);

            expect(screen.getByText('Loading history…')).toBeInTheDocument();

            setPageProps({ materials: [], recent_history: [{ id: 1 }] });
            rerender(<Page />);

            expect(screen.getByText('History loaded')).toBeInTheDocument();
            expect(
                screen.queryByText('Loading history…'),
            ).not.toBeInTheDocument();
        });

        it('follows setPageProps while mounted, with no rerender', () => {
            setPageProps({ materials: [] });
            render(<Page />);

            expect(screen.getByText('Loading history…')).toBeInTheDocument();

            setPageProps({ materials: [], recent_history: [] });

            expect(screen.getByText('History loaded')).toBeInTheDocument();
            expect(
                screen.queryByText('Loading history…'),
            ).not.toBeInTheDocument();
        });

        it('counts null and an empty list as loaded (only undefined is missing)', () => {
            setPageProps({ recent_history: null });
            render(<Page />);

            expect(screen.getByText('History loaded')).toBeInTheDocument();
        });

        it('waits for every key of an array, and reads dotted paths', () => {
            function Many() {
                return (
                    <Deferred
                        data={['stats.revenue', 'alerts']}
                        fallback={<p>Waiting</p>}
                    >
                        <p>Ready</p>
                    </Deferred>
                );
            }

            setPageProps({ stats: { revenue: '1.0000' } });
            const { rerender } = render(<Many />);

            expect(screen.getByText('Waiting')).toBeInTheDocument();

            setPageProps({ stats: { revenue: '1.0000' }, alerts: [] });
            rerender(<Many />);

            expect(screen.getByText('Ready')).toBeInTheDocument();
        });

        it('accepts a fallback function and a children function', () => {
            function Functions() {
                return (
                    <Deferred data="top" fallback={() => <p>Skeleton</p>}>
                        {({ reloading }) => (
                            <p>{reloading ? 'Reloading' : 'Top loaded'}</p>
                        )}
                    </Deferred>
                );
            }

            const { rerender } = render(<Functions />);

            expect(screen.getByText('Skeleton')).toBeInTheDocument();

            setPageProps({ top: [] });
            rerender(<Functions />);

            expect(screen.getByText('Top loaded')).toBeInTheDocument();
        });

        it('requires a fallback like the real component', () => {
            const consoleError = vi
                .spyOn(console, 'error')
                .mockImplementation(() => undefined);

            expect(() =>
                render(
                    <Deferred data="x" fallback={null}>
                        <p>Never</p>
                    </Deferred>,
                ),
            ).toThrow('requires a `fallback` prop');

            consoleError.mockRestore();
        });
    });

    describe('useForm', () => {
        type ProductData = {
            name: string;
            photo: File | null;
            components: { quantity: string }[];
        };

        type ProductFormApi = ReturnType<typeof useForm<ProductData>>;

        /** A form whose handle is handed to the test, to call its methods. */
        function ProductForm({
            onForm,
        }: {
            onForm?: (form: ProductFormApi) => void;
        }) {
            const form = useForm<ProductData>({
                name: '',
                photo: null,
                components: [{ quantity: '1' }],
            });

            onForm?.(form);

            return (
                <>
                    <label>
                        Name
                        <input
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                        />
                    </label>
                    <p>Quantity: {form.data.components[0]?.quantity}</p>
                    <p>{form.isDirty ? 'dirty' : 'clean'}</p>
                    {form.errors.name && <p role="alert">{form.errors.name}</p>}
                    {form.processing && <p>Saving…</p>}
                    <button
                        type="button"
                        onClick={() =>
                            form.submit({ url: '/products/12', method: 'put' })
                        }
                    >
                        Save
                    </button>
                </>
            );
        }

        it('keeps real state: typing updates data and the dirty flag', async () => {
            const user = userEvent.setup();

            render(<ProductForm />);

            expect(screen.getByText('clean')).toBeInTheDocument();

            await user.type(screen.getByLabelText('Name'), 'Denim');

            expect(screen.getByLabelText('Name')).toHaveValue('Denim');
            expect(screen.getByText('dirty')).toBeInTheDocument();
        });

        it('records a Wayfinder-style submit: method, url and data', async () => {
            const user = userEvent.setup();

            render(<ProductForm />);

            await user.type(screen.getByLabelText('Name'), 'Denim Jacket');
            await user.click(screen.getByRole('button', { name: 'Save' }));

            expect(inertiaMock.submissions).toEqual([
                {
                    action: '/products/12',
                    method: 'put',
                    data: {
                        name: 'Denim Jacket',
                        photo: null,
                        components: [{ quantity: '1' }],
                    },
                },
            ]);
        });

        it('keeps files as File objects in the recorded data, and records the options', () => {
            const photo = new File(['png'], 'jacket.png', {
                type: 'image/png',
            });
            let form: ProductFormApi | undefined;

            render(<ProductForm onForm={(f) => (form = f)} />);

            act(() => form?.setData('photo', photo));
            act(() =>
                form?.submit(
                    { url: '/products/12', method: 'post' },
                    { forceFormData: true },
                ),
            );

            expect(inertiaMock.submissions[0]?.data.photo).toBe(photo);
            expect(inertiaMock.submissions[0]?.options).toEqual({
                forceFormData: true,
            });
        });

        it('supports (method, url, data) and submits to it with submit()', () => {
            let form: ReturnType<typeof useForm<{ q: string }>> | undefined;

            function Bound() {
                form = useForm('post', '/sales', { q: 'x' });

                return null;
            }

            render(<Bound />);
            act(() => form?.submit());

            expect(inertiaMock.submissions).toEqual([
                { action: '/sales', method: 'post', data: { q: 'x' } },
            ]);
        });

        it.each(['get', 'post', 'put', 'patch', 'delete'] as const)(
            'records %s() shortcuts',
            (method) => {
                let form: ReturnType<typeof useForm<{ q: string }>> | undefined;

                function Shortcut() {
                    form = useForm({ q: 'x' });

                    return null;
                }

                render(<Shortcut />);
                act(() => form?.[method]('/things', { preserveScroll: true }));

                expect(inertiaMock.submissions).toEqual([
                    {
                        action: '/things',
                        method,
                        data: { q: 'x' },
                        options: { preserveScroll: true },
                    },
                ]);
            },
        );

        it('sets nested values by dotted path, object and updater', () => {
            let form: ProductFormApi | undefined;

            render(<ProductForm onForm={(f) => (form = f)} />);

            act(() => form?.setData('components.0.quantity', '2.5'));
            expect(screen.getByText('Quantity: 2.5')).toBeInTheDocument();

            act(() =>
                form?.setData((current) => ({
                    ...current,
                    components: [...current.components, { quantity: '9' }],
                })),
            );
            act(() => form?.submit('post', '/products'));

            expect(inertiaMock.submissions[0]?.data.components).toEqual([
                { quantity: '2.5' },
                { quantity: '9' },
            ]);

            act(() =>
                form?.setData({
                    name: 'Replaced',
                    photo: null,
                    components: [],
                }),
            );
            act(() => form?.submit('post', '/products'));

            expect(inertiaMock.submissions[1]?.data).toEqual({
                name: 'Replaced',
                photo: null,
                components: [],
            });
        });

        it('applies transform to what is sent, not to the form state', () => {
            let form: ProductFormApi | undefined;

            render(<ProductForm onForm={(f) => (form = f)} />);

            act(() => {
                form?.setData('name', 'denim');
                form?.transform((data) => ({
                    ...data,
                    name: data.name.toUpperCase(),
                }));
            });
            act(() => form?.submit('post', '/products'));

            expect(inertiaMock.submissions[0]?.data.name).toBe('DENIM');
            expect(screen.getByLabelText('Name')).toHaveValue('denim');
        });

        it('resets to the defaults, and setDefaults moves them', () => {
            let form: ProductFormApi | undefined;

            render(<ProductForm onForm={(f) => (form = f)} />);

            act(() => form?.setData('name', 'Changed'));
            expect(screen.getByText('dirty')).toBeInTheDocument();

            act(() => form?.reset());
            expect(screen.getByLabelText('Name')).toHaveValue('');
            expect(screen.getByText('clean')).toBeInTheDocument();

            act(() => form?.setData('name', 'Saved'));
            act(() => form?.setDefaults());
            expect(screen.getByText('clean')).toBeInTheDocument();

            act(() => form?.setData('name', 'Other'));
            act(() => form?.reset('name'));
            expect(screen.getByLabelText('Name')).toHaveValue('Saved');
        });

        it('reports the errors and processing set with setFormState', () => {
            setFormState({
                errors: { name: 'The name field is required.' },
                processing: true,
            });

            render(<ProductForm />);

            expect(screen.getByRole('alert')).toHaveTextContent(
                'The name field is required.',
            );
            expect(screen.getByText('Saving…')).toBeInTheDocument();
        });

        it('follows setFormState while mounted', () => {
            render(<ProductForm />);

            expect(screen.queryByRole('alert')).not.toBeInTheDocument();

            setFormState({ errors: { name: 'Taken.' } });

            expect(screen.getByRole('alert')).toHaveTextContent('Taken.');
        });

        it('lets the component set and clear errors itself', () => {
            let form: ProductFormApi | undefined;

            render(<ProductForm onForm={(f) => (form = f)} />);

            act(() => form?.setError('name', 'Taken.'));
            expect(screen.getByRole('alert')).toHaveTextContent('Taken.');

            act(() => form?.clearErrors());
            expect(screen.queryByRole('alert')).not.toBeInTheDocument();
        });

        it('clears recorded submissions on reset', async () => {
            const user = userEvent.setup();

            render(<ProductForm />);
            await user.click(screen.getByRole('button', { name: 'Save' }));
            expect(inertiaMock.submissions).toHaveLength(1);

            resetInertiaMock();
            expect(inertiaMock.submissions).toEqual([]);
        });
    });
});
