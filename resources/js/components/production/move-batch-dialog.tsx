import { useForm } from '@inertiajs/react';
import { useId } from 'react';
import type { FormEvent } from 'react';
import { variantName } from '@/components/production/batch-names';
import { FormField } from '@/components/materials/form-field';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import { stock } from '@/routes/progress/batches';
import { store as storeMove } from '@/routes/progress/batches/moves';
import type {
    MoveBatchFormData,
    ProgressBatch,
    ProgressStep,
    PutInStockFormData,
} from '@/types/domain';

export type BatchWindow = {
    batch: ProgressBatch;
    /** The phase the batch is in. */
    from: ProgressStep;
    /** Where it goes; null = Put in stock. */
    to: ProgressStep | null;
    /** Move back to … rather than Move to …. */
    back: boolean;
};

type MoveBatchDialogProps = {
    target: BatchWindow | null;
    onClose: () => void;
};

/** "B-0149 · Indigo M · in Sewing: 6 units". */
function batchLine(target: BatchWindow, locale: string): string {
    const { batch, from } = target;

    return `${batch.number} · ${variantName(batch)} · in ${from.name}: ${formatCount(batch.units, locale)} ${batch.units === 1 ? 'unit' : 'units'}`;
}

/** A whole number typed in the field, or null. */
function typedUnits(text: string): number | null {
    return /^\d+$/.test(text.trim()) ? Number(text.trim()) : null;
}

function MoveForm({
    target,
    onClose,
}: {
    target: BatchWindow;
    onClose: () => void;
}) {
    const uid = useId();
    const { locale } = useAppSettings();
    const { batch, from, to } = target;
    const stocking = to === null;
    const form = useForm<MoveBatchFormData | PutInStockFormData>(
        stocking
            ? { from_phase: from.phase, units: '' }
            : { from_phase: from.phase, to_phase: to.phase, units: '' },
    );
    const { data, errors, processing } = form;
    const moved = (errors as Record<string, string | undefined>).moved;

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        form.submit(stocking ? stock(batch.id) : storeMove(batch.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => onClose(),
        });
    }

    if (moved) {
        return (
            <div className="flex flex-col gap-4">
                <p role="alert" className="text-sm">
                    {moved}
                </p>
                <DialogFooter>
                    <Button type="button" onClick={onClose}>
                        Close
                    </Button>
                </DialogFooter>
            </div>
        );
    }

    // The shelf now plus the units typed, as the restock page adds its typed counts; whole units only.
    const adding = typedUnits(data.units) ?? batch.units;
    const variant = variantName(batch);

    return (
        <form noValidate onSubmit={submit} className="flex flex-col gap-4">
            <FormField
                id={`${uid}-units`}
                label="How many units?"
                hint={`Up to ${formatCount(batch.units, locale)}`}
                error={errors.units}
            >
                {(control) => (
                    <Input
                        {...control}
                        inputMode="numeric"
                        autoComplete="off"
                        value={data.units}
                        className="h-11 font-mono sm:h-9 sm:w-32"
                        onChange={(event) =>
                            form.setData('units', event.target.value)
                        }
                    />
                )}
            </FormField>

            {stocking && (
                <p className="text-sm text-muted-foreground">
                    Adds to your finished stock: {variant} goes from{' '}
                    {formatCount(batch.on_shelf, locale)} to{' '}
                    {formatCount(batch.on_shelf + adding, locale)}. Recorded as
                    a restock &quot;From production&quot;.
                </p>
            )}

            <DialogFooter>
                <Button
                    type="button"
                    variant="outline"
                    disabled={processing}
                    onClick={onClose}
                    className="max-sm:h-11"
                >
                    Cancel
                </Button>
                <Button
                    type="submit"
                    disabled={processing}
                    className="max-sm:h-11"
                >
                    {processing && <Spinner />}
                    {stocking ? 'Put in stock' : 'Move units'}
                </Button>
            </DialogFooter>
        </form>
    );
}

/**
 * Move to … / Move back to … and Put in stock (INV-5, AC-5 and AC-6): one field,
 * "How many units?". Moving part of a batch splits it (the server says so in its
 * toast); a batch that moved on meanwhile shows the server's sentence and Close.
 */
export function MoveBatchDialog({ target, onClose }: MoveBatchDialogProps) {
    const { locale } = useAppSettings();

    let title = '';

    if (target) {
        const verb =
            target.to === null
                ? 'Put in stock'
                : `${target.back ? 'Move back to' : 'Move to'} ${target.to.name}`;
        title = `${verb} · ${target.batch.product.name}`;
    }

    return (
        <Dialog
            open={target !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>
                        {target && batchLine(target, locale)}
                    </DialogDescription>
                </DialogHeader>
                {target && (
                    <MoveForm
                        key={`${target.batch.id}-${String(target.to?.phase)}`}
                        target={target}
                        onClose={onClose}
                    />
                )}
            </DialogContent>
        </Dialog>
    );
}
