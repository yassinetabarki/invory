import { toast } from 'sonner';
import { tickets } from '@/routes/progress';

/** What the page says when the browser blocks the tickets' tab (INV-15, AC-2). */
export const BLOCKED_TAB =
    "Your browser blocked the tickets' tab. Allow pop-ups for Invory, then print again.";

/** The address of the page that prints the tickets of these batches, in this order. */
export function ticketsUrl(batchIds: number[]): string {
    return tickets.url({ query: { batches: batchIds } });
}

/**
 * Opens an address in a new tab, from the click itself so pop-up blockers let it through. When the browser still blocks it, the toast
 * says so; nothing is lost, the button can be pressed again. Returns whether the tab opened.
 */
export function openInNewTab(href: string): boolean {
    const tab = window.open(href, '_blank');

    if (tab === null) {
        toast.error(BLOCKED_TAB);

        return false;
    }

    return true;
}

/** Print tickets (INV-15, AC-2): opens the tickets' page in a new tab, which calls the browser's print window once drawn. */
export function printTickets(batchIds: number[]): boolean {
    return openInNewTab(ticketsUrl(batchIds));
}
