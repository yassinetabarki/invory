import { act, fireEvent, render, screen } from '@testing-library/react';
import { useState } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { useMaterialOptions } from '@/components/products/use-material-options';
import { materialOptions } from '@/test/fixtures/products';
import {
    ignoreHttpAbort,
    inertiaMock,
    resetInertiaMock,
    respondToHttp,
} from '@/test/inertia';
import type { MaterialOption } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const [rivet, canvas, , denim, button] = materialOptions;

type ProbeProps = {
    include?: number[];
    onLoaded?: (options: MaterialOption[]) => void;
};

/** Shows what the hook returns, and lets a test type the search text. */
function Probe({ include = [], onLoaded }: ProbeProps) {
    const [search, setSearch] = useState('');
    const { options, loading, error, retry } = useMaterialOptions({
        search,
        include,
        onLoaded,
    });

    return (
        <>
            <input
                aria-label="Search"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
            />
            <ul aria-label="Options">
                {options.map((option) => (
                    <li key={option.id}>{option.name}</li>
                ))}
            </ul>
            <p>{loading ? 'loading' : 'idle'}</p>
            {error && <p role="alert">failed</p>}
            <button type="button" onClick={retry}>
                Retry
            </button>
        </>
    );
}

function type(text: string) {
    fireEvent.change(screen.getByLabelText('Search'), {
        target: { value: text },
    });
}

/** Moves the fake clock and lets the promises it unblocks settle. */
async function advance(ms: number) {
    await act(async () => {
        await vi.advanceTimersByTimeAsync(ms);
    });
}

/** Answers a request the way the network would, inside React's act. */
async function answer(index: number, data: unknown) {
    await act(async () => {
        inertiaMock.http.requests[index].resolve(data);
    });
}

function names(): string[] {
    return screen
        .getAllByRole('listitem')
        .map((item) => item.textContent ?? '');
}

describe('useMaterialOptions', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        resetInertiaMock();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('loads the first page, with no search, as soon as it mounts', async () => {
        render(<Probe include={[5, 4]} />);

        expect(inertiaMock.http.requests).toHaveLength(1);

        const [request] = inertiaMock.http.requests;

        expect(request).toMatchObject({
            method: 'get',
            path: '/materials/options',
            // `include` goes up sorted, so reordering rows is not a new request.
            params: { limit: '20', include: ['4', '5'] },
        });
        expect(request.params).not.toHaveProperty('search');
        expect(screen.getByText('loading')).toBeInTheDocument();

        await answer(0, [denim, button]);

        expect(names()).toEqual([denim.name, button.name]);
        expect(screen.getByText('idle')).toBeInTheDocument();
    });

    it('leaves out `include` when nothing is picked yet', () => {
        render(<Probe />);

        expect(inertiaMock.http.requests[0].params).toEqual({ limit: '20' });
    });

    it('waits 250 ms after the last keystroke, then sends one request with search, limit and include', async () => {
        render(<Probe include={[4, 5]} />);
        await answer(0, [rivet]);

        type('d');
        await advance(100);
        type('de');
        await advance(100);
        type('den');
        await advance(249);

        // Still only the first page: the text has not rested yet.
        expect(inertiaMock.http.requests).toHaveLength(1);

        await advance(1);

        expect(inertiaMock.http.requests).toHaveLength(2);
        expect(inertiaMock.http.requests[1].params).toEqual({
            search: 'den',
            limit: '20',
            include: ['4', '5'],
        });
    });

    it('sends the trimmed text, and does not ask again for text that only gained spaces', async () => {
        render(<Probe />);
        await answer(0, [rivet]);

        type(' den ');
        await advance(250);

        expect(inertiaMock.http.requests).toHaveLength(2);
        expect(inertiaMock.http.requests[1].params.search).toBe('den');

        type('den   ');
        await advance(1000);

        expect(inertiaMock.http.requests).toHaveLength(2);
    });

    it('asks for the first page again when the search is cleared', async () => {
        render(<Probe />);
        await answer(0, [rivet]);

        type('den');
        await advance(250);
        await answer(1, [denim]);
        type('');
        await advance(250);

        expect(inertiaMock.http.requests).toHaveLength(3);
        expect(inertiaMock.http.requests[2].params).toEqual({ limit: '20' });
    });

    it('is loading while the debounce runs and while a request is in flight', async () => {
        render(<Probe />);
        await answer(0, [rivet]);

        expect(screen.getByText('idle')).toBeInTheDocument();

        type('den');

        // Typed, not asked yet.
        expect(screen.getByText('loading')).toBeInTheDocument();

        await advance(250);

        // Asked, not answered yet.
        expect(screen.getByText('loading')).toBeInTheDocument();
        // The previous answer stays on screen meanwhile.
        expect(names()).toEqual([rivet.name]);

        await answer(1, [denim]);

        expect(screen.getByText('idle')).toBeInTheDocument();
        expect(names()).toEqual([denim.name]);
    });

    describe('last request wins', () => {
        it('aborts the request in flight when a newer one starts', async () => {
            render(<Probe />);

            type('den');
            await advance(250);

            expect(inertiaMock.http.requests.map((r) => r.status)).toEqual([
                'cancelled',
                'pending',
            ]);
        });

        it('ignores a slow answer that arrives after a newer one', async () => {
            render(<Probe />);

            type('den');
            await advance(250);

            const [slow, fast] = inertiaMock.http.requests;

            await act(async () => {
                fast.resolve([denim]);
                slow.resolve([rivet, canvas]);
            });

            expect(names()).toEqual([denim.name]);
        });

        it('ignores a slow answer even when the abort did not reach its request', async () => {
            ignoreHttpAbort();
            render(<Probe />);

            type('den');
            await advance(250);

            const [slow, fast] = inertiaMock.http.requests;

            expect(slow.status).toBe('pending');

            await act(async () => {
                fast.resolve([denim]);
                slow.resolve([rivet, canvas]);
            });

            expect(names()).toEqual([denim.name]);
        });

        it('ignores an answer to a request that was already replaced, and does not report it as loaded', async () => {
            const onLoaded = vi.fn();

            render(<Probe onLoaded={onLoaded} />);

            type('den');
            await advance(250);
            await answer(0, [rivet]);

            expect(onLoaded).not.toHaveBeenCalled();
            expect(screen.getByText('loading')).toBeInTheDocument();

            await answer(1, [denim]);

            expect(onLoaded).toHaveBeenCalledExactlyOnceWith([denim]);
        });

        it('aborts the request when the picker closes (the hook unmounts)', () => {
            const { unmount } = render(<Probe />);

            unmount();

            expect(inertiaMock.http.requests[0].status).toBe('cancelled');
        });
    });

    describe('errors', () => {
        it('reports a failed request, with no options', async () => {
            render(<Probe />);
            await answer(0, [rivet]);
            type('den');
            await advance(250);

            await act(async () => {
                inertiaMock.http.requests[1].reject(new Error('Offline'));
            });

            expect(screen.getByRole('alert')).toHaveTextContent('failed');
            expect(screen.getByText('idle')).toBeInTheDocument();
            expect(screen.queryAllByRole('listitem')).toHaveLength(0);
        });

        it('treats a reply that is not a list (a rejected query) as a failure', async () => {
            render(<Probe />);

            await answer(0, undefined);

            expect(screen.getByRole('alert')).toBeInTheDocument();
        });

        it('asks the same thing again on retry, and recovers', async () => {
            render(<Probe include={[4]} />);

            await act(async () => {
                inertiaMock.http.requests[0].reject();
            });
            fireEvent.click(screen.getByRole('button', { name: 'Retry' }));

            expect(inertiaMock.http.requests).toHaveLength(2);
            expect(inertiaMock.http.requests[1].url).toBe(
                inertiaMock.http.requests[0].url,
            );
            expect(screen.queryByRole('alert')).not.toBeInTheDocument();
            expect(screen.getByText('loading')).toBeInTheDocument();

            await answer(1, [denim]);

            expect(names()).toEqual([denim.name]);
            expect(screen.queryByRole('alert')).not.toBeInTheDocument();
        });

        it('clears the error as soon as the person types something new', async () => {
            render(<Probe />);

            await act(async () => {
                inertiaMock.http.requests[0].reject();
            });
            type('den');
            await advance(250);

            expect(screen.queryByRole('alert')).not.toBeInTheDocument();

            await answer(1, [denim]);

            expect(names()).toEqual([denim.name]);
        });
    });

    it('does not ask again when the caller re-renders with a new but equal include', async () => {
        const { rerender } = render(<Probe include={[4, 5]} />);

        await answer(0, [denim]);
        rerender(<Probe include={[5, 4]} />);
        await advance(1000);

        expect(inertiaMock.http.requests).toHaveLength(1);
    });

    it('answers from a handler as well (the default test setup)', async () => {
        respondToHttp(() => [denim]);
        render(<Probe />);
        await advance(0);

        expect(names()).toEqual([denim.name]);
    });
});
