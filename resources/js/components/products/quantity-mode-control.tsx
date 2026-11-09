import { Check } from 'lucide-react';
import { useId, useState } from 'react';
import {
    countFilledCells,
    prefilledGrid,
    sameCells,
    summariseCells,
} from '@/components/products/bom-rows';
import type {
    BomRow,
    BomRowFields,
    QuantityGrid,
} from '@/components/products/bom-rows';
import { SizeQuantitiesDialog } from '@/components/products/size-quantities-dialog';
import type { SizeQuestion } from '@/components/products/size-quantities-dialog';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { cn } from '@/lib/utils';
import type { ComponentQuantityMode, Size } from '@/types/domain';

type QuantityModeControlProps = {
    row: BomRow;
    /** The material's name, or "new material". */
    label: string;
    /** The colours ticked on the form, in list order. */
    colours: readonly { id: number; name: string }[];
    /** The sizes pressed on the form, in list order. */
    sizes: readonly Size[];
    /** Id of the summary, so the Edit grid button can be described by it. */
    summaryId: string;
    /** Id of the error line, so the Edit grid button can point at it. */
    errorId: string;
    /** The sentence to show under the line (a cell's, or "Add at least one quantity …"). */
    error?: string;
    onChange: (patch: Partial<BomRowFields>) => void;
    /** Opens the row's grid (right after a switch to By colour and size). */
    onEditGrid: () => void;
};

const ITEM =
    'h-11 flex-1 gap-1.5 px-3 md:h-9 md:flex-none data-[state=on]:border-primary data-[state=on]:bg-primary/10 data-[state=on]:text-foreground data-[state=on]:inset-ring-1 data-[state=on]:inset-ring-primary aria-disabled:cursor-not-allowed aria-disabled:opacity-60';

const MODES: readonly { value: ComponentQuantityMode; text: string }[] = [
    { value: 'same', text: 'Same for every variant' },
    { value: 'by_variant', text: 'By colour and size' },
];

/**
 * Under a composition line: **Same for every variant** / **By colour and size**
 * (INV-16). Switching to the grid pre-fills every cell of the form's colours
 * and sizes with the quantity typed and opens it; switching straight back,
 * with no cell edited since, restores the quantity typed and asks nothing;
 * switching back otherwise asks first when the grid holds cells. A line with
 * no material can't be switched (the option stays reachable, with its reason).
 * The summary and the line's error sit beside it.
 */
export function QuantityModeControl({
    row,
    label,
    colours,
    sizes,
    summaryId,
    errorId,
    error,
    onChange,
    onEditGrid,
}: QuantityModeControlProps) {
    const reasonId = useId();
    const [question, setQuestion] = useState<SizeQuestion | null>(null);
    // What the last switch to the grid started from: the quantity typed and the cells it made.
    const [started, setStarted] = useState<{
        quantity: string;
        grid: QuantityGrid;
    } | null>(null);
    const filled = countFilledCells(row.quantities, colours, sizes);
    const blocked = row.material_id === '' && row.quantity_mode === 'same';

    function switchToSame(quantity = '') {
        setStarted(null);
        onChange({ quantity_mode: 'same', quantity, quantities: {} });
    }

    function choose(mode: ComponentQuantityMode) {
        if (mode === row.quantity_mode) {
            return;
        }

        if (mode === 'by_variant') {
            if (blocked) {
                return;
            }

            const grid = prefilledGrid(row.quantity, colours, sizes);

            setStarted({ quantity: row.quantity, grid });
            onChange({
                quantity_mode: 'by_variant',
                quantity: '',
                quantities: grid,
            });
            onEditGrid();
        } else if (started && sameCells(row.quantities, started.grid)) {
            switchToSame(started.quantity);
        } else if (filled > 0) {
            setQuestion({
                title: 'Switch to one quantity for every variant?',
                body: `This clears the ${filled} ${filled === 1 ? 'quantity' : 'quantities'} you set for ${label} across its colours and sizes.`,
                keep: 'Keep the grid',
                confirm: 'Switch anyway',
            });
        } else {
            switchToSame();
        }
    }

    return (
        <div className="col-span-full flex min-w-0 flex-col gap-2">
            <div className="flex min-w-0 flex-col gap-2 @md:flex-row @md:items-center">
                <ToggleGroup
                    type="single"
                    variant="outline"
                    aria-label={`How ${label} is counted`}
                    value={row.quantity_mode}
                    className="w-full @md:w-auto"
                    onValueChange={(value) => {
                        // Pressing the selected button again must not unselect it.
                        if (value !== '') {
                            choose(value as ComponentQuantityMode);
                        }
                    }}
                >
                    {MODES.map((mode) => {
                        const selected = row.quantity_mode === mode.value;
                        const disabled = blocked && mode.value === 'by_variant';

                        return (
                            <ToggleGroupItem
                                key={mode.value}
                                value={mode.value}
                                aria-disabled={disabled || undefined}
                                aria-describedby={
                                    disabled ? reasonId : undefined
                                }
                                className={ITEM}
                            >
                                {/* The check's room is always kept, so the button keeps its width. */}
                                <Check
                                    aria-hidden="true"
                                    className={cn(
                                        'size-4',
                                        !selected && 'invisible',
                                    )}
                                />
                                {mode.text}
                            </ToggleGroupItem>
                        );
                    })}
                </ToggleGroup>
                {row.quantity_mode === 'by_variant' && (
                    <p id={summaryId} className="text-sm text-muted-foreground">
                        {summariseCells(row.quantities, colours, sizes)}
                    </p>
                )}
            </div>
            {blocked && (
                <p id={reasonId} className="text-sm text-muted-foreground">
                    Choose a material first.
                </p>
            )}
            {error && (
                <p id={errorId} className="text-sm text-destructive-foreground">
                    {error}
                </p>
            )}

            <SizeQuantitiesDialog
                question={question}
                onKeep={() => setQuestion(null)}
                onConfirm={() => {
                    setQuestion(null);
                    switchToSame();
                }}
            />
        </div>
    );
}
