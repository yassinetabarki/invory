import { act, renderHook } from '@testing-library/react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import {
    PREVIEW_DEBOUNCE_MS,
    useMovePreview,
} from '@/components/orders/use-move-preview';
import { ignoreHttpAbort, inertiaMock, resetInertiaMock } from '@/test/inertia';
import type { MovePreview, OrderLineMove } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function previewOf(units: number): MovePreview {
    return {
        move: 'start_cutting',
        units,
        rows: [],
        has_composition: true,
        nothing_taken: false,
        max_cuttable: 112,
        short_message: null,
    };
}

function setup(move: OrderLineMove = 'start_cutting') {
    return renderHook(
        ({ units }) => useMovePreview({ order: 7, line: 70, move, units }),
        { initialProps: { units: '' } },
    );
}

async function rest() {
    await act(async () => {
        await vi.advanceTimersByTimeAsync(PREVIEW_DEBOUNCE_MS);
    });
}

async function answer(index: number, data: unknown) {
    await act(async () => {
        inertiaMock.http.requests[index].resolve(data);
    });
}

describe('useMovePreview', () => {
    beforeEach(() => {
        resetInertiaMock();
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('asks the server once the number typed rests, and has nothing to show before it answers', async () => {
        const { result, rerender } = setup();

        expect(result.current.status).toBe('idle');

        rerender({ units: '3' });
        rerender({ units: '30' });

        // Still typing: nothing asked, and nothing shown.
        await act(async () => {
            await vi.advanceTimersByTimeAsync(PREVIEW_DEBOUNCE_MS - 1);
        });
        expect(inertiaMock.http.requests).toHaveLength(0);
        expect(result.current.status).toBe('loading');

        await rest();
        expect(inertiaMock.http.requests).toHaveLength(1);
        expect(inertiaMock.http.requests[0]).toMatchObject({
            method: 'get',
            path: '/orders/7/lines/70/moves/preview',
            params: { move: 'start_cutting', units: '30' },
        });
        expect(result.current.status).toBe('loading');

        await answer(0, previewOf(30));
        expect(result.current).toEqual({
            status: 'ready',
            preview: previewOf(30),
        });
    });

    it('follows the number typed, and a slow early answer never overwrites a newer one', async () => {
        // The real hook aborts only the request it started last: ignore the abort so the stale answer still arrives.
        ignoreHttpAbort();
        const { result, rerender } = setup();

        rerender({ units: '3' });
        await rest();
        rerender({ units: '30' });
        await rest();
        expect(inertiaMock.http.requests.map((r) => r.params.units)).toEqual([
            '3',
            '30',
        ]);

        await answer(1, previewOf(30));
        await answer(0, previewOf(3));

        expect(result.current).toEqual({
            status: 'ready',
            preview: previewOf(30),
        });
    });

    it('asks nothing for a move without a table or an empty field', async () => {
        const { result, rerender } = setup('mark_ready');

        rerender({ units: '5' });
        await rest();

        expect(result.current.status).toBe('idle');
        expect(inertiaMock.http.requests).toHaveLength(0);
    });
});
