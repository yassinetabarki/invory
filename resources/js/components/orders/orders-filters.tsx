import { Search } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Option, OrderStatusFilter } from '@/types/domain';

type OrdersFiltersProps = {
    /** The text in the search box (controlled; the page debounces it). */
    search: string;
    status: OrderStatusFilter;
    /** "Open orders", "Shipped", "All orders", from the server. */
    statuses: Option<OrderStatusFilter>[];
    onSearchChange: (text: string) => void;
    onStatusChange: (status: OrderStatusFilter) => void;
};

/** The search box and the Status select above the orders list. */
export function OrdersFilters({
    search,
    status,
    statuses,
    onSearchChange,
    onStatusChange,
}: OrdersFiltersProps) {
    return (
        <div
            role="search"
            aria-label="Search orders"
            className="flex flex-col gap-3 border-b p-4 md:flex-row md:items-center"
        >
            <div className="relative md:flex-1">
                <Label htmlFor="orders-search" className="sr-only">
                    Search orders
                </Label>
                <Search
                    className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    aria-hidden="true"
                />
                <Input
                    id="orders-search"
                    type="search"
                    value={search}
                    placeholder="Search client, product or note"
                    autoComplete="off"
                    className="pl-9"
                    onChange={(event) => onSearchChange(event.target.value)}
                />
            </div>

            <div className="md:w-48">
                <Label htmlFor="orders-status" className="sr-only">
                    Status
                </Label>
                <Select
                    value={status}
                    onValueChange={(value) =>
                        onStatusChange(value as OrderStatusFilter)
                    }
                >
                    <SelectTrigger id="orders-status" className="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {statuses.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
        </div>
    );
}
