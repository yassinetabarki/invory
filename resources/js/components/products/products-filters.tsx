import { Search, X } from 'lucide-react';
import { useEffect, useEffectEvent, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
    ProductCategory,
    ProductFilters,
    ProductStatus,
} from '@/types/domain';

export const PRODUCT_SEARCH_DEBOUNCE_MS = 300;

/** Radix Select items cannot have an empty value, so "All" gets a sentinel. */
const ALL = '__all__';

type ProductsFiltersProps = {
    /** Current filters (controlled). */
    filters: ProductFilters;
    categories: Option<ProductCategory>[];
    statuses: Option<ProductStatus>[];
    onChange: (next: ProductFilters) => void;
};

/**
 * Controlled filters for the products list. Category, status and "Can't
 * produce" emit immediately; search is debounced by 300 ms.
 */
export function ProductsFilters({
    filters,
    categories,
    statuses,
    onChange,
}: ProductsFiltersProps) {
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

    const debouncedText = useDebouncedValue(text, PRODUCT_SEARCH_DEBOUNCE_MS);

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
        filters.category !== null ||
        filters.status !== null ||
        filters.cant_produce;

    function clearFilters() {
        setText('');
        setEmitted(null);
        onChange({
            search: null,
            category: null,
            status: null,
            cant_produce: false,
        });
    }

    return (
        <div
            role="search"
            aria-label="Filter products"
            className="flex flex-col gap-3 md:flex-row md:flex-wrap md:items-center"
        >
            <div className="relative md:min-w-64 md:flex-1">
                <Label htmlFor="products-search" className="sr-only">
                    Search products
                </Label>
                <Search
                    className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    aria-hidden="true"
                />
                <Input
                    id="products-search"
                    type="search"
                    value={text}
                    placeholder="Search by name or SKU"
                    autoComplete="off"
                    className="pl-9"
                    onChange={(event) => setText(event.target.value)}
                />
            </div>

            <div className="md:w-44">
                <Label htmlFor="products-category" className="sr-only">
                    Category
                </Label>
                <Select
                    value={filters.category ?? ALL}
                    onValueChange={(value) =>
                        onChange({
                            ...filters,
                            category:
                                value === ALL
                                    ? null
                                    : (value as ProductCategory),
                        })
                    }
                >
                    <SelectTrigger id="products-category" className="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL}>All categories</SelectItem>
                        {categories.map((category) => (
                            <SelectItem
                                key={category.value}
                                value={category.value}
                            >
                                {category.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="md:w-40">
                <Label htmlFor="products-status" className="sr-only">
                    Status
                </Label>
                <Select
                    value={filters.status ?? ALL}
                    onValueChange={(value) =>
                        onChange({
                            ...filters,
                            status:
                                value === ALL ? null : (value as ProductStatus),
                        })
                    }
                >
                    <SelectTrigger id="products-status" className="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL}>All statuses</SelectItem>
                        {statuses.map((status) => (
                            <SelectItem key={status.value} value={status.value}>
                                {status.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="flex items-center gap-2">
                <Checkbox
                    id="products-cant-produce"
                    checked={filters.cant_produce}
                    onCheckedChange={(checked) =>
                        onChange({ ...filters, cant_produce: checked === true })
                    }
                />
                <Label htmlFor="products-cant-produce">Can't produce</Label>
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
