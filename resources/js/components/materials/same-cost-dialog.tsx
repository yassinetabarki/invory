import { useId, useState } from 'react';
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
import { Money } from '@/components/shared/money';
import type { Decimal } from '@/types/domain';

/** One saved cost a window offers to keep: the colour that has it and the cost. */
export type CostChoice = {
    /** Row key of the colour in the form. */
    id: number;
    colourName: string;
    cost: Decimal;
};

type CostChoiceListProps = {
    choices: CostChoice[];
    /** The material's unit code ("m"), shown after each cost. */
    unit?: string;
    chosen: number | null;
    onChoose: (id: number) => void;
    /** Id of the element that names the list. */
    labelledBy: string;
};

/** "Indigo · TND 8.500 / m" as radios, none chosen to start with. */
export function CostChoiceList({
    choices,
    unit,
    chosen,
    onChoose,
    labelledBy,
}: CostChoiceListProps) {
    const name = useId();

    return (
        <div
            role="radiogroup"
            aria-labelledby={labelledBy}
            className="max-h-60 space-y-2 overflow-y-auto"
        >
            {choices.map((choice) => (
                <label
                    key={choice.id}
                    className="flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border p-3 text-sm has-checked:border-primary has-checked:bg-accent has-focus-visible:ring-[3px] has-focus-visible:ring-ring/50"
                >
                    <input
                        type="radio"
                        name={name}
                        checked={chosen === choice.id}
                        onChange={() => onChoose(choice.id)}
                        className="size-4 shrink-0 accent-primary outline-none"
                    />
                    <span className="min-w-0 break-words">
                        {choice.colourName} ·{' '}
                        <Money value={choice.cost} precise />
                        {unit && ` / ${unit}`}
                    </span>
                </label>
            ))}
        </div>
    );
}

type SameCostDialogProps = {
    choices: CostChoice[];
    unit?: string;
    /** "Keep cost per colour" or Esc: the choice goes back. */
    onKeep: () => void;
    /** "Use this cost for all colours", with the cost chosen. */
    onConfirm: (cost: Decimal) => void;
};

/**
 * "Use the same cost for every colour?" — opened by choosing Same cost on a
 * material saved with cost per colour. Mount it to open it: it starts with no
 * cost chosen. The confirm button works only once one is.
 */
export function SameCostDialog({
    choices,
    unit,
    onKeep,
    onConfirm,
}: SameCostDialogProps) {
    const [chosen, setChosen] = useState<number | null>(null);
    const titleId = useId();
    const hintId = useId();
    const picked = choices.find((choice) => choice.id === chosen);

    return (
        <AlertDialog
            open
            onOpenChange={(open) => {
                if (!open) {
                    onKeep();
                }
            }}
        >
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        <span id={titleId}>
                            Use the same cost for every colour?
                        </span>
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        Choose the cost to keep. Every colour will use it, and
                        the stock value changes with it.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <CostChoiceList
                    choices={choices}
                    unit={unit}
                    chosen={chosen}
                    onChoose={setChosen}
                    labelledBy={titleId}
                />
                {!picked && (
                    <p id={hintId} className="text-sm text-muted-foreground">
                        Choose the cost to keep.
                    </p>
                )}
                <AlertDialogFooter>
                    <AlertDialogCancel className="h-11 md:h-9">
                        Keep cost per colour
                    </AlertDialogCancel>
                    <AlertDialogAction
                        disabled={!picked}
                        aria-describedby={picked ? undefined : hintId}
                        className="h-11 md:h-9"
                        onClick={(event) => {
                            // The form closes it (the dialog is mounted by its state); Action alone would report a close as "Keep".
                            event.preventDefault();

                            if (picked) {
                                onConfirm(picked.cost);
                            }
                        }}
                    >
                        Use this cost for all colours
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
