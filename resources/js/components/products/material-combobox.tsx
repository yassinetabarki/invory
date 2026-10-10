import { ChevronsUpDown } from 'lucide-react';
import type { KeyboardEvent } from 'react';
import { MaterialSearch } from '@/components/products/material-search';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import type { MaterialOption } from '@/types/domain';

type MaterialComboboxProps = {
    id: string;
    /** The id of the visible label. The picked material (or the placeholder) completes the name. */
    labelId: string;
    'aria-invalid'?: true;
    'aria-describedby'?: string;
    /** The picked material, when the form knows it (it can be missing for a moment). */
    selected: MaterialOption | undefined;
    /** The picked material's id, or '' when none is picked yet. */
    value: number | '';
    /** Materials already used on another row: shown, but not selectable. */
    usedIds: ReadonlySet<number>;
    /** Every material id picked on the form: the server returns these too. */
    include: readonly number[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** Every option the server sends, so the form can show picked materials. */
    onLoaded: (options: MaterialOption[]) => void;
    onSelect: (material: MaterialOption) => void;
};

/**
 * A searchable material picker (shadcn `Popover` + `Command`). The options
 * come from the server as the person types (`MaterialSearch`); search matches
 * the name and the SKU. Each option shows the stock and the unit cost, so the
 * choice can be made without leaving the form. Arrow keys move, Enter picks,
 * Escape closes; ArrowDown on the closed button opens it.
 */
export function MaterialCombobox({
    id,
    labelId,
    'aria-invalid': invalid,
    'aria-describedby': describedBy,
    selected,
    value,
    usedIds,
    include,
    open,
    onOpenChange,
    onLoaded,
    onSelect,
}: MaterialComboboxProps) {
    // The name is the label plus the current value. The value has its own id
    // rather than the button referencing itself, which not every assistive
    // technology (or test engine) resolves.
    const valueId = `${id}-value`;

    function handleKeyDown(event: KeyboardEvent<HTMLButtonElement>) {
        if (event.key === 'ArrowDown' && !open) {
            event.preventDefault();
            onOpenChange(true);
        }
    }

    return (
        <Popover open={open} onOpenChange={onOpenChange}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    aria-haspopup="listbox"
                    aria-labelledby={`${labelId} ${valueId}`}
                    aria-invalid={invalid}
                    aria-describedby={describedBy}
                    data-action="material"
                    className="w-full justify-between font-normal"
                    onKeyDown={handleKeyDown}
                >
                    {selected ? (
                        <span
                            id={valueId}
                            className="flex min-w-0 items-baseline gap-2"
                        >
                            <span className="truncate font-medium">
                                {selected.name}
                            </span>
                            <span className="shrink-0 font-mono text-xs text-muted-foreground">
                                {selected.sku}
                            </span>
                        </span>
                    ) : (
                        <span
                            id={valueId}
                            className="truncate text-muted-foreground"
                        >
                            {value === ''
                                ? 'Select a material…'
                                : `Material #${value}`}
                        </span>
                    )}
                    <ChevronsUpDown
                        className="size-4 shrink-0 opacity-50"
                        aria-hidden="true"
                    />
                </Button>
            </PopoverTrigger>
            <PopoverContent
                align="start"
                className="w-(--radix-popover-trigger-width) max-w-[calc(100vw-2rem)] min-w-72 p-0"
            >
                <MaterialSearch
                    value={value}
                    usedIds={usedIds}
                    include={include}
                    onLoaded={onLoaded}
                    onSelect={onSelect}
                />
            </PopoverContent>
        </Popover>
    );
}
