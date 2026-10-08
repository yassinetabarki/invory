/**
 * Test helper for mocking `@inertiajs/react` at the boundary.
 *
 * Usage (top of a test file; `vi.mock` must live in the test file so Vitest
 * can hoist it):
 *
 * ```tsx
 * import { beforeEach, vi } from 'vite-plus/test';
 * import { inertiaMock, resetInertiaMock, setPageProps } from '@/test/inertia';
 *
 * vi.mock('@inertiajs/react', async (importOriginal) => {
 *     const { createInertiaModule } = await import('@/test/inertia');
 *     return createInertiaModule(
 *         await importOriginal<typeof import('@inertiajs/react')>(),
 *     );
 * });
 *
 * beforeEach(() => resetInertiaMock());
 *
 * it('…', () => {
 *     setPageProps({ app: { currency: 'USD', locale: 'en' } });
 *     render(<Page />);
 *     expect(inertiaMock.router.get).toHaveBeenCalledWith(…);
 * });
 * ```
 *
 * Real exports are kept; `router`, `usePage`, `Head`, `Link` and `Form` are stubbed.
 *
 * Inertia's `<Form>` talks to the real router, so it is replaced by a plain
 * `<form>` whose render prop receives the state set with `setFormState`:
 *
 * ```tsx
 * setFormState({ errors: { sku: 'The sku has already been taken.' } });
 * render(<Create categories={…} units={…} />);
 * // the form's `action` / `method` come from the Wayfinder `.form()` helper,
 * // and a submit is recorded (not sent) in `inertiaMock.submissions`.
 * ```
 */
import type { AnchorHTMLAttributes, FormEvent, ReactNode } from 'react';
import { vi } from 'vite-plus/test';

export type TestPageProps = Record<string, unknown>;

export const defaultPageProps: TestPageProps = {
    name: 'Invory',
    auth: {
        user: {
            id: 1,
            name: 'Test User',
            email: 'test@example.com',
            email_verified_at: null,
            created_at: '2026-01-01T00:00:00.000000Z',
            updated_at: '2026-01-01T00:00:00.000000Z',
        },
    },
    sidebarOpen: true,
    app: { currency: 'EUR', locale: 'en' },
};

function createRouterMock() {
    return {
        get: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        patch: vi.fn(),
        delete: vi.fn(),
        visit: vi.fn(),
        reload: vi.fn(),
        replace: vi.fn(),
        cancel: vi.fn(),
        flush: vi.fn(),
        prefetch: vi.fn(),
        on: vi.fn(() => () => undefined),
    };
}

/** What the mocked `<Form>` hands to its render prop. */
export type MockFormState = {
    errors: Record<string, string>;
    processing: boolean;
};

/** A submit recorded by the mocked `<Form>` (nothing is sent). */
export type MockFormSubmission = {
    action: string;
    method: string;
    data: Record<string, string>;
};

type InertiaMockState = {
    props: TestPageProps;
    url: string;
    component: string;
    router: ReturnType<typeof createRouterMock>;
    form: MockFormState;
    submissions: MockFormSubmission[];
};

/** Mutable state read by the mocked `usePage()`; reset it between tests. */
export const inertiaMock: InertiaMockState = {
    props: { ...defaultPageProps },
    url: '/',
    component: 'Test',
    router: createRouterMock(),
    form: { errors: {}, processing: false },
    submissions: [],
};

/** Merge `props` over the defaults (so `app` is always present unless overridden). */
export function setPageProps(
    props: TestPageProps,
    page: { url?: string; component?: string } = {},
): void {
    inertiaMock.props = { ...defaultPageProps, ...props };
    inertiaMock.url = page.url ?? inertiaMock.url;
    inertiaMock.component = page.component ?? inertiaMock.component;
}

/** Set the `errors` / `processing` the mocked `<Form>` passes to its render prop. */
export function setFormState(state: Partial<MockFormState>): void {
    inertiaMock.form = { ...inertiaMock.form, ...state };
}

/** Restore default props and clear every router spy. Call in `beforeEach`. */
export function resetInertiaMock(): void {
    inertiaMock.props = { ...defaultPageProps };
    inertiaMock.url = '/';
    inertiaMock.component = 'Test';
    inertiaMock.form = { errors: {}, processing: false };
    inertiaMock.submissions = [];

    for (const spy of Object.values(inertiaMock.router)) {
        spy.mockClear();
    }
}

type LinkHref = string | { url: string };

type MockLinkProps = Omit<AnchorHTMLAttributes<HTMLAnchorElement>, 'href'> & {
    href: LinkHref;
    // Inertia-only props: accepted, never forwarded to the DOM.
    method?: string;
    as?: string;
    prefetch?: boolean | string | string[];
    preserveScroll?: boolean;
    preserveState?: boolean;
    replace?: boolean;
    only?: string[];
    data?: Record<string, unknown>;
    children?: ReactNode;
};

const INERTIA_ONLY_PROPS = new Set([
    'method',
    'as',
    'prefetch',
    'preserveScroll',
    'preserveState',
    'replace',
    'only',
    'data',
]);

function MockLink({ href, children, ...rest }: MockLinkProps) {
    const domProps = Object.fromEntries(
        Object.entries(rest).filter(([key]) => !INERTIA_ONLY_PROPS.has(key)),
    );

    return (
        <a href={typeof href === 'string' ? href : href.url} {...domProps}>
            {children}
        </a>
    );
}

function MockHead({ children }: { title?: string; children?: ReactNode }) {
    return <>{children}</>;
}

type MockFormSlot = MockFormState & {
    hasErrors: boolean;
    wasSuccessful: boolean;
    recentlySuccessful: boolean;
    isDirty: boolean;
};

type MockFormProps = {
    action?: string;
    method?: string;
    className?: string;
    children?: ReactNode | ((slot: MockFormSlot) => ReactNode);
    // Inertia-only props (options, transform, resetOnSuccess…) are accepted
    // and ignored.
    [inertiaOnly: string]: unknown;
};

function MockForm({ action, method, className, children }: MockFormProps) {
    const { errors, processing } = inertiaMock.form;

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        const data: Record<string, string> = {};

        new FormData(event.currentTarget).forEach((value, key) => {
            data[key] = typeof value === 'string' ? value : value.name;
        });

        inertiaMock.submissions.push({
            action: action ?? '',
            method: method ?? 'get',
            data,
        });
    }

    return (
        <form
            action={action}
            method={method}
            className={className}
            onSubmit={handleSubmit}
        >
            {typeof children === 'function'
                ? children({
                      errors,
                      processing,
                      hasErrors: Object.keys(errors).length > 0,
                      wasSuccessful: false,
                      recentlySuccessful: false,
                      isDirty: false,
                  })
                : children}
        </form>
    );
}

/**
 * Factory for `vi.mock('@inertiajs/react', …)`: keeps the real exports and
 * stubs `router`, `usePage`, `Head`, `Link` and `Form`.
 */
export function createInertiaModule<T extends object>(original: T) {
    return {
        ...original,
        router: inertiaMock.router,
        usePage: () => ({
            props: inertiaMock.props,
            url: inertiaMock.url,
            component: inertiaMock.component,
            version: null,
        }),
        Head: MockHead,
        Link: MockLink,
        Form: MockForm,
    };
}
