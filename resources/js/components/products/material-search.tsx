import { Check } from 'lucide-react';
import { useRef, useState } from 'react';
import { useMaterialOptions } from '@/components/products/use-material-options';
import { Money } from '@/components/shared/money';
import { Quantity } from '@/components/shared/quantity';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import type { MaterialOption } from '@/types/domain';

/**
 * Predictable search: every word typed must appear (in any order) in the
 * option's text, which is the material's name and SKU. `cmdk`'s own fuzzy
 * scoring would also match scattered letters ("zzzz" finds "Metal zipper").
 * Returns 1 (show) or 0 (hide); the list keeps its order.
 *
 * The server does the real search; this only refines what it already sent
 * while the next answer is on its way, and hides the picked materials the
 * server adds to every answer when they do not match what is typed.
 */
export function matchesSearch(optionText: string, search: string): number {
    const text = optionText.toLowerCase();
    const words = search.toLowerCase().split(/\s+/).filter(Boolean);

    return words.every((word) => text.includes(word)) ? 1 : 0;
}

type MaterialSearchProps = {
    /** The picked material's id, or '' when none is picked yet. */
    value: number | '';
    /** Materials already used on another row: shown, but not selectable. */
    usedIds: ReadonlySet<number>;
    /** Every material id picked on the form: the server returns these too. */
    include: readonly number[];
    /** Every option the server sends, so the form can show picked materials. */
    onLoaded: (options: MaterialOption[]) => void;
    onSelect: (material: MaterialOption) => void;
};

/**
 * The open picker: a search box and the server's answer. It is mounted only
 * while the popover is open, so opening the picker loads the first page and
 * closing it drops the search. The list stays on screen while a new answer
 * loads; a status line says "Searching…", "No material found." or that the
 * request failed (with a retry).
 */
export function MaterialSearch({
    value,
    usedIds,
    include,
    onLoaded,
    onSelect,
}: MaterialSearchProps) {
    const [search, setSearch] = useState('');
    const inputRef = useRef<HTMLInputElement>(null);
    const { options, loading, error, retry } = useMaterialOptions({
        search,
        include,
        onLoaded,
    });

    // `Command` hides the options the word matcher rejects, so the empty
    // state counts what is left visible, not what the server sent.
    const anyShown = options.some(
        (option) => matchesSearch(`${option.name} ${option.sku}`, search) === 1,
    );
    const empty = !loading && !error && !anyShown;

    return (
        // cmdk names its search box from the Command's `label`.
        <Command label="Search materials" filter={matchesSearch}>
            <CommandInput
                ref={inputRef}
                placeholder="Search by name or SKU…"
                value={search}
                onValueChange={setSearch}
            />
            <CommandList>
                <CommandGroup>
                    {options.map((material) => {
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
                                                value={material.unit_cost}
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
            {/* Outside the listbox (a status is not an option); always mounted so screen readers announce changes. */}
            <div
                aria-live="polite"
                className="px-2 py-1 text-center text-sm text-muted-foreground empty:hidden"
            >
                {loading && <p className="py-2">Searching…</p>}
                {empty && <p className="py-5">No material found.</p>}
            </div>
            {error && (
                <div
                    role="alert"
                    className="flex flex-col items-center gap-2 px-3 py-4 text-center text-sm"
                >
                    <p>Materials could not be loaded.</p>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => {
                            retry();
                            inputRef.current?.focus();
                        }}
                    >
                        Try again
                    </Button>
                </div>
            )}
        </Command>
    );
}
