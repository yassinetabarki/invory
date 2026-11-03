import { Search, SlidersHorizontal } from 'lucide-react';
import { ClientPicker } from '@/components/clients/client-picker';
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
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useIsMobile } from '@/hooks/use-mobile';
import type { ClientRef, Option, OrderStatusFilter } from '@/types/domain';

type OrdersFiltersProps = {
    /** The text in the search box (controlled; the page debounces it). */
    search: string;
    status: OrderStatusFilter;
    /** "Open orders", "Delivered", "Cancelled", "All orders", from the server. */
    statuses: Option<OrderStatusFilter>[];
    onSearchChange: (text: string) => void;
    onStatusChange: (status: OrderStatusFilter) => void;
    /** INV-4, AC-7: one client's orders; null for "All clients". */
    client: ClientRef | null;
    onClientChange: (client: ClientRef | null) => void;
};

/**
 * The search box, the Status select and the Client filter above the orders list. On a phone (INV-14, AC-5) the search stays and
 * **Filters** ("Filters · 1" when one is set) opens a full-height panel with Status and Client.
 */
export function OrdersFilters({
    search,
    status,
    statuses,
    onSearchChange,
    onStatusChange,
    client,
    onClientChange,
}: OrdersFiltersProps) {
    const mobile = useIsMobile();
    const set = (status === 'open' ? 0 : 1) + (client === null ? 0 : 1);

    const statusSelect = (
        <Select
            value={status}
            onValueChange={(value) =>
                onStatusChange(value as OrderStatusFilter)
            }
        >
            <SelectTrigger id="orders-status" className="w-full max-md:h-11">
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
    );
    const clientPicker = (
        <ClientPicker
            id="orders-client"
            labelledBy="orders-client-label"
            value={client}
            onSelect={onClientChange}
            clearLabel="All clients"
        />
    );

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
                    className="pl-9 max-md:h-11"
                    onChange={(event) => onSearchChange(event.target.value)}
                />
            </div>

            {mobile ? (
                <Sheet>
                    <SheetTrigger asChild>
                        <Button
                            type="button"
                            variant="outline"
                            className="h-11"
                        >
                            <SlidersHorizontal aria-hidden="true" />
                            {set > 0 ? `Filters · ${set}` : 'Filters'}
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="bottom" className="h-dvh gap-4 p-4">
                        <SheetHeader className="p-0">
                            <SheetTitle>Filters</SheetTitle>
                            <SheetDescription className="sr-only">
                                Status and client of the orders shown.
                            </SheetDescription>
                        </SheetHeader>
                        <div className="flex flex-col gap-2">
                            <Label htmlFor="orders-status">Status</Label>
                            {statusSelect}
                        </div>
                        <div className="flex flex-col gap-2">
                            <p
                                id="orders-client-label"
                                className="text-sm font-medium"
                            >
                                Client
                            </p>
                            {clientPicker}
                        </div>
                    </SheetContent>
                </Sheet>
            ) : (
                <>
                    <div className="md:w-48">
                        <Label htmlFor="orders-status" className="sr-only">
                            Status
                        </Label>
                        {statusSelect}
                    </div>

                    <div className="md:w-56">
                        <p id="orders-client-label" className="sr-only">
                            Client
                        </p>
                        {clientPicker}
                    </div>
                </>
            )}
        </div>
    );
}
