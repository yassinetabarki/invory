import { Search, X } from 'lucide-react';
import { useEffect, useEffectEvent, useState } from 'react';
import { SEARCH_DEBOUNCE_MS } from '@/components/shared/filters-bar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import type {
    Option,
    SaleProductOption,
    SalesFilters,
    SalesPeriod,
} from '@/types/domain';
import { DEFAULT_PERIOD, PeriodSelect } from '@/components/sales/period-select';

/** Radix Select items cannot have an empty value, so "All products" gets a sentinel. */
const ALL_PRODUCTS = '__all__';

type SalesFilterBarProps = {
    /** Current filters (controlled). */
    filters: SalesFilters;
    periods: Option<SalesPeriod>[];
    /** Every non-deleted product, whatever its status: old sales may belong to any. */
    products: SaleProductOption[];
    onChange: (next: SalesFilters) => void;
};

/**
 * Controlled filters of the sales log: search (debounced by 300 ms), period
 * and product emit at once. "Clear filters" resets all three.
 */
export function SalesFilterBar({
    filters,
    periods,
    products,
    onChange,
}: SalesFilterBarProps) {
    const [text, setText] = useState(filters.search ?? '');
    // The last search value we emitted. A server echo of it must not
    // overwrite what the user has typed since.
    const [emitted, setEmitted] = useState(filters.search);
    const [seenSearch, setSeenSearch] = useState(filters.search);

    // External change of `filters.search` (back button, clear): adopt it.
    if (filters.search !== seenSearch) {
        setSeenSearch(filters.search);

        if (filters.search !== emitted) {
            setText(filters.search ?? '');
            setEmitted(filters.search);
        }
    }

    const debouncedText = useDebouncedValue(text, SEARCH_DEBOUNCE_MS);

    const emitSearch = useEffectEvent((value: string) => {
        const search = value.trim() === '' ? null : value.trim();

        if (search !== emitted) {
            setEmitted(search);
            onChange({ ...filters, search });
        }
    });

    useEffect(() => {
        emitSearch(debouncedText);
    }, [debouncedText]);

    const hasActiveFilters =
        text.trim() !== '' ||
        filters.search !== null ||
        filters.product_id !== null ||
        filters.period !== DEFAULT_PERIOD;

    function clearFilters() {
        setText('');
        setEmitted(null);
        onChange({ search: null, product_id: null, period: DEFAULT_PERIOD });
    }

    return (
        <div
            role="search"
            aria-label="Filter sales"
            className="flex flex-wrap items-center gap-2.5 border-b p-4"
        >
            <div className="relative min-w-0 flex-[1_1_14rem]">
                <Label htmlFor="sales-search" className="sr-only">
                    Search sales
                </Label>
                <Search
                    className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    aria-hidden="true"
                />
                <Input
                    id="sales-search"
                    type="search"
                    value={text}
                    placeholder="Search product or note"
                    autoComplete="off"
                    className="pl-9"
                    onChange={(event) => setText(event.target.value)}
                />
            </div>

            <PeriodSelect
                id="sales-period"
                value={filters.period}
                periods={periods}
                onChange={(period) => onChange({ ...filters, period })}
                className="w-full sm:w-40"
            />

            <div className="w-full sm:w-52">
                <Label htmlFor="sales-product" className="sr-only">
                    Product
                </Label>
                <Select
                    value={
                        filters.product_id === null
                            ? ALL_PRODUCTS
                            : String(filters.product_id)
                    }
                    onValueChange={(value) =>
                        onChange({
                            ...filters,
                            product_id:
                                products.find(
                                    (product) => String(product.id) === value,
                                )?.id ?? null,
                        })
                    }
                >
                    <SelectTrigger id="sales-product" className="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL_PRODUCTS}>
                            All products
                        </SelectItem>
                        {products.map((product) => (
                            <SelectItem
                                key={product.id}
                                value={String(product.id)}
                            >
                                {product.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            {hasActiveFilters && (
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={clearFilters}
                >
                    <X aria-hidden="true" />
                    Clear filters
                </Button>
            )}
        </div>
    );
}
