import { router, useHttp } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { variantName } from '@/components/production/batch-names';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Spinner } from '@/components/ui/spinner';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount, formatQuantity } from '@/lib/format';
import { undo, undoPreview } from '@/routes/progress/batches';
import type { ProgressBatch, UndoBatchPreview } from '@/types/domain';

type UndoBatchDialogProps = {
    batch: ProgressBatch | null;
    onClose: () => void;
};

/**
 * "Takes the 4 units of Denim Jacket · Indigo M off Progress and gives back what was cut: 7.92 m Denim 12oz Indigo, 32 pc Metal
 * shank button 17mm, 504 m Polyester thread and 4 pc Woven brand label." The amounts come from the server, formatted here.
 */
export function undoText(
    batch: ProgressBatch,
    preview: UndoBatchPreview | null,
    locale: string,
): string {
    const takes = `Takes the ${formatCount(batch.units, locale)} ${batch.units === 1 ? 'unit' : 'units'} of ${batch.product.name} · ${variantName(batch)} off Progress`;

    if (preview === null) {
        return `${takes} and gives back what was cut.`;
    }

    const back = preview.rows
        .filter((row) => row.not_returned === null)
        .map(
            (row) =>
                `${formatQuantity(row.quantity, { locale, unit: row.material.unit })} ${row.material.name}`,
        );

    if (back.length === 0) {
        return `${takes}. No material was taken out of stock for it, so none is returned.`;
    }

    const list =
        back.length === 1
            ? back[0]
            : `${back.slice(0, -1).join(', ')} and ${back[back.length - 1]}`;

    return `${takes} and gives back what was cut: ${list}.`;
}

/** Undo batch B-0150? (INV-5, AC-6): only for a batch In cutting. */
export function UndoBatchDialog({ batch, onClose }: UndoBatchDialogProps) {
    const { locale } = useAppSettings();
    const { get, cancel } = useHttp<Record<string, never>, UndoBatchPreview>();
    const [preview, setPreview] = useState<{
        id: number;
        data: UndoBatchPreview;
    } | null>(null);
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const id = batch?.id ?? null;

    useEffect(() => {
        if (id === null) {
            return;
        }

        let current = true;

        get(undoPreview.url(id)).then(
            (data) => {
                if (current && data && Array.isArray(data.rows)) {
                    setPreview({ id, data });
                }
            },
            () => undefined,
        );

        return () => {
            current = false;
            cancel();
        };
    }, [get, cancel, id]);

    function confirm() {
        if (!batch) {
            return;
        }

        setProcessing(true);
        setError(null);
        router.post(
            undo.url(batch.id),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => onClose(),
                onError: (errors) =>
                    setError(
                        errors.undo ?? "Couldn't undo the batch. Try again.",
                    ),
                onFinish: () => setProcessing(false),
            },
        );
    }

    const loaded = preview !== null && preview.id === id ? preview.data : null;

    return (
        <AlertDialog
            open={batch !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        Undo batch {batch?.number}?
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        {batch && undoText(batch, loaded, locale)}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                {error && (
                    <p role="alert" className="text-sm text-destructive">
                        {error}
                    </p>
                )}
                <AlertDialogFooter>
                    <AlertDialogCancel disabled={processing}>
                        Keep batch
                    </AlertDialogCancel>
                    <AlertDialogAction
                        variant="destructive"
                        disabled={processing}
                        onClick={(event) => {
                            event.preventDefault();
                            confirm();
                        }}
                    >
                        {processing && <Spinner />}
                        Undo batch
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
