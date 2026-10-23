import { useForm } from '@inertiajs/react';
import { useEffect, useId, useRef } from 'react';
import type { FormEvent, RefObject } from 'react';
import { MaterialsMoveTable } from '@/components/orders/materials-move-table';
import { OrderSizeFields } from '@/components/orders/order-size-fields';
import { formatUnits } from '@/components/orders/stage-figure';
import {
    hasPreview,
    useMovePreview,
} from '@/components/orders/use-move-preview';
import { FormField } from '@/components/products/form-field';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import { store } from '@/routes/orders/lines/moves';
import type {
    MoveUnitsFormData,
    Order,
    OrderLine,
    OrderLineMove,
    OrderStage,
} from '@/types/domain';

/** The stage that holds the units a move takes, and the words that name the move. */
export const MOVES: Record<OrderLineMove, { from: OrderStage; title: string }> =
    {
        start_cutting: { from: 'to_start', title: 'Start cutting' },
        mark_ready: { from: 'in_cutting', title: 'Mark ready' },
        ship: { from: 'ready_to_ship', title: 'Ship' },
        back_to_start: { from: 'in_cutting', title: 'Move back to To start' },
        back_to_cutting: {
            from: 'ready_to_ship',
            title: 'Move back to In cutting',
        },
        back_to_ready: { from: 'shipped', title: 'Move back to Ready to ship' },
    };

/** Id of a line block's heading: where focus lands when the button that opened the window is no longer usable. */
export function lineHeadingId(lineId: number): string {
    return `order-line-${lineId}-title`;
}

/** The move being made: the line is read from the order, so its counts follow every reload. */
export type MoveTarget = { lineId: number; move: OrderLineMove };

type MoveUnitsDialogProps = {
    order: Order;
    /** The dialog is open while this is set. */
    target: MoveTarget | null;
    onClose: () => void;
    /** The button that opened the dialog: gets focus back when it closes. */
    returnFocusTo?: RefObject<HTMLElement | null>;
};

type MoveUnitsFormProps = {
    order: Order;
    line: OrderLine;
    move: OrderLineMove;
    onClose: () => void;
};

/** The whole number typed in a size field; an empty or non-numeric field counts 0 (the server judges it on Move units). */
function typedUnits(value: string | undefined): number {
    const text = (value ?? '').trim();

    return /^\d+$/.test(text) ? Number(text) : 0;
}

const NOTHING_TYPED = { status: 'idle' } as const;

function MoveUnitsForm({ order, line, move, onClose }: MoveUnitsFormProps) {
    const uid = useId();
    const { locale } = useAppSettings();
    const fieldsRef = useRef<HTMLDivElement>(null);
    // A line counted by size asks a number per size; the others keep the single "How many units?" field.
    const bySize = line.size_mode === 'by_size';
    const from = MOVES[move].from;
    // Only the sizes whose stage holds units have a field.
    const sizeRows = bySize
        ? line.sizes.filter((entry) => entry[from] > 0)
        : [];
    const form = useForm<MoveUnitsFormData>({
        move,
        units: '',
        ...(bySize ? { sizes: {} } : {}),
    });
    const { errors, processing } = form;
    const typedSizes = form.data.sizes ?? {};
    // The total typed across the sizes: what the materials table is asked for (a 0 counts as empty).
    const typedTotal = sizeRows.reduce(
        (sum, entry) => sum + typedUnits(typedSizes[entry.size.id]),
        0,
    );
    const nothingTyped = bySize && typedTotal === 0;
    // A message for a size that has no field now (its stage holds 0 after a concurrent move) has no place
    // of its own: it shows under all the fields, like the messages about the move as a whole.
    const shown = new Set(sizeRows.map((entry) => `sizes.${entry.size.id}`));
    const fieldless = Object.entries(errors).find(
        ([key, text]) => key.startsWith('sizes.') && !shown.has(key) && text,
    )?.[1];
    // `sizes` is the move as a whole, `sizes.<size id>` one size; single-field lines keep `units`.
    const refusal = bySize
        ? (errors.sizes ?? errors.move ?? fieldless)
        : (errors.units ?? errors.move);
    const sizeErrors = Object.fromEntries(
        sizeRows.map((entry) => [
            entry.size.id,
            (errors as Record<string, string | undefined>)[
                `sizes.${entry.size.id}`
            ],
        ]),
    );
    // Start cutting and Move back to To start show the materials of the number typed.
    const needsTable = hasPreview(move);
    const asked = useMovePreview({
        order: order.id,
        line: line.id,
        move,
        units: bySize
            ? typedTotal > 0
                ? String(typedTotal)
                : ''
            : form.data.units,
    });
    // On a line counted by size the limit is per size, and the server answers it on Move units: the
    // total passing the stage is no message here and no reason to disable the button.
    const overStage = bySize && asked.status === 'invalid';
    // Nothing typed in any size is not a number to ask about: Move units gets the server's own word.
    const preview = nothingTyped || overStage ? NOTHING_TYPED : asked;
    // The server's word on the number typed (preview) or on the move itself (refusal): the same place.
    const error = preview.status === 'invalid' ? preview.message : refusal;
    // A refused cut repeats the table's short-stock sentence: show it once, in the table,
    // and let the field(s) point at it (aria-invalid, aria-describedby).
    const shortMessageId = `${uid}-short`;
    const refusedAsShown =
        preview.status === 'ready' &&
        preview.preview.short_message !== null &&
        preview.preview.short_message === refusal;
    // The count saved now: it follows the page's reload after a refused move.
    const held = line[from];
    // Every message of the last refusal: focus returns to the fields whenever a new one arrives.
    const refusalKey = [refusal, ...Object.values(sizeErrors)]
        .filter(Boolean)
        .join('|');

    // Back to the first refused field (or the first field) once a move has been refused.
    useEffect(() => {
        if (!processing && refusalKey) {
            const fields = fieldsRef.current;

            (
                fields?.querySelector<HTMLInputElement>(
                    'input[aria-invalid="true"]',
                ) ?? fields?.querySelector<HTMLInputElement>('input')
            )?.focus();
        }
    }, [refusalKey, processing]);

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        // Keep the window (and its state) through the reload that follows a refusal.
        form.submit(store({ order: order.id, line: line.id }), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: onClose,
        });
    }

    return (
        <form
            noValidate
            className="flex min-h-0 flex-col gap-4"
            onSubmit={handleSubmit}
        >
            <DialogHeader className="shrink-0">
                <DialogTitle>
                    {MOVES[move].title} · {line.product.name}
                </DialogTitle>
            </DialogHeader>

            {/* The middle scrolls; the title and the buttons stay on screen. */}
            <div className="-mx-1 flex min-h-0 flex-col gap-4 overflow-y-auto px-1">
                <div ref={fieldsRef} className="min-w-0">
                    {bySize ? (
                        <OrderSizeFields
                            idPrefix={`${uid}-sizes`}
                            legend="How many units of each size?"
                            showLegend
                            sizes={sizeRows.map((entry) => entry.size)}
                            values={typedSizes}
                            onChange={(sizeId, value) =>
                                form.setData('sizes', {
                                    ...typedSizes,
                                    [sizeId]: value,
                                })
                            }
                            errors={sizeErrors}
                            hints={Object.fromEntries(
                                sizeRows.map((entry) => [
                                    entry.size.id,
                                    `Up to ${formatCount(entry[from], locale)}`,
                                ]),
                            )}
                            totalError={refusedAsShown ? undefined : error}
                            messageId={
                                refusedAsShown ? shortMessageId : undefined
                            }
                            showTotal
                        />
                    ) : (
                        <FormField
                            id={`${uid}-units`}
                            label="How many units?"
                            hint={`Up to ${formatUnits(held, locale)}.`}
                            error={refusedAsShown ? undefined : error}
                        >
                            {(control) => (
                                <Input
                                    {...control}
                                    aria-invalid={
                                        refusedAsShown
                                            ? true
                                            : control['aria-invalid']
                                    }
                                    aria-describedby={
                                        refusedAsShown
                                            ? [
                                                  control['aria-describedby'],
                                                  shortMessageId,
                                              ]
                                                  .filter(Boolean)
                                                  .join(' ')
                                            : control['aria-describedby']
                                    }
                                    type="text"
                                    inputMode="numeric"
                                    autoComplete="off"
                                    value={form.data.units}
                                    onChange={(event) =>
                                        form.setData(
                                            'units',
                                            event.target.value,
                                        )
                                    }
                                />
                            )}
                        </FormField>
                    )}
                </div>

                {needsTable && (
                    // Always mounted, so a screen reader hears the table arrive.
                    <div aria-live="polite" className="empty:hidden">
                        <MaterialsMoveTable
                            state={preview}
                            productName={line.product.name}
                            shortMessageId={shortMessageId}
                        />
                    </div>
                )}
            </div>

            <DialogFooter className="shrink-0">
                <Button type="button" variant="outline" onClick={onClose}>
                    Cancel
                </Button>
                <Button
                    type="submit"
                    disabled={
                        processing ||
                        (needsTable &&
                            !nothingTyped &&
                            !overStage &&
                            preview.status !== 'ready')
                    }
                >
                    {processing && <Spinner />}
                    Move units
                </Button>
            </DialogFooter>
        </form>
    );
}

/**
 * The move window: how many units of a line go to the next (or the previous)
 * stage, or for a line counted by size how many of each size. The server checks
 * the limit against the count saved at that moment and its message shows under
 * the field (or the size); nothing is computed here but the total typed, which
 * the materials table is asked for.
 */
export function MoveUnitsDialog({
    order,
    target,
    onClose,
    returnFocusTo,
}: MoveUnitsDialogProps) {
    const line = target
        ? order.lines.find((candidate) => candidate.id === target.lineId)
        : undefined;
    // The line the window was last open for: the window has no target any more once it closes.
    const lastLineId = useRef<number | null>(null);

    useEffect(() => {
        if (target) {
            lastLineId.current = target.lineId;
        }
    }, [target]);

    return (
        <Dialog
            open={target !== null && line !== undefined}
            onOpenChange={(open) => {
                if (!open) {
                    onClose();
                }
            }}
        >
            <DialogContent
                // At most the screen's height: the form inside scrolls its middle.
                className="flex max-h-[calc(100dvh-2rem)] flex-col"
                aria-describedby={undefined}
                onCloseAutoFocus={(event) => {
                    const opener = returnFocusTo?.current;

                    // Back to the button that opened the window; when a move emptied its stage (or the
                    // product went away) the button is disabled, so focus goes to the line's heading.
                    if (opener?.isConnected && !opener.matches(':disabled')) {
                        event.preventDefault();
                        opener.focus();

                        return;
                    }

                    const heading =
                        lastLineId.current === null
                            ? null
                            : document.getElementById(
                                  lineHeadingId(lastLineId.current),
                              );

                    if (heading) {
                        event.preventDefault();
                        heading.focus();
                    }
                }}
            >
                {target && line && (
                    <MoveUnitsForm
                        key={`${line.id}-${target.move}`}
                        order={order}
                        line={line}
                        move={target.move}
                        onClose={onClose}
                    />
                )}
            </DialogContent>
        </Dialog>
    );
}
