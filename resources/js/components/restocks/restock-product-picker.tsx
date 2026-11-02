import { Check, ChevronsUpDown, Plus } from 'lucide-react';
import { useRef, useState } from 'react';
import type { KeyboardEvent, ReactNode } from 'react';
import { useProductOptions } from '@/components/restocks/use-product-options';
import { ProductStatusBadge } from '@/components/shared/product-status-badge';
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
import type { ProductStatus, RestockProductRef } from '@/types/domain';

/** The words of a product's status badge (the restock pages show it for a draft or archived product only). */
export const STATUS_LABEL: Record<ProductStatus, string> = {
    draft: 'Draft',
    active: 'Active',
    archived: 'Archived',
};

type Common = {
    /** Called with the chosen product, or null for the clear option. */
    onSelect: (product: RestockProductRef | null) => void;
};

type FilterMode = Common & {
    mode: 'filter';
    id: string;
    /** Id of the visible label: the chosen product (or "All products") completes the button's name. */
    labelledBy: string;
    value: RestockProductRef | null;
};

type AddMode = Common & {
    mode: 'add';
    /** The products already on the restock: shown with a check. */
    chosen: number[];
    disabled?: boolean;
};

/**
 * The product picker of the restock pages (INV-3). `filter`: the Restocks
 * list's Product filter, a combobox whose first option is "All products".
 * `add`: New restock's **Add product** button; a product already on the
 * restock shows a check. Options come from the server as the person types
 * (name or SKU), each with its SKU in grey and, for a draft or archived
 * product, its status badge. A popover on a laptop; a panel over the whole
 * screen on a phone.
 */
export function RestockProductPicker(props: FilterMode | AddMode) {
    const mobile = useIsMobile();
    const [open, setOpen] = useState(false);

    function choose(product: RestockProductRef | null) {
        setOpen(false);
        props.onSelect(product);
    }

    function handleKeyDown(event: KeyboardEvent<HTMLButtonElement>) {
        if (event.key === 'ArrowDown' && !open) {
            event.preventDefault();
            setOpen(true);
        }
    }

    const trigger =
        props.mode === 'filter' ? (
            <Button
                id={props.id}
                type="button"
                variant="outline"
                role="combobox"
                aria-expanded={open}
                aria-haspopup="listbox"
                aria-labelledby={`${props.labelledBy} ${props.id}-value`}
                className="h-11 w-full justify-between font-normal sm:h-9"
                onKeyDown={handleKeyDown}
                onClick={mobile ? () => setOpen(true) : undefined}
            >
                <span
                    id={`${props.id}-value`}
                    className={
                        props.value ? 'truncate font-medium' : 'truncate'
                    }
                >
                    {props.value ? props.value.name : 'All products'}
                </span>
                <ChevronsUpDown
                    className="size-4 shrink-0 opacity-50"
                    aria-hidden="true"
                />
            </Button>
        ) : (
            <Button
                type="button"
                variant="outline"
                aria-expanded={open}
                aria-haspopup="listbox"
                disabled={props.disabled}
                className="max-sm:h-11 max-sm:w-full"
                onClick={mobile ? () => setOpen(true) : undefined}
            >
                <Plus aria-hidden="true" />
                Add product
            </Button>
        );

    const search = (
        <ProductSearch
            selected={
                props.mode === 'filter'
                    ? props.value
                        ? [props.value.id]
                        : []
                    : props.chosen
            }
            include={props.mode === 'filter' ? (props.value?.id ?? null) : null}
            clearLabel={props.mode === 'filter' ? 'All products' : undefined}
            onSelect={choose}
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
                            <SheetTitle>Choose a product</SheetTitle>
                            <SheetDescription className="sr-only">
                                Search by name or SKU.
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
                className="w-(--radix-popover-trigger-width) max-w-[calc(100vw-2rem)] min-w-80 p-0"
            >
                {search}
            </PopoverContent>
        </Popover>
    );
}

type ProductSearchProps = {
    selected: number[];
    include: number | null;
    clearLabel?: string;
    onSelect: (product: RestockProductRef | null) => void;
    mobile: boolean;
};

/** The open picker: the search box and the server's answer, mounted only while open. */
function ProductSearch({
    selected,
    include,
    clearLabel,
    onSelect,
    mobile,
}: ProductSearchProps) {
    const [search, setSearch] = useState('');
    const inputRef = useRef<HTMLInputElement>(null);
    const { options, loading, error, retry } = useProductOptions(
        search,
        include,
    );
    const typed = search.trim();

    let status: ReactNode = null;

    if (loading) {
        status = <p className="py-2">Searching…</p>;
    } else if (!error && options.length === 0) {
        status = (
            <p className="py-4">
                {typed === ''
                    ? 'No products yet.'
                    : `No product matches "${typed}".`}
            </p>
        );
    }

    return (
        <Command
            label="Search products"
            shouldFilter={false}
            className="flex min-h-0 flex-1 flex-col"
        >
            <CommandInput
                ref={inputRef}
                placeholder="Search products"
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
                    {options.map((product) => {
                        const isSelected = selected.includes(product.id);

                        return (
                            <CommandItem
                                key={product.id}
                                value={String(product.id)}
                                onSelect={() => onSelect(product)}
                                aria-selected={isSelected || undefined}
                                className="gap-2 py-2"
                            >
                                <Check
                                    aria-hidden="true"
                                    className={
                                        isSelected
                                            ? 'size-4 shrink-0'
                                            : 'size-4 shrink-0 opacity-0'
                                    }
                                />
                                <span className="flex min-w-0 flex-1 flex-col">
                                    <span className="truncate font-medium">
                                        {product.name}
                                        {isSelected && (
                                            <span className="sr-only">
                                                {' '}
                                                (on this restock)
                                            </span>
                                        )}
                                    </span>
                                    <span className="font-mono text-xs text-muted-foreground">
                                        {product.sku}
                                    </span>
                                </span>
                                {product.status !== 'active' && (
                                    <ProductStatusBadge
                                        status={product.status}
                                        label={STATUS_LABEL[product.status]}
                                    />
                                )}
                            </CommandItem>
                        );
                    })}
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
                    <p>Couldn&apos;t load the products. Try again.</p>
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
