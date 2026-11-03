import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { BLOCKED_TAB, printTickets, ticketsUrl } from '@/lib/print-tickets';

const toast = vi.hoisted(() => ({ error: vi.fn() }));
vi.mock('sonner', () => ({ toast }));

describe('printTickets (INV-15, AC-2)', () => {
    afterEach(() => {
        vi.restoreAllMocks();
        toast.error.mockClear();
    });

    it('asks for the batches in the order given', () => {
        expect(decodeURIComponent(ticketsUrl([12, 13]))).toBe(
            '/progress/tickets?batches[]=12&batches[]=13',
        );
    });

    it("opens the tickets' page in a new tab", () => {
        const open = vi.spyOn(window, 'open').mockReturnValue({} as Window);

        expect(printTickets([12])).toBe(true);
        expect(open).toHaveBeenCalledWith(ticketsUrl([12]), '_blank');
        expect(toast.error).not.toHaveBeenCalled();
    });

    it('says so when the browser blocks the tab', () => {
        vi.spyOn(window, 'open').mockReturnValue(null);

        expect(printTickets([12])).toBe(false);
        expect(toast.error).toHaveBeenCalledWith(BLOCKED_TAB);
        expect(BLOCKED_TAB).toBe(
            "Your browser blocked the tickets' tab. Allow pop-ups for Invory, then print again.",
        );
    });
});
