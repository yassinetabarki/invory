import { ChevronsUpDown, Check } from 'lucide-react';
import type { KeyboardEvent } from 'react';
import { Money } from '@/components/shared/money';
import { Quantity } from '@/components/shared/quantity';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
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
    materials: MaterialOption[];
    /** The picked material's id, or '' when none is picked yet. */
    value: number | '';
    /** Materials already used on another row: shown, but not selectable. */
    usedIds: ReadonlySet<number>;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onSelect: (material: MaterialOption) => void;
};

/**
 * Predictable search: every word typed must appear (in any order) in the
 * option's text, which is the material's name and SKU. `cmdk`'s own fuzzy
 * scoring would also match scattered letters ("zzzz" finds "Metal zipper").
 * Returns 1 (show) or 0 (hide); the list keeps its order.
 */
export function matchesSearch(optionText: string, search: string): number {
    const text = optionText.toLowerCase();
    const words = search.toLowerCase().split(/\s+/).filter(Boolean);

    return words.every((word) => text.includes(word)) ? 1 : 0;
}

/**
 * A searchable material picker (shadcn `Popover` + `Command`). Search matches
 * the name and the SKU. Each option shows the stock and the unit cost, so the
 * choice can be made without leaving the form. Arrow keys move, Enter picks,
 * Escape closes; ArrowDown on the closed button opens it.
 */
export function MaterialCombobox({
    id,
    labelId,
    'aria-invalid': invalid,
    'aria-describedby': describedBy,
    materials,
    value,
    usedIds,
    open,
    onOpenChange,
    onSelect,
}: MaterialComboboxProps) {
    const selected = materials.find((material) => material.id === value);
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
                {/* cmdk names its search box from the Command's `label`. */}
                <Command label="Search materials" filter={matchesSearch}>
                    <CommandInput placeholder="Search by name or SKU…" />
                    <CommandList>
                        <CommandEmpty>No material found.</CommandEmpty>
                        <CommandGroup>
                            {materials.map((material) => {
                                const used = usedIds.has(material.id);

                                return (
                                    <CommandItem
                                        key={material.id}
                                        value={`${material.name} ${material.sku}`}
                                        disabled={used}
                                        onSelect={() => onSelect(material)}
                                        className="flex-wrap items-start justify-between gap-x-3 gap-y-0.5 py-2"
                                    >
                                        <span className="flex min-w-0 flex-col">
                                            <span className="flex items-center gap-1.5 font-medium">
                                                {material.name}
                                                {material.id === value && (
                                                    <Check
                                                        className="size-3.5"
                                                        aria-hidden="true"
                                                    />
                                                )}
                                            </span>
                                            <span className="font-mono text-xs text-muted-foreground">
                                                {material.sku}
                                            </span>
                                        </span>
                                        <span className="text-xs text-muted-foreground tabular-nums">
                                            {used ? (
                                                'Already in this composition'
                                            ) : (
                                                <>
                                                    <Quantity
                                                        value={
                                                            material.quantity_on_hand
                                                        }
                                                        unit={material.unit}
                                                    />{' '}
                                                    in stock ·{' '}
                                                    <Money
                                                        value={
                                                            material.unit_cost
                                                        }
                                                        precise
                                                    />{' '}
                                                    / {material.unit}
                                                </>
                                            )}
                                        </span>
                                    </CommandItem>
                                );
                            })}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}
