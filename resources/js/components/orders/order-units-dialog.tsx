import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useEffect, useId, useRef } from 'react';
import type { UnitsMode } from '@/components/orders/order-product-card';
import {
    OrderSizeFields,
    typedUnits,
} from '@/components/orders/order-size-fields';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import { cn } from '@/lib/utils';
import { store as storeCancellation } from '@/routes/orders/lines/cancellations';
import {
    destroy as moveBack,
    store as storeDelivery,
} from '@/routes/orders/lines/deliveries';
import type {
    OrderCellsFormData,
    TrackerLine,
    TrackerRow,
} from '@/types/domain';

/** The window's words per mode (INV-14, AC-3 and AC-4). */
const MODES: Record<
    UnitsMode,
    { title: string; line: string; note: string; submit: string; keep: string }
> = {
    deliver: {
        title: 'Mark delivered',
        line: 'to deliver and on the shelf',
        note: 'Delivered units leave your finished stock.',
        submit: 'Mark delivered',
        keep: 'Cancel',
    },
    move_back: {
        title: 'Move back to To deliver',
        line: 'delivered',
        note: 'These units go back on your shelf.',
        submit: 'Move back',
        keep: 'Cancel',
    },
    cancel: {
        title: 'Cancel units',
        line: 'to deliver',
        note: "Cancelled units stay on the order, marked Cancelled, and are not delivered. This can't be undone. Finished stock doesn't change.",
        submit: 'Cancel units',
        keep: 'Keep units',
    },
};

/** The sentences that mean the page is stale: the window offers Close only. */
const CHANGED = [
    'The shelf or this order changed since you opened this page.',
    'This order changed since you opened this page.',
];

/** The most a row can take in this mode: the server's figures, never computed here. */
function upTo(row: TrackerRow, mode: UnitsMode): number {
    return mode === 'deliver'
        ? row.can_deliver
        : mode === 'move_back'
          ? row.delivered
          : row.to_deliver;
}

type Field = {
    key: string;
    colourId: number;
    sizeId: number;
    name: string;
    upTo: number;
};

/** The rows with something to move, in the line's order, each named as the server names it ("Indigo S", "Camel"). */
function fieldsOf(line: TrackerLine, mode: UnitsMode): Field[] {
    return line.colours.flatMap(({ colour, rows }) =>
        rows
            .filter((row) => upTo(row, mode) > 0)
            .map((row) => ({
                key: `${colour.id}.${row.size.id}`,
                colourId: colour.id,
                sizeId: row.size.id,
                name: line.one_size
                    ? colour.name
                    : /^\d+$/.test(row.size.label)
                      ? `${colour.name}, size ${row.size.label}`
                      : `${colour.name} ${row.size.label}`,
                upTo: upTo(row, mode),
            })),
    );
}

/** "Indigo S 2 · L 3": each colour named once before its sizes. */
function summary(line: TrackerLine, mode: UnitsMode, locale: string): string {
    return line.colours
        .map(({ colour, rows }) => {
            const parts = rows
                .filter((row) => upTo(row, mode) > 0)
                .map((row) =>
                    line.one_size
                        ? formatCount(upTo(row, mode), locale)
                        : `${row.size.label} ${formatCount(upTo(row, mode), locale)}`,
                );

            return parts.length === 0
                ? null
                : `${colour.name} ${parts.join(' · ')}`;
        })
        .filter(Boolean)
        .join(' · ');
}

type UnitsFormProps = {
    orderId: number;
    client: string;
    line: TrackerLine;
    mode: UnitsMode;
    onClose: () => void;
};

function UnitsForm({ orderId, client, line, mode, onClose }: UnitsFormProps) {
    const uid = useId();
    const { locale } = useAppSettings();
    const fieldsRef = useRef<HTMLDivElement>(null);
    const words = MODES[mode];
    const fields = fieldsOf(line, mode);
    const form = useForm<OrderCellsFormData>({ cells: {} });
    const { errors, processing } = form;
    const allErrors = errors as Record<string, string | undefined>;
    const typedOf = (field: Field) =>
        form.data.cells[field.colourId]?.[field.sizeId];
    const typed = fields.reduce(
        (sum, field) => sum + typedUnits(typedOf(field)),
        0,
    );
    const shown = new Set(fields.map((field) => `cells.${field.key}`));
    const fieldless = Object.entries(allErrors).find(
        ([key, text]) => key.startsWith('cells.') && !shown.has(key) && text,
    )?.[1];
    const whole = allErrors.cells ?? fieldless;
    const stale = whole !== undefined && CHANGED.includes(whole);
    const refusalKey = Object.values(allErrors).filter(Boolean).join('|');

    // The focus starts in the first field, and goes back to the first refused one after a refusal.
    useEffect(() => {
        const inputs = fieldsRef.current;

        (
            inputs?.querySelector<HTMLInputElement>(
                'input[aria-invalid="true"]',
            ) ?? inputs?.querySelector<HTMLInputElement>('input')
        )?.focus();
    }, [refusalKey]);

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const route =
            mode === 'deliver'
                ? storeDelivery
                : mode === 'move_back'
                  ? moveBack
                  : storeCancellation;

        // Keep the window (and what was typed) through the reload that follows a refusal.
        form.submit(route({ order: orderId, line: line.id }), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: onClose,
        });
    }

    const submitLabel =
        mode === 'cancel' && typed > 0
            ? `Cancel ${formatCount(typed, locale)} ${typed === 1 ? 'unit' : 'units'}`
            : words.submit;

    return (
        <form
            noValidate
            className="flex min-h-0 flex-1 flex-col gap-4"
            onSubmit={handleSubmit}
        >
            <DialogHeader className="shrink-0 pr-6">
                <DialogTitle>
                    {words.title} · {line.product.name}
                </DialogTitle>
                <p className="text-sm text-muted-foreground">
                    {client} · {words.line}: {summary(line, mode, locale)}
                </p>
            </DialogHeader>

            <div className="-mx-1 flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto px-1">
                <div ref={fieldsRef} className="min-w-0">
                    <OrderSizeFields
                        idPrefix={`${uid}-cells`}
                        legend="How many units of each colour and size?"
                        showLegend
                        sizes={fields.map((field, index) => ({
                            id: index,
                            label: field.name,
                        }))}
                        values={Object.fromEntries(
                            fields.map((field, index) => [
                                index,
                                typedOf(field),
                            ]),
                        )}
                        onChange={(index, value) => {
                            const field = fields[index];

                            form.setData('cells', {
                                ...form.data.cells,
                                [field.colourId]: {
                                    ...form.data.cells[field.colourId],
                                    [field.sizeId]: value,
                                },
                            });
                        }}
                        errors={Object.fromEntries(
                            fields.map((field, index) => [
                                index,
                                allErrors[`cells.${field.key}`],
                            ]),
                        )}
                        hints={Object.fromEntries(
                            fields.map((field, index) => [
                                index,
                                `Up to ${formatCount(field.upTo, locale)}`,
                            ]),
                        )}
                        totalError={whole}
                        showTotal={false}
                    />
                </div>
                <p
                    className={cn(
                        'text-sm',
                        mode === 'cancel'
                            ? 'rounded-md bg-warning px-3 py-2 text-warning-foreground'
                            : 'text-muted-foreground',
                    )}
                >
                    {words.note}
                </p>
            </div>

            <DialogFooter className="shrink-0 max-sm:sticky max-sm:bottom-0 max-sm:-mx-4 max-sm:border-t max-sm:bg-background max-sm:p-3">
                {stale ? (
                    <Button type="button" onClick={onClose}>
                        Close
                    </Button>
                ) : (
                    <>
                        <Button
                            type="button"
                            variant="outline"
                            className="max-sm:h-11"
                            onClick={onClose}
                        >
                            {words.keep}
                        </Button>
                        <Button
                            type="submit"
                            variant={
                                mode === 'cancel' ? 'destructive' : 'default'
                            }
                            disabled={processing}
                            className="max-sm:h-11"
                        >
                            {processing && <Spinner />}
                            {submitLabel}
                        </Button>
                    </>
                )}
            </DialogFooter>
        </form>
    );
}

type OrderUnitsDialogProps = {
    orderId: number;
    client: string;
    /** The window is open while this is set; the line is read from the tracker, so its figures follow every reload. */
    target: { line: TrackerLine; mode: UnitsMode } | null;
    onClose: () => void;
};

/**
 * The three windows of the order page (INV-14, AC-3 and AC-4): Mark delivered, Move back to To deliver and Cancel units. A field per
 * colour and size that has something to move, "Up to" under it; the server checks every limit and its messages show under the fields.
 * Full screen on a phone, its buttons in a bar fixed at the bottom.
 */
export function OrderUnitsDialog({
    orderId,
    client,
    target,
    onClose,
}: OrderUnitsDialogProps) {
    return (
        <Dialog
            open={target !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent
                aria-describedby={undefined}
                className="flex max-h-[calc(100dvh-2rem)] flex-col px-4 max-sm:h-dvh max-sm:max-h-dvh max-sm:max-w-none max-sm:rounded-none sm:px-6"
            >
                {target && (
                    <UnitsForm
                        key={`${target.line.id}-${target.mode}`}
                        orderId={orderId}
                        client={client}
                        line={target.line}
                        mode={target.mode}
                        onClose={onClose}
                    />
                )}
            </DialogContent>
        </Dialog>
    );
}
