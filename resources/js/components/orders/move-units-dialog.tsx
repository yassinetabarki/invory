import { useForm } from '@inertiajs/react';
import { useEffect, useId, useRef } from 'react';
import type { FormEvent, RefObject } from 'react';
import { MaterialsMoveTable } from '@/components/orders/materials-move-table';
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

function MoveUnitsForm({ order, line, move, onClose }: MoveUnitsFormProps) {
    const uid = useId();
    const { locale } = useAppSettings();
    const inputRef = useRef<HTMLInputElement>(null);
    const form = useForm<MoveUnitsFormData>({ move, units: '' });
    const { errors, processing } = form;
    const refusal = errors.units ?? errors.move;
    // Start cutting and Move back to To start show the materials of the number typed.
    const needsTable = hasPreview(move);
    const preview = useMovePreview({
        order: order.id,
        line: line.id,
        move,
        units: form.data.units,
    });
    // The server's word on the number typed (preview) or on the move itself (refusal): the same place.
    const error = preview.status === 'invalid' ? preview.message : refusal;
    // The count saved now: it follows the page's reload after a refused move.
    const held = line[MOVES[move].from];

    // Back to the field once a move has been refused.
    useEffect(() => {
        if (!processing && refusal) {
            inputRef.current?.focus();
        }
    }, [refusal, processing]);

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
            className="flex flex-col gap-4"
            onSubmit={handleSubmit}
        >
            <DialogHeader>
                <DialogTitle>
                    {MOVES[move].title} · {line.product.name}
                </DialogTitle>
            </DialogHeader>

            <FormField
                id={`${uid}-units`}
                label="How many units?"
                hint={`Up to ${formatUnits(held, locale)}.`}
                error={error}
            >
                {(control) => (
                    <Input
                        {...control}
                        ref={inputRef}
                        type="text"
                        inputMode="numeric"
                        autoComplete="off"
                        value={form.data.units}
                        onChange={(event) =>
                            form.setData('units', event.target.value)
                        }
                    />
                )}
            </FormField>

            {needsTable && (
                // Always mounted, so a screen reader hears the table arrive.
                <div aria-live="polite" className="empty:hidden">
                    <MaterialsMoveTable
                        state={preview}
                        productName={line.product.name}
                    />
                </div>
            )}

            <DialogFooter>
                <Button type="button" variant="outline" onClick={onClose}>
                    Cancel
                </Button>
                <Button
                    type="submit"
                    disabled={
                        processing || (needsTable && preview.status !== 'ready')
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
 * stage. The server checks the limit against the count saved at that moment
 * and its message shows under the field; nothing is computed here.
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
                aria-describedby={undefined}
                onCloseAutoFocus={(event) => {
                    if (returnFocusTo?.current) {
                        event.preventDefault();
                        returnFocusTo.current.focus();
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
