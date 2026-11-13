import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { openInNewTab } from '@/lib/print-tickets';
import type { FlashToast } from '@/types/ui';

/** The window event a toast's `restock` action dispatches; `RestockSheetHost` (in the app layout) opens the sheet. */
export const RESTOCK_SHEET_EVENT = 'invory:restock-sheet';

export function useFlashToast(): void {
    useEffect(() => {
        return router.on('flash', (event) => {
            const flash = (event as CustomEvent).detail?.flash;
            const data = flash?.toast as FlashToast | undefined;

            if (!data) {
                return;
            }

            // INV-5: Put in stock's toast carries "See the restock", a link to it. INV-15: a split's carries "Print ticket B-0155",
            // which opens the new batch's ticket in a new tab. INV-21: a colour added carries "Restock Light wash", which opens
            // the Restock sheet on that colour over the page.
            const action = data.action;

            toast[data.type](
                data.message,
                action
                    ? {
                          action: {
                              label: action.label,
                              onClick: () => {
                                  if (action.restock) {
                                      window.dispatchEvent(
                                          new CustomEvent(RESTOCK_SHEET_EVENT, {
                                              detail: action.restock,
                                          }),
                                      );
                                  } else if (action.new_tab) {
                                      openInNewTab(action.href);
                                  } else {
                                      router.visit(action.href);
                                  }
                              },
                          },
                      }
                    : undefined,
            );
        });
    }, []);
}
