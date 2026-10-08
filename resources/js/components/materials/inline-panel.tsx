import { X } from 'lucide-react';
import { useEffect, useId, useRef } from 'react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type InlinePanelProps = {
    /** The id the opening button names in `aria-controls`. */
    id: string;
    title: string;
    description?: ReactNode;
    /** Accessible name of the close button, e.g. "Close restock". */
    closeLabel: string;
    onClose: () => void;
    /**
     * Move focus to the panel itself when it opens. Leave off for panels whose
     * first field takes focus (a form), so the cursor lands where the user types.
     */
    focusOnMount?: boolean;
    className?: string;
    children: ReactNode;
};

/**
 * A titled group that opens inside a table row or card (restock, history).
 * Escape and the close button call `onClose`, which returns focus to the
 * button that opened the panel.
 */
export function InlinePanel({
    id,
    title,
    description,
    closeLabel,
    onClose,
    focusOnMount = false,
    className,
    children,
}: InlinePanelProps) {
    const titleId = useId();
    const panelRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (focusOnMount) {
            panelRef.current?.focus();
        }
    }, [focusOnMount]);

    return (
        <div
            ref={panelRef}
            id={id}
            role="group"
            aria-labelledby={titleId}
            tabIndex={-1}
            onKeyDown={(event) => {
                if (event.key === 'Escape') {
                    event.stopPropagation();
                    onClose();
                }
            }}
            className={cn(
                'flex w-full max-w-2xl flex-col gap-3.5 rounded-xl border bg-card p-4 text-left whitespace-normal text-card-foreground shadow-md outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50',
                className,
            )}
        >
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0 space-y-0.5">
                    <h2 id={titleId} className="text-base font-semibold">
                        {title}
                    </h2>
                    {description && (
                        <p className="text-xs text-muted-foreground">
                            {description}
                        </p>
                    )}
                </div>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label={closeLabel}
                    className="-mt-1 -mr-1 shrink-0"
                    onClick={onClose}
                >
                    <X aria-hidden="true" />
                </Button>
            </div>
            {children}
        </div>
    );
}
