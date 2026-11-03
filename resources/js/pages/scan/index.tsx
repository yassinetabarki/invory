import { Head, useHttp } from '@inertiajs/react';
import { ScanQrCode } from 'lucide-react';
import { useRef, useState } from 'react';
import { CameraReader, canUseCamera } from '@/components/scan/camera-reader';
import { postScan, ScanConfirmWindow } from '@/components/scan/scan-confirm';
import { ScansToday } from '@/components/scan/scans-today';
import { PageHeader } from '@/components/shared/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useIsMobile } from '@/hooks/use-mobile';
import { index, lookup } from '@/routes/scan';
import type {
    ScanConfirm,
    ScanIndexProps,
    ScanLookupResult,
} from '@/types/domain';

/** What the last scan gave: its confirm, the reason nothing can move, or a lookup that failed (with **Try again**). */
type Found =
    | { kind: 'confirm'; confirm: ScanConfirm }
    | { kind: 'refusal'; text: string }
    | { kind: 'failed'; text: string; entry: string }
    | null;

function initial(props: ScanIndexProps): Found {
    if (props.confirm) {
        return { kind: 'confirm', confirm: props.confirm };
    }

    return props.refusal ? { kind: 'refusal', text: props.refusal } : null;
}

/**
 * Scan (INV-15, AC-3 to AC-6, ADR-0024): a phone's camera, a USB or Bluetooth scanner (which types like a keyboard, ending with
 * Enter) or a typed batch number finds the batch; one confirm moves it to its next phase or puts it in stock. Opened from a ticket's
 * QR link, the page starts with that ticket's confirm. After a move the field is empty and ready for the next ticket.
 */
export default function Scan(props: ScanIndexProps) {
    const { scans_today: scansToday } = props;
    const isMobile = useIsMobile();
    const { get } = useHttp<Record<string, never>, ScanLookupResult>();
    const [entry, setEntry] = useState('');
    const [found, setFound] = useState<Found>(() => initial(props));
    const [finding, setFinding] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [refusal, setRefusal] = useState<string | null>(null);
    const [notice, setNotice] = useState<string | null>(null);
    const [camera, setCamera] = useState(() => canUseCamera());
    const field = useRef<HTMLInputElement>(null);
    const confirm = found?.kind === 'confirm' ? found.confirm : null;

    function ready() {
        setEntry('');
        setRefusal(null);
        setNotice(null);
        window.setTimeout(() => field.current?.focus(), 0);
    }

    function find(raw: string, keepNotice = false) {
        const typed = raw.trim();

        if (typed === '') {
            field.current?.focus();

            return;
        }

        setFinding(true);
        setRefusal(null);

        if (!keepNotice) {
            setNotice(null);
        }

        get(lookup.url({ query: { q: typed } }))
            .then(
                (result) => {
                    setFound(
                        result.confirm
                            ? { kind: 'confirm', confirm: result.confirm }
                            : { kind: 'refusal', text: result.refusal ?? '' },
                    );
                    setEntry('');
                },
                () =>
                    setFound({
                        kind: 'failed',
                        text: `Couldn't load ticket ${typed}. Try again.`,
                        entry: typed,
                    }),
            )
            .finally(() => setFinding(false));
    }

    function move() {
        if (!confirm) {
            return;
        }

        setProcessing(true);
        setRefusal(null);
        postScan(confirm, {
            onSuccess: () => {
                setFound(null);
                ready();
            },
            onError: (kind, message) => {
                if (kind === 'phases') {
                    // The button relabels with the fresh next step; nothing has moved.
                    setNotice(message);
                    find(confirm.number, true);
                } else if (kind === 'moved') {
                    setRefusal(message);
                } else {
                    setNotice(message);
                }
            },
            onFinish: () => setProcessing(false),
        });
    }

    function close() {
        setFound(null);
        ready();
    }

    const showCamera = isMobile && camera;

    return (
        <>
            <Head title="Scan" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader title="Scan" />

                <div className="flex max-w-xl flex-col gap-4">
                    {showCamera && (
                        <CameraReader
                            paused={confirm !== null}
                            onRead={(value) => find(value)}
                            onUnavailable={() => setCamera(false)}
                        />
                    )}

                    <form
                        noValidate
                        className="flex flex-col gap-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            find(entry);
                        }}
                    >
                        <Label htmlFor="scan-entry">
                            {showCamera
                                ? 'Or type the batch number'
                                : 'Scan or type a batch number'}
                        </Label>
                        <div className="flex flex-col gap-2 sm:flex-row">
                            <div className="relative flex-1">
                                <ScanQrCode
                                    aria-hidden="true"
                                    className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                                />
                                <Input
                                    ref={field}
                                    id="scan-entry"
                                    autoFocus={!showCamera}
                                    autoComplete="off"
                                    autoCapitalize="characters"
                                    spellCheck={false}
                                    className="h-11 pl-9 font-mono sm:h-9"
                                    aria-describedby="scan-hint"
                                    value={entry}
                                    onChange={(event) =>
                                        setEntry(event.target.value)
                                    }
                                />
                            </div>
                            <Button
                                type="submit"
                                variant="outline"
                                className="max-sm:h-11 max-sm:w-full"
                                disabled={finding}
                            >
                                {finding && <Spinner />}
                                Find ticket
                            </Button>
                        </div>
                        <p
                            id="scan-hint"
                            className="text-sm text-muted-foreground"
                        >
                            A scanner works like typing. Each ticket opens its
                            move to confirm.
                        </p>
                    </form>

                    {found?.kind === 'refusal' && (
                        <div
                            role="alert"
                            className="flex flex-col items-start gap-2 rounded-lg border p-3 text-sm"
                        >
                            <p>{found.text}</p>
                            <Button
                                type="button"
                                variant="outline"
                                className="max-sm:h-11"
                                onClick={close}
                            >
                                Scan another
                            </Button>
                        </div>
                    )}

                    {found?.kind === 'failed' && (
                        <div
                            role="alert"
                            className="flex flex-col items-start gap-2 rounded-lg border border-destructive/40 p-3 text-sm text-destructive"
                        >
                            <p>{found.text}</p>
                            <Button
                                type="button"
                                variant="outline"
                                className="max-sm:h-11"
                                onClick={() => find(found.entry)}
                            >
                                Try again
                            </Button>
                        </div>
                    )}

                    <ScansToday scans={scansToday} />
                </div>
            </div>

            <ScanConfirmWindow
                confirm={confirm}
                refusal={refusal}
                notice={notice}
                processing={processing}
                onConfirm={move}
                onClose={close}
            />
        </>
    );
}

Scan.layout = {
    breadcrumbs: [{ title: 'Scan', href: index() }],
};
