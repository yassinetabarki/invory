import { act, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import Scan from '@/pages/scan/index';
import { confirm0147, confirm0148 } from '@/test/fixtures/tickets';
import {
    inertiaMock,
    resetInertiaMock,
    respondToHttp,
    setPageProps,
} from '@/test/inertia';
import type { ScanIndexProps, ScanLookupResult } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const device = vi.hoisted(() => ({ mobile: false }));
vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => device.mobile }));

// The camera has its own test; here a phone shows a stand-in that reads B-0147's link when asked.
const camera = vi.hoisted(() => ({
    available: true,
    read: null as ((value: string) => void) | null,
    paused: false,
}));
vi.mock('@/components/scan/camera-reader', () => ({
    canUseCamera: () => camera.available,
    CameraReader: ({
        onRead,
        paused,
    }: {
        onRead: (value: string) => void;
        paused: boolean;
    }) => {
        camera.read = onRead;
        camera.paused = paused;

        return <p>Point the camera at the QR code on a work ticket.</p>;
    },
}));

const props: ScanIndexProps = { scans_today: [], confirm: null, refusal: null };

function renderPage(overrides: Partial<ScanIndexProps> = {}) {
    const page = { ...props, ...overrides };
    setPageProps({ ...page }, { url: '/scan' });

    return { user: userEvent.setup(), ...render(<Scan {...page} />) };
}

function answer(result: ScanLookupResult) {
    respondToHttp(() => result);
}

type PostOptions = {
    onSuccess: () => void;
    onError: (errors: Record<string, string>) => void;
    onNetworkError: () => boolean;
    onFinish: () => void;
};

function lastPost(): [string, unknown, PostOptions] {
    return inertiaMock.router.post.mock.lastCall as [
        string,
        unknown,
        PostOptions,
    ];
}

describe('scan/index (INV-15, AC-3 to AC-6)', () => {
    beforeEach(() => {
        resetInertiaMock();
        device.mobile = false;
        camera.available = true;
        camera.read = null;
    });

    it('on a laptop: the field with the focus and its hint, no camera, and no scan yet today', () => {
        renderPage();

        const field = screen.getByLabelText('Scan or type a batch number');
        expect(field).toHaveFocus();
        expect(field).toHaveAccessibleDescription(
            'A scanner works like typing. Each ticket opens its move to confirm.',
        );
        expect(
            screen.getByRole('button', { name: 'Find ticket' }),
        ).toBeInTheDocument();
        expect(
            screen.queryByText(
                'Point the camera at the QR code on a work ticket.',
            ),
        ).toBeNull();
        expect(
            screen.getByRole('heading', { name: 'Your scans today' }),
        ).toBeInTheDocument();
        expect(screen.getByText('No scan yet today.')).toBeInTheDocument();
    });

    it('on a phone: the camera, then "Or type the batch number"; with no camera, the field only', () => {
        device.mobile = true;
        const { unmount } = renderPage();

        expect(
            screen.getByText(
                'Point the camera at the QR code on a work ticket.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByLabelText('Or type the batch number'),
        ).toBeInTheDocument();
        unmount();

        camera.available = false;
        renderPage();
        expect(
            screen.queryByText(
                'Point the camera at the QR code on a work ticket.',
            ),
        ).toBeNull();
        expect(
            screen.getByLabelText('Scan or type a batch number'),
        ).toBeInTheDocument();
    });

    it('finds what a scanner types (ending with Enter) and opens one confirm (AC-4)', async () => {
        answer({ confirm: confirm0147, refusal: null });
        const { user } = renderPage();

        await user.type(
            screen.getByLabelText('Scan or type a batch number'),
            'b147{Enter}',
        );

        expect(inertiaMock.http.requests[0].params).toEqual({ q: 'b147' });
        const window = await screen.findByRole('dialog', {
            name: 'Move to Finishing?',
        });
        const dialog = within(window);
        expect(window).toHaveAccessibleDescription(
            'B-0147 · 4 units · Denim Jacket · Indigo · S · started 23 Oct 2026',
        );
        const path = dialog.getByText(/Sewing \(now\)/);
        expect(path.textContent?.replace(/\s+/g, ' ')).toBe(
            'In cutting · Sewing (now) · Finishing · Put in stock',
        );
        expect(within(path).getByLabelText('done')).toBeInTheDocument();
        expect(within(path).getByText('Finishing').tagName).toBe('STRONG');
        expect(
            dialog.getByRole('button', { name: 'Move 4 units to Finishing' }),
        ).toBeInTheDocument();
        expect(
            dialog.getByRole('button', { name: 'Not now' }),
        ).toBeInTheDocument();
    });

    it('moves the whole batch, then the field is empty and ready for the next ticket (AC-4)', async () => {
        answer({ confirm: confirm0147, refusal: null });
        const { user } = renderPage();

        await user.type(
            screen.getByLabelText('Scan or type a batch number'),
            'B-0147',
        );
        await user.click(screen.getByRole('button', { name: 'Find ticket' }));
        await user.click(
            await screen.findByRole('button', {
                name: 'Move 4 units to Finishing',
            }),
        );

        const [url, body, options] = lastPost();
        expect(url).toBe('/scan/batches/147');
        expect(body).toEqual({ from: 11, to: 12, units: 4 });

        act(() => {
            options.onSuccess();
            options.onFinish();
        });

        await waitFor(() => expect(screen.queryByRole('dialog')).toBeNull());
        const field = screen.getByLabelText('Scan or type a batch number');
        expect(field).toHaveValue('');
        await waitFor(() => expect(field).toHaveFocus());
    });

    it('offers Put in stock at the last phase, with where the shelf goes (AC-4)', async () => {
        renderPage({ confirm: confirm0148 });

        const dialog = within(
            screen.getByRole('dialog', { name: 'Put 4 units in stock?' }),
        );
        expect(
            dialog.getByText(
                'Adds to your finished stock: Indigo M goes from 0 to 4.',
            ),
        ).toBeInTheDocument();
        expect(
            dialog.getByRole('button', { name: 'Put 4 units in stock' }),
        ).toBeInTheDocument();
    });

    it('opens with the confirm of the ticket whose QR link opened the page (AC-5)', () => {
        renderPage({ confirm: confirm0147 });

        expect(
            screen.getByRole('dialog', { name: 'Move to Finishing?' }),
        ).toBeInTheDocument();
    });

    it('moves nothing on Not now', async () => {
        const { user } = renderPage({ confirm: confirm0147 });

        await user.click(screen.getByRole('button', { name: 'Not now' }));

        expect(screen.queryByRole('dialog')).toBeNull();
        expect(inertiaMock.router.post).not.toHaveBeenCalled();
    });

    it('warns when the batch moved less than 2 minutes ago, and the button still works (AC-6)', () => {
        renderPage({
            confirm: {
                ...confirm0147,
                warning:
                    'B-0147 moved to Finishing 12 seconds ago (Admin). Scanning again moves it on to Put in stock.',
            },
        });

        const dialog = within(screen.getByRole('dialog'));
        expect(dialog.getByRole('status')).toHaveTextContent(
            'B-0147 moved to Finishing 12 seconds ago (Admin). Scanning again moves it on to Put in stock.',
        );
        expect(
            dialog.getByRole('button', { name: 'Move 4 units to Finishing' }),
        ).toBeEnabled();
    });

    it.each([
        'B-0139 was put in stock on 28 Oct 2026. Nothing left to do.',
        "This code isn't a work ticket of your workshop. Check the ticket, or type its number.",
        'No batch B-0999 in your workshop. Check the number on the ticket.',
        'A batch number looks like B-0142. Check what you typed.',
    ])('says "%s" with Scan another (AC-6)', async (refusal) => {
        answer({ confirm: null, refusal });
        const { user } = renderPage();

        await user.type(
            screen.getByLabelText('Scan or type a batch number'),
            'x{Enter}',
        );

        const alert = await screen.findByRole('alert');
        expect(alert).toHaveTextContent(refusal);
        await user.click(
            within(alert).getByRole('button', { name: 'Scan another' }),
        );
        expect(screen.queryByRole('alert')).toBeNull();
        expect(
            screen.getByLabelText('Scan or type a batch number'),
        ).toHaveFocus();
    });

    it('shows that the batch moved meanwhile, and only Scan another (AC-6)', async () => {
        const { user } = renderPage({ confirm: confirm0147 });

        await user.click(
            screen.getByRole('button', { name: 'Move 4 units to Finishing' }),
        );
        act(() => {
            lastPost()[2].onError({
                moved: 'B-0147 has moved since you scanned it: it is now in Finishing. Scan it again to move it on.',
            });
            lastPost()[2].onFinish();
        });

        const dialog = within(screen.getByRole('dialog'));
        expect(dialog.getByRole('alert')).toHaveTextContent(
            'B-0147 has moved since you scanned it: it is now in Finishing. Scan it again to move it on.',
        );
        expect(
            dialog.queryByRole('button', { name: 'Move 4 units to Finishing' }),
        ).toBeNull();
        expect(
            dialog.getByRole('button', { name: 'Scan another' }),
        ).toBeInTheDocument();
    });

    it('relabels the button when the phases changed, moving nothing (AC-6)', async () => {
        const { user } = renderPage({ confirm: confirm0148 });
        answer({
            confirm: {
                ...confirm0148,
                to: 13,
                to_name: 'Quality check',
                shelf: null,
            },
            refusal: null,
        });

        await user.click(
            screen.getByRole('button', { name: 'Put 4 units in stock' }),
        );
        act(() => {
            lastPost()[2].onError({
                phases: 'The phases have changed since you scanned. B-0148 now goes to Quality check.',
            });
            lastPost()[2].onFinish();
        });

        const dialog = within(
            await screen.findByRole('dialog', {
                name: 'Move to Quality check?',
            }),
        );
        expect(dialog.getByRole('alert')).toHaveTextContent(
            'The phases have changed since you scanned. B-0148 now goes to Quality check.',
        );
        expect(
            dialog.getByRole('button', {
                name: 'Move 4 units to Quality check',
            }),
        ).toBeInTheDocument();
        expect(inertiaMock.http.requests.at(-1)?.params).toEqual({
            q: 'B-0148',
        });
    });

    it('says when it could not move or could not load, with a way to try again (AC-6)', async () => {
        const { user, unmount } = renderPage({ confirm: confirm0147 });

        await user.click(
            screen.getByRole('button', { name: 'Move 4 units to Finishing' }),
        );
        act(() => {
            lastPost()[2].onNetworkError();
            lastPost()[2].onFinish();
        });
        expect(
            within(screen.getByRole('dialog')).getByRole('alert'),
        ).toHaveTextContent(
            "Couldn't move B-0147. Check the connection and try again.",
        );
        expect(
            screen.getByRole('button', { name: 'Move 4 units to Finishing' }),
        ).toBeEnabled();
        unmount();

        respondToHttp(() => {
            throw new Error('offline');
        });
        renderPage();
        await user.type(
            screen.getByLabelText('Scan or type a batch number'),
            'B-0147{Enter}',
        );
        const alert = await screen.findByRole('alert');
        expect(alert).toHaveTextContent(
            "Couldn't load ticket B-0147. Try again.",
        );

        answer({ confirm: confirm0147, refusal: null });
        await user.click(
            within(alert).getByRole('button', { name: 'Try again' }),
        );
        expect(
            await screen.findByRole('dialog', { name: 'Move to Finishing?' }),
        ).toBeInTheDocument();
    });

    it('finds the ticket the camera read, and stops the camera while the confirm is open', async () => {
        device.mobile = true;
        answer({ confirm: confirm0147, refusal: null });
        renderPage();

        expect(camera.paused).toBe(false);
        act(() => camera.read?.('https://invory.test/scan/t/abc'));

        expect(
            await screen.findByRole('dialog', { name: 'Move to Finishing?' }),
        ).toBeInTheDocument();
        expect(inertiaMock.http.requests[0].params).toEqual({
            q: 'https://invory.test/scan/t/abc',
        });
        expect(camera.paused).toBe(true);
    });

    it('shows the confirm as a sheet from the bottom on a phone, its buttons one under another', () => {
        device.mobile = true;
        renderPage({ confirm: confirm0147 });

        const sheet = screen.getByRole('dialog', {
            name: 'Move to Finishing?',
        });
        expect(
            within(sheet).getByRole('button', {
                name: 'Move 4 units to Finishing',
            }),
        ).toHaveClass('h-14', 'w-full');
        expect(
            within(sheet).getByRole('button', { name: 'Not now' }),
        ).toHaveClass('w-full');
    });

    it("lists this member's scans of today", () => {
        renderPage({
            scans_today: [
                {
                    number: 'B-0147',
                    product: 'Denim Jacket',
                    to: 'Finishing',
                    at: '2026-10-30T09:05:00+00:00',
                },
                {
                    number: 'B-0148',
                    product: 'Denim Jacket',
                    to: 'Put in stock',
                    at: '2026-10-30T09:00:00+00:00',
                },
            ],
        });

        const items = within(
            screen.getByRole('region', { name: 'Your scans today' }),
        ).getAllByRole('listitem');
        expect(items).toHaveLength(2);
        expect(items[0]).toHaveTextContent('B-0147 · Denim Jacket → Finishing');
        expect(items[1]).toHaveTextContent(
            'B-0148 · Denim Jacket → Put in stock',
        );
        expect(items[0].querySelector('time')).toHaveAttribute(
            'datetime',
            '2026-10-30T09:05:00+00:00',
        );
    });
});
