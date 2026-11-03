import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { openInNewTab } from '@/lib/print-tickets';
import type { FlashToast } from '@/types/ui';

export function useFlashToast(): void {
    useEffect(() => {
        return router.on('flash', (event) => {
            const flash = (event as CustomEvent).detail?.flash;
            const data = flash?.toast as FlashToast | undefined;

            if (!data) {
                return;
            }

            // INV-5: Put in stock's toast carries "See the restock", a link to it. INV-15: a split's carries "Print ticket B-0155",
            // which opens the new batch's ticket in a new tab.
            const action = data.action;

            toast[data.type](
                data.message,
                action
                    ? {
                          action: {
                              label: action.label,
                              onClick: () =>
                                  action.new_tab
                                      ? openInNewTab(action.href)
                                      : router.visit(action.href),
                          },
                      }
                    : undefined,
            );
        });
    }, []);
}
