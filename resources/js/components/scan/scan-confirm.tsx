import { router } from '@inertiajs/react';
import { Check, TriangleAlert } from 'lucide-react';
import { Fragment } from 'react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { useAppSettings } from '@/hooks/use-app-settings';
import { useIsMobile } from '@/hooks/use-mobile';
import { formatCount, formatDate } from '@/lib/format';
import { store } from '@/routes/scan/batches';
import type { ScanBatchFormData, ScanConfirm } from '@/types/domain';

/** "Move to Finishing?" · "Put 4 units in stock?" */
export function confirmTitle(confirm: ScanConfirm, locale: string): string {
    return confirm.to === 'stock'
        ? `Put ${units(confirm.units, locale)} in stock?`
        : `Move to ${confirm.to_name}?`;
}

/** "Move 4 units to Finishing" · "Put 4 units in stock" */
export function confirmButton(confirm: ScanConfirm, locale: string): string {
    return confirm.to === 'stock'
        ? `Put ${units(confirm.units, locale)} in stock`
        : `Move ${units(confirm.units, locale)} to ${confirm.to_name}`;
}

function units(count: number, locale: string): string {
    return `${formatCount(count, locale)} ${count === 1 ? 'unit' : 'units'}`;
}

/** "In cutting ✓ · Sewing (now) · **Finishing** · Put in stock" */
function PathLine({ confirm }: { confirm: ScanConfirm }) {
    return (
        <p className="text-sm text-muted-foreground">
            {confirm.path.map((step, index) => (
                <Fragment key={step.name}>
                    {index > 0 && ' · '}
                    {step.state === 'next' ? (
                        <strong className="text-foreground">{step.name}</strong>
                    ) : (
                        step.name
                    )}
                    {step.state === 'done' && (
                        <>
                            {' '}
                            <Check
                                aria-label="done"
                                className="inline size-3.5"
                            />
                        </>
                    )}
                    {step.state === 'now' && ' (now)'}
                </Fragment>
            ))}
        </p>
    );
}

type ScanConfirmProps = {
    confirm: ScanConfirm | null;
    /** The server's refusal of this confirm ("B-0147 has moved since you scanned it: …"), shown in place of the move. */
    refusal: string | null;
    /** A notice that keeps the button: "The phases have changed since you scanned. B-0147 now goes to Quality check." */
    notice: string | null;
    processing: boolean;
    onConfirm: () => void;
    onClose: () => void;
};

/**
 * The scan confirm (INV-15, AC-4): one per scan, it never moves anything by itself and never times out. A dialog on a laptop, a sheet
 * from the bottom on a phone with its buttons one under another.
 */
export function ScanConfirmWindow({
    confirm,
    refusal,
    notice,
    processing,
    onConfirm,
    onClose,
}: ScanConfirmProps) {
    const { locale } = useAppSettings();
    const isMobile = useIsMobile();
    const open = confirm !== null;

    if (!confirm) {
        return null;
    }

    const title = confirmTitle(confirm, locale);
    const description = (
        <>
            {confirm.number} · <strong>{units(confirm.units, locale)}</strong> ·{' '}
            {confirm.product} · {confirm.variant} · started{' '}
            {formatDate(confirm.started_on, locale)}
        </>
    );
    const body: ReactNode = (
        <div className="flex flex-col gap-3">
            <PathLine confirm={confirm} />
            {confirm.shelf && (
                <p className="text-sm">
                    Adds to your finished stock:{' '}
                    {confirm.variant.replace(' · ', ' ')} goes from{' '}
                    {formatCount(confirm.shelf.now, locale)} to{' '}
                    {formatCount(confirm.shelf.after, locale)}.
                </p>
            )}
            {confirm.warning && !refusal && (
                <p
                    role="status"
                    className="flex items-start gap-2 rounded-md bg-amber-50 p-2 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-100"
                >
                    <TriangleAlert
                        aria-hidden="true"
                        className="mt-0.5 size-4 shrink-0"
                    />
                    {confirm.warning}
                </p>
            )}
            {(refusal ?? notice) && (
                <p role="alert" className="text-sm text-destructive">
                    {refusal ?? notice}
                </p>
            )}
        </div>
    );
    const buttons = refusal ? (
        <Button type="button" className="h-14 w-full" onClick={onClose}>
            Scan another
        </Button>
    ) : (
        <>
            <Button
                type="button"
                className="h-14 w-full text-base"
                disabled={processing}
                onClick={onConfirm}
            >
                {processing && <Spinner />}
                {confirmButton(confirm, locale)}
            </Button>
            <Button
                type="button"
                variant="outline"
                className="h-11 w-full"
                disabled={processing}
                onClick={onClose}
            >
                Not now
            </Button>
        </>
    );

    if (isMobile) {
        return (
            <Sheet open={open} onOpenChange={(next) => !next && onClose()}>
                <SheetContent side="bottom" className="gap-4 p-4">
                    <SheetHeader className="p-0">
                        <SheetTitle>{title}</SheetTitle>
                        <SheetDescription>{description}</SheetDescription>
                    </SheetHeader>
                    {body}
                    <SheetFooter className="flex-col gap-2 p-0">
                        {buttons}
                    </SheetFooter>
                </SheetContent>
            </Sheet>
        );
    }

    return (
        <Dialog open={open} onOpenChange={(next) => !next && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>
                {body}
                <DialogFooter className="flex-col gap-2 sm:flex-col">
                    {buttons}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

/** "Couldn't move B-0147. Check the connection and try again." */
export function couldntMove(confirm: ScanConfirm): string {
    return `Couldn't move ${confirm.number}. Check the connection and try again.`;
}

/** What the confirm's button posts: what the confirm showed, so the server refuses anything that changed since. */
export function scanBody(confirm: ScanConfirm): ScanBatchFormData {
    return { from: confirm.from, to: confirm.to, units: confirm.units };
}

/**
 * Posts the confirm: the whole batch moves on (or is put in stock), then the page is `/scan` again. `onError` gets the server's
 * sentence and which kind it is: `moved` (nothing can move: Scan another) or `phases` (look the batch up again: the button relabels).
 */
export function postScan(
    confirm: ScanConfirm,
    handlers: {
        onSuccess: () => void;
        onError: (kind: 'moved' | 'phases' | 'failed', message: string) => void;
        onFinish: () => void;
    },
): void {
    router.post(store.url(confirm.batch_id), scanBody(confirm), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: handlers.onSuccess,
        onError: (errors) => {
            if (errors.phases) {
                handlers.onError('phases', errors.phases);
            } else if (errors.moved ?? errors.units) {
                handlers.onError('moved', errors.moved ?? errors.units);
            } else {
                handlers.onError('failed', couldntMove(confirm));
            }
        },
        onNetworkError: () => {
            handlers.onError('failed', couldntMove(confirm));

            return false;
        },
        onHttpException: () => {
            handlers.onError('failed', couldntMove(confirm));

            return false;
        },
        onFinish: handlers.onFinish,
    });
}
