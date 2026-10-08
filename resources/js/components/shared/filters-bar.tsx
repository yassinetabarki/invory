import { X } from 'lucide-react';
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
    MaterialCategory,
    MaterialFilters,
    MaterialSort,
    Option,
} from '@/types/domain';

export const SEARCH_DEBOUNCE_MS = 300;

/** Radix Select items cannot have an empty value, so "All" gets a sentinel. */
const ALL_CATEGORIES = '__all__';

const SORT_OPTIONS: Option<MaterialSort>[] = [
    { value: 'name', label: 'Name A–Z' },
    { value: '-name', label: 'Name Z–A' },
    { value: 'sku', label: 'SKU A–Z' },
    { value: '-sku', label: 'SKU Z–A' },
    { value: 'quantity_on_hand', label: 'Stock low→high' },
    { value: '-quantity_on_hand', label: 'Stock high→low' },
    { value: 'unit_cost', label: 'Unit cost low→high' },
    { value: '-unit_cost', label: 'Unit cost high→low' },
    { value: '-updated_at', label: 'Recently updated' },
    { value: 'updated_at', label: 'Least recently updated' },
];

type FiltersBarProps = {
    /** Current filters (controlled). */
    filters: MaterialFilters;
    categories: Option<MaterialCategory>[];
    onChange: (next: MaterialFilters) => void;
};

/**
 * Controlled filters for the materials list. Category, "Needs restock" and
 * sort emit immediately; search is debounced by 300 ms. "Clear filters" resets
 * search, category and "Needs restock" and keeps the chosen sort.
 */
export function FiltersBar({ filters, categories, onChange }: FiltersBarProps) {
    const [text, setText] = useState(filters.search ?? '');
    // The last search value we emitted. A server echo of it must not
    // overwrite what the user has typed since.
    const [emitted, setEmitted] = useState(filters.search);
    const [seenSearch, setSeenSearch] = useState(filters.search);

    // External change of `filters.search` (e.g. back button, clear): adopt it.
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
        filters.category !== null ||
        filters.low_stock;

    function clearFilters() {
        setText('');
        setEmitted(null);
        onChange({
            ...filters,
            search: null,
            category: null,
            low_stock: false,
        });
    }

    return (
        <div
            role="search"
            aria-label="Filter materials"
            className="flex flex-col gap-3 md:flex-row md:flex-wrap md:items-center"
        >
            <div className="md:min-w-64 md:flex-1">
                <Label htmlFor="materials-search" className="sr-only">
                    Search materials
                </Label>
                <Input
                    id="materials-search"
                    type="search"
                    value={text}
                    placeholder="Search by name or SKU"
                    autoComplete="off"
                    onChange={(event) => setText(event.target.value)}
                />
            </div>

            <div className="md:w-48">
                <Label htmlFor="materials-category" className="sr-only">
                    Category
                </Label>
                <Select
                    value={filters.category ?? ALL_CATEGORIES}
                    onValueChange={(value) =>
                        onChange({
                            ...filters,
                            category:
                                value === ALL_CATEGORIES
                                    ? null
                                    : (value as MaterialCategory),
                        })
                    }
                >
                    <SelectTrigger id="materials-category" className="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL_CATEGORIES}>
                            All categories
                        </SelectItem>
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

            <div className="md:w-56">
                <Label htmlFor="materials-sort" className="sr-only">
                    Sort by
                </Label>
                <Select
                    value={filters.sort}
                    onValueChange={(value) =>
                        onChange({ ...filters, sort: value as MaterialSort })
                    }
                >
                    <SelectTrigger id="materials-sort" className="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {SORT_OPTIONS.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="flex items-center gap-2">
                <Checkbox
                    id="materials-low-stock"
                    checked={filters.low_stock}
                    onCheckedChange={(checked) =>
                        onChange({ ...filters, low_stock: checked === true })
                    }
                />
                <Label htmlFor="materials-low-stock">Needs restock</Label>
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
