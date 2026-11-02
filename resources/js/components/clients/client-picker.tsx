import { ChevronsUpDown, Plus, Search } from 'lucide-react';
import { useRef, useState } from 'react';
import type { KeyboardEvent, ReactNode } from 'react';
import { sourceText } from '@/components/clients/source-label';
import { useClientOptions } from '@/components/clients/use-client-options';
import { Button } from '@/components/ui/button';
import {
    Command,
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
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useIsMobile } from '@/hooks/use-mobile';
import type { ClientRef } from '@/types/domain';

/** The grey line under a client's name: "Instagram · Sousse · 22 345 678" (no source: "Sousse · 22 345 678"). */
export function clientLine(client: ClientRef): string {
    return [sourceText(client), client.city, client.phone]
        .filter(Boolean)
        .join(' · ');
}

type ClientPickerProps = {
    id: string;
    /** Id of the visible label: the chosen client (or the placeholder) completes the button's name. */
    labelledBy: string;
    'aria-invalid'?: true;
    'aria-describedby'?: string;
    value: ClientRef | null;
    onSelect: (client: ClientRef | null) => void;
    /** Offers "+ Add "amira" as a new client" (or "+ New client") last; called with what was typed. */
    onAdd?: (typed: string) => void;
    /** A first option that clears the choice: "All clients" on the Orders list. */
    clearLabel?: string;
    placeholder?: string;
    /** New order's "Find a client by name" (INV-12): an outline button that opens the same search. */
    findButton?: boolean;
};

/**
 * A searchable client picker (INV-4, AC-5, AC-6, AC-7). The options come from
 * the server as the person types (name, phone digits or city), A → Z, each with
 * its grey line. On a laptop it is a popover; on a phone, a panel over the whole
 * screen titled "Choose a client", the search on top and the add option fixed
 * at the bottom.
 */
export function ClientPicker({
    id,
    labelledBy,
    'aria-invalid': invalid,
    'aria-describedby': describedBy,
    value,
    onSelect,
    onAdd,
    clearLabel,
    placeholder = 'Choose a client…',
    findButton = false,
}: ClientPickerProps) {
    const mobile = useIsMobile();
    const [open, setOpen] = useState(false);
    const valueId = `${id}-value`;

    function choose(client: ClientRef | null) {
        setOpen(false);
        onSelect(client);
    }

    function add(typed: string) {
        setOpen(false);
        onAdd?.(typed);
    }

    function handleKeyDown(event: KeyboardEvent<HTMLButtonElement>) {
        if (event.key === 'ArrowDown' && !open) {
            event.preventDefault();
            setOpen(true);
        }
    }

    const trigger = findButton ? (
        <Button
            id={id}
            type="button"
            variant="outline"
            aria-expanded={open}
            aria-haspopup="listbox"
            className="max-sm:h-11 max-sm:w-full"
            onClick={mobile ? () => setOpen(true) : undefined}
        >
            <Search aria-hidden="true" />
            Find a client by name
        </Button>
    ) : (
        <Button
            id={id}
            type="button"
            variant="outline"
            role="combobox"
            aria-expanded={open}
            aria-haspopup="listbox"
            aria-labelledby={`${labelledBy} ${valueId}`}
            aria-invalid={invalid}
            aria-describedby={describedBy}
            className="h-11 w-full justify-between font-normal sm:h-9"
            onKeyDown={handleKeyDown}
            // On a laptop the popover's trigger opens and closes it; on a phone the button opens the panel.
            onClick={mobile ? () => setOpen(true) : undefined}
        >
            <span
                id={valueId}
                className={
                    value
                        ? 'truncate font-medium'
                        : 'truncate text-muted-foreground'
                }
            >
                {value ? value.name : (clearLabel ?? placeholder)}
            </span>
            <ChevronsUpDown
                className="size-4 shrink-0 opacity-50"
                aria-hidden="true"
            />
        </Button>
    );

    const search = (
        <ClientSearch
            value={value}
            onSelect={choose}
            onAdd={onAdd ? add : undefined}
            clearLabel={clearLabel}
            mobile={mobile}
        />
    );

    if (mobile) {
        return (
            <>
                {trigger}
                <Sheet open={open} onOpenChange={setOpen}>
                    <SheetContent
                        side="bottom"
                        className="flex h-dvh flex-col gap-0 p-0"
                    >
                        <SheetHeader className="border-b">
                            <SheetTitle>Choose a client</SheetTitle>
                            <SheetDescription className="sr-only">
                                Search by name, phone or city.
                            </SheetDescription>
                        </SheetHeader>
                        {open && search}
                    </SheetContent>
                </Sheet>
            </>
        );
    }

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>{trigger}</PopoverTrigger>
            <PopoverContent
                align="start"
                className="w-(--radix-popover-trigger-width) max-w-[calc(100vw-2rem)] min-w-72 p-0"
            >
                {search}
            </PopoverContent>
        </Popover>
    );
}

type ClientSearchProps = {
    value: ClientRef | null;
    onSelect: (client: ClientRef | null) => void;
    onAdd?: (typed: string) => void;
    clearLabel?: string;
    mobile: boolean;
};

/** The open picker: the search box and the server's answer, mounted only while open. */
function ClientSearch({
    value,
    onSelect,
    onAdd,
    clearLabel,
    mobile,
}: ClientSearchProps) {
    const [search, setSearch] = useState('');
    const inputRef = useRef<HTMLInputElement>(null);
    const { options, loading, error, retry } = useClientOptions(
        search,
        value?.id ?? null,
    );
    const typed = search.trim();
    const empty = !loading && !error && options.length === 0;

    let status: ReactNode = null;

    if (loading) {
        status = <p className="py-2">Searching…</p>;
    } else if (empty) {
        status = (
            <p className="py-4">
                {typed === ''
                    ? 'No clients yet.'
                    : `No client matches "${typed}".`}
            </p>
        );
    }

    const addOption = onAdd && (
        <CommandItem
            value="__add__"
            onSelect={() => onAdd(typed)}
            className="gap-2 py-2.5 font-medium"
        >
            <Plus className="size-4" aria-hidden="true" />
            {typed === '' ? 'New client' : `Add "${typed}" as a new client`}
        </CommandItem>
    );

    return (
        <Command
            label="Search clients"
            shouldFilter={false}
            className="flex min-h-0 flex-1 flex-col"
        >
            <CommandInput
                ref={inputRef}
                placeholder="Search clients"
                value={search}
                onValueChange={setSearch}
            />
            <CommandList className={mobile ? 'max-h-none flex-1' : undefined}>
                <CommandGroup>
                    {clearLabel && (
                        <CommandItem
                            value="__all__"
                            onSelect={() => onSelect(null)}
                        >
                            {clearLabel}
                        </CommandItem>
                    )}
                    {options.map((client) => (
                        <CommandItem
                            key={client.id}
                            value={String(client.id)}
                            onSelect={() => onSelect(client)}
                            className="flex-col items-start gap-0.5 py-2"
                        >
                            <span className="font-medium">{client.name}</span>
                            <span className="text-xs text-muted-foreground">
                                {clientLine(client)}
                            </span>
                        </CommandItem>
                    ))}
                    {!mobile && addOption}
                </CommandGroup>
            </CommandList>
            <div
                aria-live="polite"
                className="px-2 text-center text-sm text-muted-foreground empty:hidden"
            >
                {status}
            </div>
            {error && (
                <div
                    role="alert"
                    className="flex flex-col items-center gap-2 px-3 py-4 text-center text-sm"
                >
                    <p>Couldn&apos;t load the clients. Try again.</p>
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
            {mobile && addOption && (
                <CommandList className="shrink-0 border-t">
                    <CommandGroup>{addOption}</CommandGroup>
                </CommandList>
            )}
        </Command>
    );
}
