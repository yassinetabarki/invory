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
 * Real exports are kept; `router`, `usePage`, `Head`, `Link`, `Form`,
 * `Deferred`, `useForm` and `useHttp` are stubbed.
 *
 * ## `<Link>`
 *
 * The mocked `<Link>` renders a plain `<a>`. Its Inertia-only props are not
 * forwarded, except these, which are visible as attributes so a test can
 * assert them: `prefetch` → `data-prefetch` and `only` → `data-only`
 * (comma-separated).
 *
 * ```tsx
 * expect(screen.getByRole('link', { name: 'Add material' })).not.toHaveAttribute(
 *     'data-prefetch',
 * );
 * ```
 *
 * ## Inertia `<Form>`
 *
 * The real `<Form>` talks to the real router, so it is replaced by a plain
 * `<form>` whose render prop receives the state set with `setFormState`:
 *
 * ```tsx
 * setFormState({ errors: { sku: 'The sku has already been taken.' } });
 * render(<Create categories={…} units={…} />);
 * // the form's `action` / `method` come from the Wayfinder `.form()` helper,
 * // and a submit is recorded (not sent) in `inertiaMock.submissions`.
 * ```
 *
 * A recorded submission is `{ action, method, data }`: text fields as strings
 * and a chosen file input as its `File` (`''` when nothing was chosen).
 *
 * ## `<Deferred>`
 *
 * `<Deferred data="recent_history" fallback={…}>` shows the fallback while the
 * prop is absent from the page props and its children once it is present.
 * Mounted components follow `setPageProps` (and `setFormState`) like they
 * follow a real Inertia visit, so no `rerender` is needed to see the switch:
 *
 * ```tsx
 * setPageProps({ materials });                       // `recent_history` not loaded yet
 * render(<Index {...props} />);
 * expect(screen.getByText('Loading history…')).toBeInTheDocument();
 *
 * setPageProps({ materials, recent_history: [] });   // the deferred request landed
 * expect(screen.queryByText('Loading history…')).not.toBeInTheDocument();
 * ```
 *
 * `data` may be an array (all keys must be present) or a dotted path
 * (`'stats.revenue'`).
 *
 * A key listed in the page's `rescuedProps` (a deferred prop whose load failed
 * on the server) shows `rescue` (a node, or a function of `{ reloading }`;
 * `reloading` is always `false` here) instead of the children, like Inertia.
 * Without a `rescue` it shows the fallback. Set it with
 * `setPageProps(props, { rescuedProps: ['finished_stock'] })`; it stays until
 * you pass another list (`[]` clears it) or call `resetInertiaMock()`.
 *
 * ## `useForm`
 *
 * `useForm` keeps real state behaviour (`data`, `setData` with a key, a dotted
 * path, an object or an updater, `errors`, `isDirty`, `reset`, `setDefaults`,
 * `transform`) but sends nothing: `submit()`, `get()`, `post()`, `put()`,
 * `patch()` and `delete()` record `{ action, method, data, options }` in
 * `inertiaMock.submissions`. `data` is exactly what Inertia would send
 * (after `transform`), so files stay `File` objects. Callbacks in `options`
 * (`onSuccess`, `onFinish`…) are recorded, never run.
 *
 * ```tsx
 * render(<ProductForm … />);
 * await user.type(screen.getByLabelText('Name'), 'Denim Jacket');
 * await user.click(screen.getByRole('button', { name: 'Save product' }));
 *
 * expect(inertiaMock.submissions).toHaveLength(1);
 * expect(inertiaMock.submissions[0]).toMatchObject({
 *     action: '/products/12',            // ProductController.update(12).url
 *     method: 'put',
 *     data: { name: 'Denim Jacket', photo: expect.any(File) },
 * });
 * ```
 *
 * `setFormState({ errors, processing })` also drives `useForm`'s `errors`
 * and `processing` (nested keys such as `'components.0.quantity'` work as
 * keys). Errors set by the component (`setError`, `clearErrors`) take over from
 * then on.
 *
 * ## `useHttp`
 *
 * `useHttp` makes no network call either. `get()`, `post()`, `put()`,
 * `patch()`, `delete()` and `submit()` record a request in
 * `inertiaMock.http.requests` and return a promise for it. A request knows its
 * `method`, `url` (with the query), `path` and `params` (the query as an
 * object: `include[]=4&include[]=5` is `include: ['4', '5']`).
 *
 * Answer requests in one of two ways:
 *
 * ```tsx
 * // 1. A handler answers every request on its own (data, a promise, or a throw):
 * respondToHttp(({ params }) => materialsMatching(params.search));
 *
 * // 2. No handler: requests wait, and the test answers them (in any order):
 * const [first, second] = inertiaMock.http.requests;
 * second.resolve([…]);
 * first.reject(); // a network failure; a later first.resolve(…) is ignored
 * ```
 *
 * `cancel()` (called by the component) rejects the hook's pending requests
 * with a "Request was cancelled" error and marks them `status: 'cancelled'`
 * (`ignoreHttpAbort()` turns that off).
 * `status` is `'pending' | 'resolved' | 'rejected' | 'cancelled'`. Of the
 * request options only `onSuccess` and `onFinish` are called.
 */
import { act } from '@testing-library/react';
import { useRef, useState, useSyncExternalStore } from 'react';
import type { AnchorHTMLAttributes, FormEvent, ReactNode } from 'react';
import { vi } from 'vite-plus/test';
import { referenceData } from '@/test/fixtures/reference';

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
    app: { currency: 'EUR', locale: 'en', today: '2026-10-08' },
    // The shared once-prop: the seeded units and categories (override it with setPageProps).
    reference: referenceData(),
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

/** What the mocked `<Form>` and `useForm` get as `errors` / `processing`. */
export type MockFormState = {
    errors: Record<string, string>;
    processing: boolean;
};

/**
 * A submit recorded by the mocked `<Form>` or `useForm` (nothing is sent).
 * `<Form>` data: strings, and a `File` per chosen file input (`''` when none).
 * `useForm` data: the values as they were in the form (after `transform`).
 */
export type MockFormSubmission = {
    action: string;
    method: string;
    data: Record<string, unknown>;
    /** `useForm` only: the options passed to `submit()` / `post()`… */
    options?: Record<string, unknown>;
};

/** One request made through the mocked `useHttp` (see "useHttp" above). */
export type MockHttpRequest = {
    method: string;
    /** The URL as requested, query included. */
    url: string;
    path: string;
    /** The query: `key[]` parameters are arrays. */
    params: Record<string, string | string[]>;
    status: 'pending' | 'resolved' | 'rejected' | 'cancelled';
    /** Answer the request (ignored once it has been answered or cancelled). */
    resolve: (data: unknown) => void;
    /** Fail the request, like a network error (ignored once answered or cancelled). */
    reject: (error?: Error) => void;
};

type HttpHandler = (request: MockHttpRequest) => unknown;

type InertiaMockState = {
    props: TestPageProps;
    url: string;
    component: string;
    rescuedProps: string[];
    router: ReturnType<typeof createRouterMock>;
    form: MockFormState;
    submissions: MockFormSubmission[];
    http: {
        requests: MockHttpRequest[];
        handler: HttpHandler | null;
        abortWorks: boolean;
    };
};

/** Mutable state read by the mocked `usePage()`; reset it between tests. */
export const inertiaMock: InertiaMockState = {
    props: { ...defaultPageProps },
    url: '/',
    component: 'Test',
    rescuedProps: [],
    router: createRouterMock(),
    form: { errors: {}, processing: false },
    submissions: [],
    http: { requests: [], handler: null, abortWorks: true },
};

// Components read the page and form state through `useSyncExternalStore`, so a
// change made while a component is mounted re-renders it, like a real page
// visit would (and React Compiler's memoised elements cannot hide it).
const listeners = new Set<() => void>();

function subscribe(listener: () => void): () => void {
    listeners.add(listener);

    return () => {
        listeners.delete(listener);
    };
}

function notify(): void {
    if (listeners.size === 0) {
        return;
    }

    act(() => {
        for (const listener of listeners) {
            listener();
        }
    });
}

/** Merge `props` over the defaults (so `app` is always present unless overridden). */
export function setPageProps(
    props: TestPageProps,
    page: { url?: string; component?: string; rescuedProps?: string[] } = {},
): void {
    inertiaMock.props = { ...defaultPageProps, ...props };
    inertiaMock.url = page.url ?? inertiaMock.url;
    inertiaMock.component = page.component ?? inertiaMock.component;
    inertiaMock.rescuedProps = page.rescuedProps ?? inertiaMock.rescuedProps;
    notify();
}

/** Set the `errors` / `processing` the mocked `<Form>` and `useForm` report. */
export function setFormState(state: Partial<MockFormState>): void {
    inertiaMock.form = { ...inertiaMock.form, ...state };
    notify();
}

/**
 * Make the mocked `useHttp` answer every request with what `handler` returns
 * (data, a promise of it, or a throw to fail the request).
 */
export function respondToHttp(handler: HttpHandler): void {
    inertiaMock.http.handler = handler;
}

/**
 * Make `cancel()` of the mocked `useHttp` do nothing, so a test can check that
 * a component also ignores the answer of a request it asked to abort (the
 * real hook only aborts the request it started last).
 */
export function ignoreHttpAbort(): void {
    inertiaMock.http.abortWorks = false;
}

/** Restore default props and clear every router spy. Call in `beforeEach`. */
export function resetInertiaMock(): void {
    inertiaMock.props = { ...defaultPageProps };
    inertiaMock.url = '/';
    inertiaMock.component = 'Test';
    inertiaMock.rescuedProps = [];
    inertiaMock.form = { errors: {}, processing: false };
    inertiaMock.submissions = [];
    inertiaMock.http = { requests: [], handler: null, abortWorks: true };

    for (const spy of Object.values(inertiaMock.router)) {
        spy.mockClear();
    }

    notify();
}

type PageSnapshot = {
    props: TestPageProps;
    url: string;
    component: string;
    rescuedProps: string[];
    version: null;
};

let pageSnapshot: PageSnapshot | null = null;

/** The page as `usePage()` returns it; the same object until something changes. */
function getPageSnapshot(): PageSnapshot {
    if (
        pageSnapshot === null ||
        pageSnapshot.props !== inertiaMock.props ||
        pageSnapshot.url !== inertiaMock.url ||
        pageSnapshot.component !== inertiaMock.component ||
        pageSnapshot.rescuedProps !== inertiaMock.rescuedProps
    ) {
        pageSnapshot = {
            props: inertiaMock.props,
            url: inertiaMock.url,
            component: inertiaMock.component,
            rescuedProps: inertiaMock.rescuedProps,
            version: null,
        };
    }

    return pageSnapshot;
}

function usePageMock(): PageSnapshot {
    return useSyncExternalStore(subscribe, getPageSnapshot);
}

function useFormStateMock(): MockFormState {
    return useSyncExternalStore(subscribe, () => inertiaMock.form);
}

// ---------------------------------------------------------------------------
// Small guards shared by the mocks
// ---------------------------------------------------------------------------

type Fields = Record<string, unknown>;

function isFunction(value: unknown): value is (...args: unknown[]) => unknown {
    return typeof value === 'function';
}

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function isPlainObject(value: unknown): value is Record<string, unknown> {
    return isRecord(value) && Object.getPrototypeOf(value) === Object.prototype;
}

/** Reads a dotted path (`stats.revenue`, `components.0.quantity`). */
function getIn(source: unknown, path: string): unknown {
    return path.split('.').reduce<unknown>((current, key) => {
        if (Array.isArray(current)) {
            return current[Number(key)];
        }

        return isRecord(current) ? current[key] : undefined;
    }, source);
}

/** Immutable `set` along a dotted path, creating objects on the way. */
function setIn(source: unknown, keys: string[], value: unknown): unknown {
    const [head, ...rest] = keys;

    if (head === undefined) {
        return value;
    }

    if (Array.isArray(source)) {
        const copy: unknown[] = [...source];
        const index = Number(head);

        copy[index] = setIn(copy[index], rest, value);

        return copy;
    }

    const record = isRecord(source) ? source : {};

    return { ...record, [head]: setIn(record[head], rest, value) };
}

/** Deep equality for form data; files and other class instances compare by identity. */
function isEqual(a: unknown, b: unknown): boolean {
    if (Object.is(a, b)) {
        return true;
    }

    if (Array.isArray(a) && Array.isArray(b)) {
        return a.length === b.length && a.every((v, i) => isEqual(v, b[i]));
    }

    if (isPlainObject(a) && isPlainObject(b)) {
        const keys = Object.keys(a);

        return (
            keys.length === Object.keys(b).length &&
            keys.every((key) => key in b && isEqual(a[key], b[key]))
        );
    }

    return false;
}

// ---------------------------------------------------------------------------
// Link, Head
// ---------------------------------------------------------------------------

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
    cacheFor?: string | number | (string | number)[];
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
    'cacheFor',
    'data',
]);

function joined(value: string | string[] | undefined): string | undefined {
    return value === undefined ? undefined : [value].flat().join(',');
}

function MockLink({ href, children, ...rest }: MockLinkProps) {
    const { prefetch, only } = rest;
    const domProps = Object.fromEntries(
        Object.entries(rest).filter(([key]) => !INERTIA_ONLY_PROPS.has(key)),
    );

    return (
        <a
            href={typeof href === 'string' ? href : href.url}
            data-prefetch={
                prefetch === undefined || prefetch === false
                    ? undefined
                    : String(prefetch)
            }
            data-only={joined(only)}
            {...domProps}
        >
            {children}
        </a>
    );
}

function MockHead({ children }: { title?: string; children?: ReactNode }) {
    return <>{children}</>;
}

// ---------------------------------------------------------------------------
// <Form>
// ---------------------------------------------------------------------------

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

/** The form's data the way a browser submits it, with chosen files as `File`s. */
function readFormData(form: HTMLFormElement): Fields {
    const data: Fields = {};

    new FormData(form).forEach((value, key) => {
        if (typeof value === 'string') {
            data[key] = value;

            return;
        }

        // `FormData` cannot see files that `userEvent.upload` put on an input,
        // so file entries are read from the input itself.
        const field = form.elements.namedItem(key);
        const chosen =
            field instanceof HTMLInputElement ? field.files?.item(0) : null;

        data[key] = chosen ?? '';
    });

    return data;
}

function MockForm({ action, method, className, children }: MockFormProps) {
    const { errors, processing } = useFormStateMock();

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        inertiaMock.submissions.push({
            action: action ?? '',
            method: method ?? 'get',
            data: readFormData(event.currentTarget),
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

// ---------------------------------------------------------------------------
// <Deferred>
// ---------------------------------------------------------------------------

type MockDeferredProps = {
    data: string | string[];
    fallback: ReactNode | (() => ReactNode);
    children: ReactNode | ((slot: { reloading: boolean }) => ReactNode);
    rescue?: ReactNode | ((slot: { reloading: boolean }) => ReactNode);
};

function MockDeferred({ data, fallback, children, rescue }: MockDeferredProps) {
    if (!data) {
        throw new Error(
            '`<Deferred>` requires a `data` prop to be a string or array of strings',
        );
    }

    if (!fallback) {
        throw new Error('`<Deferred>` requires a `fallback` prop');
    }

    const { props, rescuedProps } = usePageMock();
    const keys = Array.isArray(data) ? data : [data];
    const loaded = keys.every((key) => getIn(props, key) !== undefined);
    const rescued = keys.some((key) => rescuedProps.includes(key));

    if (loaded && !rescued) {
        return (
            <>
                {typeof children === 'function'
                    ? children({ reloading: false })
                    : children}
            </>
        );
    }

    if (rescued && rescue) {
        return (
            <>
                {typeof rescue === 'function'
                    ? rescue({ reloading: false })
                    : rescue}
            </>
        );
    }

    return <>{typeof fallback === 'function' ? fallback() : fallback}</>;
}

// ---------------------------------------------------------------------------
// useForm
// ---------------------------------------------------------------------------

type FormTarget = { method?: string; url?: string };

function resolveFields(value: unknown): Fields {
    const resolved = isFunction(value) ? value() : value;

    return isRecord(resolved) ? resolved : {};
}

function toTarget(pair: unknown): FormTarget {
    if (!isRecord(pair)) {
        return {};
    }

    return {
        method:
            typeof pair.method === 'string'
                ? pair.method.toLowerCase()
                : undefined,
        url: typeof pair.url === 'string' ? pair.url : undefined,
    };
}

/** The overloads of `useForm`: (data), (rememberKey, data), (pair, data), (method, url, data). */
function parseUseFormArgs(args: unknown[]): {
    initial: Fields;
    target: FormTarget;
} {
    if (args.length >= 3) {
        const [method, url, data] = args;

        return {
            initial: resolveFields(data),
            target: toTarget({
                method: isFunction(method) ? method() : method,
                url: isFunction(url) ? url() : url,
            }),
        };
    }

    if (args.length === 2) {
        const [first, data] = args;

        return {
            initial: resolveFields(data),
            // A string first argument is the remember key: no target.
            target:
                typeof first === 'string'
                    ? {}
                    : toTarget(isFunction(first) ? first() : first),
        };
    }

    return { initial: resolveFields(args[0]), target: {} };
}

/** The overloads of `submit`: (pair, options?), (method, url, options?), (options?). */
function parseSubmitArgs(
    args: unknown[],
    fallback: FormTarget,
): { method: string; url: string; options?: Record<string, unknown> } {
    const [first, second, third] = args;

    if (typeof first === 'string') {
        return {
            method: first.toLowerCase(),
            url: typeof second === 'string' ? second : '',
            options: isRecord(third) ? third : undefined,
        };
    }

    if (isRecord(first) && typeof first.url === 'string') {
        const target = toTarget(first);

        return {
            method: target.method ?? fallback.method ?? 'get',
            url: first.url,
            options: isRecord(second) ? second : undefined,
        };
    }

    return {
        method: fallback.method ?? 'get',
        url: fallback.url ?? '',
        options: isRecord(first) ? first : undefined,
    };
}

/**
 * A stand-in for Inertia's `useForm`: real local state, no network.
 * Submissions are recorded in `inertiaMock.submissions`.
 */
export function useFormMock(...args: unknown[]) {
    const [config] = useState(() => parseUseFormArgs(args));
    const [defaults, setDefaultsState] = useState<Fields>(config.initial);
    const [data, setDataState] = useState<Fields>(config.initial);
    const [ownErrors, setOwnErrors] = useState<Record<string, string> | null>(
        null,
    );
    const dataRef = useRef<Fields>(data);
    const transformRef = useRef<(values: Fields) => Fields>((values) => values);

    const formState = useFormStateMock();
    const errors = ownErrors ?? formState.errors;

    function commit(next: Fields) {
        dataRef.current = next;
        setDataState(next);
    }

    function setData(...params: unknown[]) {
        const [first, second] = params;

        if (typeof first === 'string') {
            const updated = setIn(dataRef.current, first.split('.'), second);

            commit(isRecord(updated) ? updated : dataRef.current);
        } else if (isFunction(first)) {
            const updated = first(dataRef.current);

            commit(isRecord(updated) ? updated : dataRef.current);
        } else if (isRecord(first)) {
            commit(first);
        }
    }

    function setDefaults(...params: unknown[]) {
        const [first, second] = params;

        if (first === undefined) {
            setDefaultsState(dataRef.current);
        } else if (typeof first === 'string') {
            const updated = setIn(defaults, first.split('.'), second);

            setDefaultsState(isRecord(updated) ? updated : defaults);
        } else if (isRecord(first)) {
            setDefaultsState({ ...defaults, ...first });
        }
    }

    function reset(...fields: string[]) {
        if (fields.length === 0) {
            commit(defaults);

            return;
        }

        commit(
            fields.reduce<Fields>((current, field) => {
                const restored = setIn(
                    current,
                    field.split('.'),
                    getIn(defaults, field),
                );

                return isRecord(restored) ? restored : current;
            }, dataRef.current),
        );
    }

    function setError(...params: unknown[]) {
        const [first, second] = params;

        if (typeof first === 'string') {
            setOwnErrors({ ...errors, [first]: String(second) });
        } else if (isRecord(first)) {
            setOwnErrors(
                Object.fromEntries(
                    Object.entries(first).map(([key, value]) => [
                        key,
                        String(value),
                    ]),
                ),
            );
        }
    }

    function clearErrors(...fields: string[]) {
        setOwnErrors(
            fields.length === 0
                ? {}
                : Object.fromEntries(
                      Object.entries(errors).filter(
                          ([key]) => !fields.includes(key),
                      ),
                  ),
        );
    }

    function record(
        method: string,
        url: string,
        options?: Record<string, unknown>,
    ) {
        inertiaMock.submissions.push({
            action: url,
            method,
            data: transformRef.current(dataRef.current),
            ...(options ? { options } : {}),
        });
    }

    function shortcut(method: string) {
        return (url: string, options?: Record<string, unknown>) => {
            record(method, url, options);
        };
    }

    const form = {
        data,
        isDirty: !isEqual(data, defaults),
        errors,
        hasErrors: Object.keys(errors).length > 0,
        processing: formState.processing,
        progress: null,
        wasSuccessful: false,
        recentlySuccessful: false,
        setData,
        transform: (callback: (values: Fields) => Fields) => {
            transformRef.current = callback;
        },
        setDefaults,
        reset,
        clearErrors,
        resetAndClearErrors: (...fields: string[]) => {
            reset(...fields);
            clearErrors(...fields);
        },
        setError,
        submit: (...submitArgs: unknown[]) => {
            const { method, url, options } = parseSubmitArgs(
                submitArgs,
                config.target,
            );

            record(method, url, options);
        },
        get: shortcut('get'),
        post: shortcut('post'),
        put: shortcut('put'),
        patch: shortcut('patch'),
        delete: shortcut('delete'),
        cancel: () => undefined,
        // Chainable in Inertia (they return the form); the mock keeps them inert.
        dontRemember: (): unknown => form,
        optimistic: (): unknown => form,
    };

    return form;
}

// ---------------------------------------------------------------------------
// useHttp
// ---------------------------------------------------------------------------

function queryParams(url: string): Record<string, string | string[]> {
    const params: Record<string, string | string[]> = {};

    for (const [name, value] of new URL(url, 'http://localhost').searchParams) {
        if (name.endsWith('[]')) {
            const key = name.slice(0, -2);
            const list = params[key];

            params[key] = [...(Array.isArray(list) ? list : []), value];
        } else {
            params[name] = value;
        }
    }

    return params;
}

/** The overloads of `submit`: (method, url, options), (pair, options). */
function parseHttpSubmitArgs(args: unknown[]): {
    method: string;
    url: string;
    options: Fields;
} {
    const [first, second, third] = args;

    if (typeof first === 'string' && typeof second === 'string') {
        return {
            method: first.toLowerCase(),
            url: second,
            options: isRecord(third) ? third : {},
        };
    }

    const { method, url } = toTarget(first);

    if (method === undefined || url === undefined) {
        throw new Error('The mocked useHttp needs a method and a URL.');
    }

    return { method, url, options: isRecord(second) ? second : {} };
}

/**
 * A stand-in for Inertia's `useHttp`: no network. Requests are recorded in
 * `inertiaMock.http.requests` and answered by `respondToHttp` or by the test.
 */
export function useHttpMock() {
    const [processing, setProcessing] = useState(false);
    const [response, setResponse] = useState<unknown>(null);
    // Created once, so the functions keep their identity across renders like the real ones.
    const [api] = useState(() => {
        // The pending requests of this hook, each with the way to cancel it.
        const pending = new Map<MockHttpRequest, () => void>();

        function submit(...args: unknown[]): Promise<unknown> {
            const { method, url, options } = parseHttpSubmitArgs(args);

            return new Promise((resolvePromise, rejectPromise) => {
                function settle(
                    status: 'resolved' | 'rejected' | 'cancelled',
                    run: () => void,
                ) {
                    if (request.status !== 'pending') {
                        return;
                    }

                    request.status = status;
                    pending.delete(request);
                    setProcessing(pending.size > 0);
                    run();

                    if (isFunction(options.onFinish)) {
                        options.onFinish();
                    }
                }

                const request: MockHttpRequest = {
                    method,
                    url,
                    path: new URL(url, 'http://localhost').pathname,
                    params: queryParams(url),
                    status: 'pending',
                    resolve: (data) =>
                        settle('resolved', () => {
                            setResponse(data);

                            if (isFunction(options.onSuccess)) {
                                options.onSuccess(data);
                            }

                            resolvePromise(data);
                        }),
                    reject: (error = new Error('Network error')) =>
                        settle('rejected', () => rejectPromise(error)),
                };

                pending.set(request, () =>
                    settle('cancelled', () =>
                        rejectPromise(new Error('Request was cancelled')),
                    ),
                );
                inertiaMock.http.requests.push(request);
                setProcessing(true);

                const { handler } = inertiaMock.http;

                if (handler) {
                    Promise.resolve()
                        .then(() => handler(request))
                        .then(request.resolve, (error: unknown) =>
                            request.reject(
                                error instanceof Error
                                    ? error
                                    : new Error('Request failed'),
                            ),
                        );
                }
            });
        }

        const shortcut = (method: string) => (url: string, options?: unknown) =>
            submit(method, url, options);

        return {
            submit,
            get: shortcut('get'),
            post: shortcut('post'),
            put: shortcut('put'),
            patch: shortcut('patch'),
            delete: shortcut('delete'),
            cancel: () => {
                if (!inertiaMock.http.abortWorks) {
                    return;
                }

                for (const cancelRequest of pending.values()) {
                    cancelRequest();
                }
            },
        };
    });

    return { ...api, processing, response, data: {}, errors: {} };
}

/**
 * Factory for `vi.mock('@inertiajs/react', …)`: keeps the real exports and
 * stubs `router`, `usePage`, `Head`, `Link`, `Form`, `Deferred` and `useForm`.
 */
export function createInertiaModule<T extends object>(original: T) {
    return {
        ...original,
        router: inertiaMock.router,
        usePage: usePageMock,
        Head: MockHead,
        Link: MockLink,
        Form: MockForm,
        Deferred: MockDeferred,
        useForm: useFormMock,
        useHttp: useHttpMock,
    };
}
