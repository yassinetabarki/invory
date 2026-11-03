import { useEffect, useRef, useState } from 'react';

/** What the page says when the browser refuses the camera (INV-15, AC-6). */
export const CAMERA_REFUSED =
    "Invory can't use the camera. Allow the camera for Invory in your browser's settings, or type the batch number.";

/** How often the camera's picture is read, in milliseconds. */
const READ_EVERY = 250;

/** A code read again within this many milliseconds is the same ticket still in view, not a new scan. */
const SAME_CODE_FOR = 4000;

type Detector = {
    detect: (source: HTMLVideoElement) => Promise<{ rawValue: string }[]>;
};

/**
 * The QR reader (ADR-0024 decision 8): `barcode-detector` uses the browser's own `BarcodeDetector` when it has one, and its
 * WebAssembly reader otherwise. The `.wasm` file is bundled by Vite and served by Invory, never by a CDN. Loaded on the Scan page only.
 */
async function loadDetector(): Promise<Detector> {
    const [{ BarcodeDetector, prepareZXingModule }, { default: wasmUrl }] =
        await Promise.all([
            import('barcode-detector/ponyfill'),
            import('zxing-wasm/reader/zxing_reader.wasm?url'),
        ]);

    prepareZXingModule({
        overrides: {
            locateFile: (path: string, prefix: string) =>
                path.endsWith('.wasm') ? wasmUrl : prefix + path,
        },
    });

    return new BarcodeDetector({ formats: ['qr_code'] });
}

/** Whether this browser can open a camera at all. */
export function canUseCamera(): boolean {
    return (
        typeof navigator !== 'undefined' &&
        typeof navigator.mediaDevices?.getUserMedia === 'function'
    );
}

type CameraReaderProps = {
    /** A QR code was read: the link (or text) it holds. */
    onRead: (value: string) => void;
    /** While a confirm is open the camera stops, so it never reads behind it. */
    paused: boolean;
    /** No camera on this device: the page shows the field only. */
    onUnavailable: () => void;
};

/**
 * The camera view on the Scan page (INV-15-f, AC-3): "Point the camera at the QR code on a work ticket." The camera runs only while
 * the page shows and no confirm is open; leaving the page stops it.
 */
export function CameraReader({
    onRead,
    paused,
    onUnavailable,
}: CameraReaderProps) {
    const video = useRef<HTMLVideoElement>(null);
    const [refused, setRefused] = useState(false);
    // The callbacks change on every render of the page; the loop reads the latest through a ref.
    const latest = useRef({ onRead, onUnavailable });
    const lastRead = useRef<{ value: string; at: number } | null>(null);

    useEffect(() => {
        latest.current = { onRead, onUnavailable };
    });

    useEffect(() => {
        if (paused) {
            return;
        }

        let stopped = false;
        let stream: MediaStream | null = null;
        let timer: number | undefined;

        async function start() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment' },
                    audio: false,
                });
            } catch (error) {
                if (stopped) {
                    return;
                }

                const name = error instanceof DOMException ? error.name : '';

                if (name === 'NotAllowedError' || name === 'SecurityError') {
                    setRefused(true);
                } else {
                    latest.current.onUnavailable();
                }

                return;
            }

            if (stopped || !video.current) {
                stream.getTracks().forEach((track) => track.stop());

                return;
            }

            video.current.srcObject = stream;
            await video.current.play().catch(() => undefined);

            const detector = await loadDetector();

            const read = async () => {
                if (stopped || !video.current) {
                    return;
                }

                try {
                    const [found] = await detector.detect(video.current);
                    const now = Date.now();
                    const last = lastRead.current;

                    if (found?.rawValue) {
                        const same =
                            last !== null &&
                            last.value === found.rawValue &&
                            now - last.at < SAME_CODE_FOR;

                        // The same ticket still in view keeps counting as seen, so it is read once, not every quarter second.
                        lastRead.current = { value: found.rawValue, at: now };

                        if (!same) {
                            latest.current.onRead(found.rawValue);
                        }
                    }
                } catch {
                    // A frame that can't be read yet (the video is still starting): try the next one.
                }

                timer = window.setTimeout(read, READ_EVERY);
            };

            void read();
        }

        void start();

        return () => {
            stopped = true;
            window.clearTimeout(timer);
            stream?.getTracks().forEach((track) => track.stop());
        };
    }, [paused]);

    if (refused) {
        return (
            <p
                role="alert"
                className="rounded-lg border border-destructive/40 p-3 text-sm text-destructive"
            >
                {CAMERA_REFUSED}
            </p>
        );
    }

    return (
        <figure className="flex flex-col gap-2">
            <div className="relative mx-auto aspect-square w-full max-w-sm overflow-hidden rounded-xl bg-black">
                <video
                    ref={video}
                    className="size-full object-cover"
                    muted
                    playsInline
                    aria-hidden="true"
                />
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-[15%] rounded-lg border-2 border-white/80"
                />
            </div>
            <figcaption className="text-center text-sm">
                Point the camera at the QR code on a work ticket.
            </figcaption>
        </figure>
    );
}
