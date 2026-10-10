import { act, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import {
    MaterialSearch,
    matchesSearch,
} from '@/components/products/material-search';
import { materialOptions } from '@/test/fixtures/products';
import { inertiaMock, resetInertiaMock } from '@/test/inertia';
import type { MaterialOption } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function find(id: number): MaterialOption {
    const found = materialOptions.find((option) => option.id === id);

    if (!found) {
        throw new Error(`No fixture material ${id}.`);
    }

    return found;
}

const rivet = find(1);
const denim = find(4);
const button = find(5);
const zipper = find(6);
const thread = find(8);

type SetupOptions = {
    value?: number | '';
    usedIds?: number[];
    include?: number[];
};

function setup({ value = '', usedIds = [], include }: SetupOptions = {}) {
    const onLoaded = vi.fn();
    const onSelect = vi.fn();
    const user = userEvent.setup();

    render(
        <MaterialSearch
            value={value}
            usedIds={new Set(usedIds)}
            include={include ?? usedIds}
            onLoaded={onLoaded}
            onSelect={onSelect}
        />,
    );

    return { user, onLoaded, onSelect };
}

/** Answers a request the way the network would, inside React's act. */
async function answer(index: number, data: unknown) {
    await act(async () => {
        inertiaMock.http.requests[index].resolve(data);
    });
}

function optionNames(): string[] {
    return screen
        .queryAllByRole('option')
        .map((option) => option.textContent ?? '');
}

describe('matchesSearch (the word matcher that refines the server list)', () => {
    const text = 'Metal shank button 17mm MAT-BTN-017';

    it('needs every word, in any order, in the name or SKU', () => {
        expect(matchesSearch(text, 'button metal')).toBe(1);
        expect(matchesSearch(text, 'btn-017')).toBe(1);
        expect(matchesSearch(text, 'button zipper')).toBe(0);
    });

    it('shows everything for an empty search and never matches scattered letters', () => {
        expect(matchesSearch(text, '  ')).toBe(1);
        expect(matchesSearch(text, 'mbtz')).toBe(0);
    });
});

describe('<MaterialSearch>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    describe('loading', () => {
        it('asks for the first page as soon as it opens and says it is searching', () => {
            setup({ usedIds: [4] });

            expect(inertiaMock.http.requests).toHaveLength(1);
            expect(inertiaMock.http.requests[0].params).toEqual({
                limit: '20',
                include: ['4'],
            });

            const status = screen.getByText('Searching…');

            expect(status).toBeInTheDocument();
            // A polite live region, so a screen reader hears it without losing focus.
            expect(status.closest('[aria-live]')).toHaveAttribute(
                'aria-live',
                'polite',
            );
            expect(screen.queryByText('No material found.')).toBeNull();
        });

        it('replaces "Searching…" with the options when the answer arrives', async () => {
            setup();

            await answer(0, [rivet, denim]);

            expect(optionNames()).toEqual([
                expect.stringContaining('Brass rivet 9mm'),
                expect.stringContaining('Denim 12oz Indigo'),
            ]);
            expect(screen.queryByText('Searching…')).toBeNull();
        });

        it('keeps the list on screen, refined by what is typed, while the next answer loads', async () => {
            const { user } = setup();

            await answer(0, [rivet, denim, button]);
            await user.type(
                screen.getByRole('combobox', { name: 'Search materials' }),
                'but',
            );

            expect(optionNames()).toEqual([
                expect.stringContaining('Metal shank button 17mm'),
            ]);
            expect(screen.getByText('Searching…')).toBeInTheDocument();
        });
    });

    describe('results', () => {
        it('sends what is typed, after the pause, and shows the answer', async () => {
            const { user } = setup({ usedIds: [4] });

            await answer(0, [rivet, denim]);
            await user.type(
                screen.getByRole('combobox', { name: 'Search materials' }),
                'thr',
            );
            await waitFor(() =>
                expect(inertiaMock.http.requests).toHaveLength(2),
            );

            expect(inertiaMock.http.requests[1].params).toEqual({
                search: 'thr',
                limit: '20',
                include: ['4'],
            });

            // The server's answer: the match, then the picked material that does not match.
            await answer(1, [thread, denim]);

            // The picked material does not match "thr", so it is not offered.
            expect(optionNames()).toEqual([
                expect.stringContaining('Polyester thread'),
            ]);
            expect(screen.queryByText('Searching…')).toBeNull();
        });

        it('finds a material by a part of its SKU', async () => {
            const { user } = setup();

            await answer(0, [rivet, zipper]);
            await user.type(
                screen.getByRole('combobox', { name: 'Search materials' }),
                'zip-018',
            );
            await waitFor(() =>
                expect(inertiaMock.http.requests).toHaveLength(2),
            );
            await answer(1, [zipper]);

            expect(optionNames()).toEqual([
                expect.stringContaining('Metal zipper 18cm'),
            ]);
        });

        it('keeps the first page order and shows each option with stock and unit cost', async () => {
            setup();

            await answer(0, [thread, rivet]);

            const [first, second] = screen.getAllByRole('option');

            expect(first).toHaveTextContent('Polyester thread');
            expect(first).toHaveTextContent('20,000 m in stock · €0.0040 / m');
            expect(second).toHaveTextContent('Brass rivet 9mm');
            expect(second).toHaveTextContent('5,000 pc in stock · €0.08 / pc');
        });

        it('disables the materials used on other rows, but not the own row one', async () => {
            setup({ value: 4, usedIds: [5, 8], include: [4, 5, 8] });

            await answer(0, [rivet, denim, button, thread]);

            expect(
                screen.getByRole('option', { name: /Metal shank button/ }),
            ).toHaveAttribute('aria-disabled', 'true');
            expect(
                screen.getByRole('option', { name: /Polyester thread/ }),
            ).toHaveAttribute('aria-disabled', 'true');
            expect(
                screen.getByRole('option', { name: /Polyester thread/ }),
            ).toHaveTextContent('Already in this composition');
            expect(
                screen.getByRole('option', { name: /Denim 12oz Indigo/ }),
            ).not.toHaveAttribute('aria-disabled', 'true');
            expect(
                screen.getByRole('option', { name: /Brass rivet/ }),
            ).not.toHaveAttribute('aria-disabled', 'true');
        });

        it('picks an option with the mouse, but not a used one', async () => {
            const { user, onSelect } = setup({ usedIds: [8] });

            await answer(0, [rivet, thread]);
            await user.click(
                screen.getByRole('option', { name: /Polyester thread/ }),
            );

            expect(onSelect).not.toHaveBeenCalled();

            await user.click(
                screen.getByRole('option', { name: /Brass rivet/ }),
            );

            expect(onSelect).toHaveBeenLastCalledWith(rivet);

            expect(onSelect).toHaveBeenCalledTimes(1);
        });

        it('hands every option the server sent to the form', async () => {
            const { onLoaded } = setup();

            await answer(0, [rivet, thread]);

            expect(onLoaded).toHaveBeenCalledExactlyOnceWith([rivet, thread]);
        });
    });

    describe('nothing found', () => {
        it('says "No material found." when the server has none', async () => {
            setup();

            await answer(0, []);

            expect(screen.getByText('No material found.')).toBeInTheDocument();
            expect(screen.queryByRole('option')).toBeNull();
        });

        it('says it when the picked materials the server adds do not match the search', async () => {
            const { user } = setup({ usedIds: [4] });

            await answer(0, [denim]);
            await user.type(
                screen.getByRole('combobox', { name: 'Search materials' }),
                'zzzz',
            );
            await waitFor(() =>
                expect(inertiaMock.http.requests).toHaveLength(2),
            );
            await answer(1, [denim]);

            expect(screen.getByText('No material found.')).toBeInTheDocument();
            expect(screen.queryByRole('option')).toBeNull();
        });

        it('does not say it while the answer is still on its way', async () => {
            const { user } = setup();

            await answer(0, [rivet]);
            await user.type(
                screen.getByRole('combobox', { name: 'Search materials' }),
                'zzzz',
            );

            expect(screen.getByText('Searching…')).toBeInTheDocument();
            expect(screen.queryByText('No material found.')).toBeNull();
        });
    });

    describe('errors', () => {
        it('shows an alert with a "Try again" button when the request fails', async () => {
            setup();

            await act(async () => {
                inertiaMock.http.requests[0].reject();
            });

            const alert = screen.getByRole('alert');

            expect(alert).toHaveTextContent('Materials could not be loaded.');
            expect(
                within(alert).getByRole('button', { name: 'Try again' }),
            ).toBeInTheDocument();
            expect(screen.queryByText('Searching…')).toBeNull();
            expect(screen.queryByText('No material found.')).toBeNull();
        });

        it('asks again on "Try again", puts focus back in the search box and recovers', async () => {
            const { user } = setup();

            await act(async () => {
                inertiaMock.http.requests[0].reject();
            });
            await user.click(screen.getByRole('button', { name: 'Try again' }));

            expect(inertiaMock.http.requests).toHaveLength(2);
            expect(
                screen.getByRole('combobox', { name: 'Search materials' }),
            ).toHaveFocus();
            expect(screen.queryByRole('alert')).toBeNull();
            expect(screen.getByText('Searching…')).toBeInTheDocument();

            await answer(1, [rivet]);

            expect(optionNames()).toHaveLength(1);
            expect(screen.queryByRole('alert')).toBeNull();
        });

        it('can fail again after a retry', async () => {
            const { user } = setup();

            await act(async () => {
                inertiaMock.http.requests[0].reject();
            });
            await user.click(screen.getByRole('button', { name: 'Try again' }));
            await act(async () => {
                inertiaMock.http.requests[1].reject();
            });

            expect(screen.getByRole('alert')).toBeInTheDocument();
        });
    });

    describe('stale answers', () => {
        it('shows the answer of the last request, whatever order the answers arrive in', async () => {
            const { user } = setup();

            await answer(0, [rivet, denim]);
            await user.type(
                screen.getByRole('combobox', { name: 'Search materials' }),
                'den',
            );
            await waitFor(() =>
                expect(inertiaMock.http.requests).toHaveLength(2),
            );
            await user.type(
                screen.getByRole('combobox', { name: 'Search materials' }),
                'im',
            );
            await waitFor(() =>
                expect(inertiaMock.http.requests).toHaveLength(3),
            );

            const [, early, latest] = inertiaMock.http.requests;

            await act(async () => {
                latest.resolve([denim]);
                // "den" answers late, with a different list.
                early.resolve([denim, button]);
            });

            expect(optionNames()).toEqual([
                expect.stringContaining('Denim 12oz Indigo'),
            ]);
        });
    });
});
