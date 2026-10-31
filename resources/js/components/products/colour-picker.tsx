import { Check } from 'lucide-react';
import { useEffect, useId, useRef, useState } from 'react';
import type { KeyboardEvent, ReactNode } from 'react';
import { ColourSwatch } from '@/components/shared/colour-tag';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useIsMobile } from '@/hooks/use-mobile';
import { cn } from '@/lib/utils';
import type { Colour } from '@/types/domain';

type ColourPickerProps = {
    /** The workshop's whole list, in list order. */
    colours: Colour[];
    /** Ids of the chosen colours. */
    chosen: number[];
    onToggle: (id: number) => void;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** The button that opens the list ("Add colour"); closing puts the focus back on it. */
    trigger: ReactNode;
};

/**
 * Choose colours from the workshop's list: a list under the trigger on a
 * laptop, a full-screen sheet on a phone. Both hold the same multi-select
 * list: Enter or a click chooses or un-chooses, the list stays open, Done or
 * Esc closes it.
 */
export function ColourPicker({
    colours,
    chosen,
    onToggle,
    open,
    onOpenChange,
    trigger,
}: ColourPickerProps) {
    const mobile = useIsMobile();
    const sheetRef = useRef<HTMLDivElement>(null);
    const list = (
        <ColourList
            colours={colours}
            chosen={chosen}
            onToggle={onToggle}
            onDone={() => onOpenChange(false)}
        />
    );

    if (mobile) {
        return (
            <Sheet open={open} onOpenChange={onOpenChange}>
                <SheetTrigger asChild>{trigger}</SheetTrigger>
                {/* The sheet's own ✕ ("Close") is the only one: made a 44 px target here. */}
                <SheetContent
                    ref={sheetRef}
                    side="bottom"
                    tabIndex={-1}
                    aria-describedby={undefined}
                    // The focus starts on the sheet (announced "Choose colours"), so the keyboard opens only when the person taps the search.
                    onOpenAutoFocus={(event) => {
                        event.preventDefault();
                        sheetRef.current?.focus();
                    }}
                    className="top-0 h-dvh gap-0 border-t-0 outline-none [&>button:last-child]:top-2 [&>button:last-child]:right-2 [&>button:last-child]:flex [&>button:last-child]:size-11 [&>button:last-child]:items-center [&>button:last-child]:justify-center"
                >
                    <SheetHeader className="border-b py-3 pr-14">
                        <SheetTitle>Choose colours</SheetTitle>
                    </SheetHeader>
                    {list}
                </SheetContent>
            </Sheet>
        );
    }

    return (
        <Popover open={open} onOpenChange={onOpenChange}>
            <PopoverTrigger asChild>{trigger}</PopoverTrigger>
            <PopoverContent
                align="start"
                aria-label="Choose colours"
                className="w-90 max-w-[calc(100vw-2rem)] p-0"
            >
                {list}
            </PopoverContent>
        </Popover>
    );
}

type ColourListProps = Pick<
    ColourPickerProps,
    'colours' | 'chosen' | 'onToggle'
> & {
    onDone: () => void;
};

/**
 * A plain multi-select listbox (not cmdk, which marks the highlighted row as
 * selected): the search field keeps the focus and points at the active row with
 * `aria-activedescendant`. "N chosen" and Done sit under the scrolling list, so
 * they never cover a row.
 */
function ColourList({ colours, chosen, onToggle, onDone }: ColourListProps) {
    const listId = useId();
    const [query, setQuery] = useState('');
    const [activeId, setActiveId] = useState<number | null>(null);
    // No active row until the search has had the focus or a row was clicked
    // (on a phone the sheet opens without the keyboard).
    const [engaged, setEngaged] = useState(false);

    const needle = query.trim().toLowerCase();
    const matches = colours.filter(
        (colour) =>
            needle === '' ||
            colour.name.toLowerCase().includes(needle) ||
            colour.code.toLowerCase().includes(needle),
    );
    const active = engaged
        ? (matches.find((colour) => colour.id === activeId) ?? matches[0])
        : undefined;
    const optionId = (id: number) => `${listId}-${id}`;

    // Keep the active row in sight inside the list (the bar is outside it).
    useEffect(() => {
        if (active) {
            document
                .getElementById(`${listId}-${active.id}`)
                ?.scrollIntoView({ block: 'nearest' });
        }
    }, [active?.id, listId]);

    function move(step: 1 | -1) {
        if (!active) {
            return;
        }

        const next = matches[matches.indexOf(active) + step];

        if (next) {
            setActiveId(next.id);
        }
    }

    function handleKeyDown(event: KeyboardEvent<HTMLInputElement>) {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            move(1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            move(-1);
        } else if (event.key === 'Enter') {
            // Never submits the product form behind the list.
            event.preventDefault();

            if (active) {
                onToggle(active.id);
            }
        }
    }

    return (
        <div className="flex min-h-0 flex-1 flex-col">
            <div className="border-b p-2">
                <Input
                    role="combobox"
                    aria-label="Search colours"
                    aria-expanded="true"
                    aria-controls={matches.length > 0 ? listId : undefined}
                    aria-activedescendant={
                        active ? optionId(active.id) : undefined
                    }
                    aria-autocomplete="list"
                    autoComplete="off"
                    placeholder="Search colours"
                    value={query}
                    className="h-11 md:h-9"
                    onChange={(event) => {
                        setQuery(event.target.value);
                        setActiveId(null);
                    }}
                    onFocus={() => setEngaged(true)}
                    onKeyDown={handleKeyDown}
                />
            </div>

            {matches.length > 0 && (
                <ul
                    id={listId}
                    role="listbox"
                    aria-multiselectable="true"
                    aria-label="Colours"
                    className="min-h-0 flex-1 overflow-y-auto p-1 md:max-h-72 md:flex-none"
                >
                    {matches.map((colour) => {
                        const isChosen = chosen.includes(colour.id);

                        return (
                            <li
                                key={colour.id}
                                id={optionId(colour.id)}
                                role="option"
                                aria-selected={isChosen}
                                aria-label={`${colour.name}, ${colour.code}`}
                                className={cn(
                                    'flex min-h-11 cursor-pointer items-center gap-2 rounded-md px-2 text-sm hover:bg-accent md:min-h-9',
                                    active?.id === colour.id &&
                                        'bg-accent ring-1 ring-ring ring-inset',
                                )}
                                // Keep the focus in the search field.
                                onMouseDown={(event) => event.preventDefault()}
                                onClick={() => {
                                    setEngaged(true);
                                    setActiveId(colour.id);
                                    onToggle(colour.id);
                                }}
                            >
                                <span className="flex size-4 shrink-0 items-center justify-center">
                                    {isChosen && (
                                        <Check
                                            aria-hidden="true"
                                            className="size-4"
                                        />
                                    )}
                                </span>
                                <ColourSwatch swatch={colour.swatch} />
                                <span className="min-w-0 flex-1 break-words">
                                    {colour.name}
                                </span>
                                <span
                                    aria-hidden="true"
                                    className="shrink-0 font-mono text-xs text-muted-foreground"
                                >
                                    {colour.code}
                                </span>
                            </li>
                        );
                    })}
                </ul>
            )}

            {matches.length === 0 && (
                <div
                    role="status"
                    className="space-y-1 px-3 py-4 text-sm text-muted-foreground"
                >
                    <p>{`No colour named "${query.trim()}".`}</p>
                    <p>Missing one? Add it in Settings › Colours.</p>
                </div>
            )}

            <div className="mt-auto flex items-center justify-between gap-3 border-t p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
                <span aria-live="polite" className="text-sm font-medium">
                    {chosen.length} chosen
                </span>
                <Button type="button" className="h-11 md:h-9" onClick={onDone}>
                    Done
                </Button>
            </div>
        </div>
    );
}
