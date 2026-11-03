import { render, screen, waitFor } from '@testing-library/react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import {
    CAMERA_REFUSED,
    CameraReader,
    canUseCamera,
} from '@/components/scan/camera-reader';

// The QR reader: each detect() answers the next value of `reads` (none = nothing in view).
const reader = vi.hoisted(() => ({
    reads: [] as string[],
    located: '' as string,
}));
vi.mock('barcode-detector/ponyfill', () => ({
    BarcodeDetector: class {
        detect() {
            const value = reader.reads.shift();

            return Promise.resolve(value ? [{ rawValue: value }] : []);
        }
    },
    prepareZXingModule: ({
        overrides,
    }: {
        overrides: { locateFile: (path: string, prefix: string) => string };
    }) => {
        reader.located = overrides.locateFile(
            'zxing_reader.wasm',
            'https://cdn.example/',
        );
    },
}));

const stop = vi.fn();

function cameraAnswers(answer: () => Promise<MediaStream>) {
    Object.defineProperty(navigator, 'mediaDevices', {
        configurable: true,
        value: { getUserMedia: vi.fn(answer) },
    });
}

describe('CameraReader (INV-15, AC-3, AC-6)', () => {
    beforeEach(() => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        reader.reads = [];
        stop.mockClear();
        vi.spyOn(HTMLMediaElement.prototype, 'play').mockResolvedValue(
            undefined,
        );
        cameraAnswers(() =>
            Promise.resolve({
                getTracks: () => [{ stop }],
            } as unknown as MediaStream),
        );
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.restoreAllMocks();
    });

    it('knows whether the browser can open a camera', () => {
        expect(canUseCamera()).toBe(true);
    });

    it('reads a QR code once, even while the same ticket stays in view', async () => {
        const onRead = vi.fn();
        reader.reads = [
            'https://invory.test/scan/t/abc',
            'https://invory.test/scan/t/abc',
            'https://invory.test/scan/t/abc',
        ];
        render(
            <CameraReader
                paused={false}
                onRead={onRead}
                onUnavailable={vi.fn()}
            />,
        );

        expect(
            screen.getByText(
                'Point the camera at the QR code on a work ticket.',
            ),
        ).toBeInTheDocument();

        await waitFor(() => expect(onRead).toHaveBeenCalled());
        await vi.advanceTimersByTimeAsync(1000);

        expect(onRead).toHaveBeenCalledTimes(1);
        expect(onRead).toHaveBeenCalledWith('https://invory.test/scan/t/abc');
    });

    it('serves its WebAssembly reader from Invory, never from a CDN', async () => {
        render(
            <CameraReader
                paused={false}
                onRead={vi.fn()}
                onUnavailable={vi.fn()}
            />,
        );

        // Vite's own address of the bundled file; the CDN prefix the reader would use otherwise is never taken.
        await waitFor(() =>
            expect(reader.located).toMatch(/zxing_reader\.wasm$/),
        );
        expect(reader.located).not.toContain('cdn.example');
    });

    it('stops the camera while a confirm is open and when the page is left', async () => {
        const { rerender, unmount } = render(
            <CameraReader
                paused={false}
                onRead={vi.fn()}
                onUnavailable={vi.fn()}
            />,
        );
        await vi.advanceTimersByTimeAsync(10);

        rerender(
            <CameraReader paused onRead={vi.fn()} onUnavailable={vi.fn()} />,
        );
        expect(stop).toHaveBeenCalledTimes(1);

        rerender(
            <CameraReader
                paused={false}
                onRead={vi.fn()}
                onUnavailable={vi.fn()}
            />,
        );
        await vi.advanceTimersByTimeAsync(10);
        unmount();
        expect(stop).toHaveBeenCalledTimes(2);
    });

    it('says how to allow the camera when the browser refuses it', async () => {
        cameraAnswers(() =>
            Promise.reject(new DOMException('no', 'NotAllowedError')),
        );
        render(
            <CameraReader
                paused={false}
                onRead={vi.fn()}
                onUnavailable={vi.fn()}
            />,
        );

        expect(await screen.findByRole('alert')).toHaveTextContent(
            CAMERA_REFUSED,
        );
        expect(CAMERA_REFUSED).toBe(
            "Invory can't use the camera. Allow the camera for Invory in your browser's settings, or type the batch number.",
        );
    });

    it('leaves the field only when the device has no camera', async () => {
        const onUnavailable = vi.fn();
        cameraAnswers(() =>
            Promise.reject(new DOMException('none', 'NotFoundError')),
        );
        render(
            <CameraReader
                paused={false}
                onRead={vi.fn()}
                onUnavailable={onUnavailable}
            />,
        );

        await waitFor(() => expect(onUnavailable).toHaveBeenCalledTimes(1));
    });
});
