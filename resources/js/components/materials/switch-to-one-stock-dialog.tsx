import { useEffect, useId, useState } from 'react';
import { CostChoiceList } from '@/components/materials/same-cost-dialog';
import type { CostChoice } from '@/components/materials/same-cost-dialog';
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
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { stockTotal } from '@/routes/materials';
import type { Decimal } from '@/types/domain';

/** What the form holds for one colour, sent to the server to be added up. */
export type ColourQuantity = { colourId: number; quantity: string };

type StockTotal = { total: Decimal; sentence: string };

function parseStockTotal(value: unknown): StockTotal | null {
    if (
        typeof value === 'object' &&
        value !== null &&
        'total' in value &&
        'sentence' in value &&
        typeof value.total === 'string' &&
        typeof value.sentence === 'string'
    ) {
        return { total: value.total, sentence: value.sentence };
    }

    return null;
}

type Result =
    | { key: string; failed: false; data: StockTotal }
    | { key: string; failed: true };

/**
 * Asks the server for the sum of the colours ("Indigo 250 m + Black 40 m become
 * one stock of 290 m."). The answer belongs to one request; a new request (Try
 * again) is "loading" until its own answer arrives.
 */
function useStockTotal(url: string) {
    const [attempt, setAttempt] = useState(0);
    const [result, setResult] = useState<Result | null>(null);
    const key = `${attempt}|${url}`;

    useEffect(() => {
        const controller = new AbortController();

        fetch(url, {
            headers: { Accept: 'application/json' },
            signal: controller.signal,
        })
            .then((response) =>
                response.ok
                    ? response.json()
                    : Promise.reject(new Error(String(response.status))),
            )
            .then((body: unknown) => {
                const data = parseStockTotal(body);

                setResult(
                    data ? { key, failed: false, data } : { key, failed: true },
                );
            })
            .catch(() => {
                if (!controller.signal.aborted) {
                    setResult({ key, failed: true });
                }
            });

        return () => controller.abort();
    }, [key, url]);

    const current = result?.key === key ? result : null;

    return {
        loading: current === null,
        failed: current?.failed === true,
        data: current && !current.failed ? current.data : null,
        retry: () => setAttempt((count) => count + 1),
    };
}

type SwitchToOneStockDialogProps = {
    unit?: string;
    colours: ColourQuantity[];
    /** The costs to choose from with cost per colour; null with the same cost. */
    choices: CostChoice[] | null;
    /** "Keep stock per colour" or Esc: the choice goes back. */
    onKeep: () => void;
    /** "Switch to one stock", with the server's total and the cost kept (null with the same cost). */
    onConfirm: (total: Decimal, cost: Decimal | null) => void;
};

/**
 * "Switch to one stock for all colours?" — opened by choosing One stock on a
 * material saved per colour. Mount it to open it. "Keep stock per colour" is
 * always on (it also takes the focus when the window opens, so keyboard users
 * land inside it); "Switch to one stock" stays off while the total is worked
 * out or if that failed, and until a cost is chosen, when one is asked.
 */
export function SwitchToOneStockDialog({
    unit,
    colours,
    choices,
    onKeep,
    onConfirm,
}: SwitchToOneStockDialogProps) {
    const [chosen, setChosen] = useState<number | null>(null);
    const costLabelId = useId();
    const hintId = useId();
    const url = stockTotal.url({
        query: {
            unit,
            colours: Object.fromEntries(
                colours.map(({ colourId, quantity }) => [
                    String(colourId),
                    quantity,
                ]),
            ),
        },
    });
    const total = useStockTotal(url);
    const picked = choices?.find((choice) => choice.id === chosen);
    const needsCost = choices !== null && !picked;
    const ready = total.data !== null;

    return (
        <AlertDialog
            open
            onOpenChange={(open) => {
                if (!open) {
                    onKeep();
                }
            }}
        >
            <AlertDialogContent aria-busy={total.loading}>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        Switch to one stock for all colours?
                    </AlertDialogTitle>
                    <AlertDialogDescription asChild>
                        <div aria-live="polite" aria-atomic="true">
                            {total.data && (
                                <p>
                                    <strong>{total.data.sentence}</strong> The
                                    history of each colour stays.
                                </p>
                            )}
                            {total.loading && (
                                <Skeleton
                                    aria-hidden="true"
                                    className="h-5 w-full"
                                />
                            )}
                            {total.failed && (
                                <p role="alert">
                                    Couldn&apos;t work out the total.
                                </p>
                            )}
                        </div>
                    </AlertDialogDescription>
                </AlertDialogHeader>

                {total.failed && (
                    <Button
                        type="button"
                        variant="outline"
                        className="h-11 w-full md:h-9 md:w-auto md:self-start"
                        onClick={total.retry}
                    >
                        Try again
                    </Button>
                )}

                {choices && (
                    <div className="space-y-2">
                        <p id={costLabelId} className="text-sm font-medium">
                            Choose the cost to keep for every colour:
                        </p>
                        <CostChoiceList
                            choices={choices}
                            unit={unit}
                            chosen={chosen}
                            onChoose={setChosen}
                            labelledBy={costLabelId}
                        />
                        <p className="text-sm text-muted-foreground">
                            The stock value changes with the cost you keep.
                        </p>
                        {!picked && (
                            <p
                                id={hintId}
                                className="text-sm text-muted-foreground"
                            >
                                Choose the cost to keep.
                            </p>
                        )}
                    </div>
                )}

                <AlertDialogFooter>
                    <AlertDialogCancel className="h-11 md:h-9">
                        Keep stock per colour
                    </AlertDialogCancel>
                    <AlertDialogAction
                        disabled={!ready || needsCost}
                        aria-describedby={needsCost ? hintId : undefined}
                        className="h-11 md:h-9"
                        onClick={(event) => {
                            // The form closes it (the dialog is mounted by its state); Action alone would report a close as "Keep".
                            event.preventDefault();

                            if (total.data && !needsCost) {
                                onConfirm(
                                    total.data.total,
                                    picked?.cost ?? null,
                                );
                            }
                        }}
                    >
                        Switch to one stock
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
