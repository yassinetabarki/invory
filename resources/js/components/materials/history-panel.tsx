import { Deferred, Link } from '@inertiajs/react';
import { HistoryList } from '@/components/materials/history-list';
import { InlinePanel } from '@/components/materials/inline-panel';
import { Skeleton } from '@/components/ui/skeleton';
import type { Material, StockMovement } from '@/types/domain';

type HistoryPanelProps = {
    id: string;
    material: Material;
    /**
     * The page's deferred `recent_history` (the newest movements of every
     * material on the page); undefined until the deferred request has landed.
     */
    recentHistory: StockMovement[] | undefined;
    /** URL of the material's detail page (Wayfinder in the page). */
    showHref: string;
    onClose: () => void;
};

function HistorySkeleton() {
    return (
        <div role="status" className="flex flex-col gap-3">
            <span className="sr-only">Loading history…</span>
            {[0, 1, 2].map((row) => (
                <Skeleton key={row} className="h-5 w-full" />
            ))}
        </div>
    );
}

/**
 * The index's History panel: the material's recent movements from the
 * page's deferred `recent_history` prop (a skeleton until it arrives), with a
 * link to the full history on the detail page.
 */
export function HistoryPanel({
    id,
    material,
    recentHistory,
    showHref,
    onClose,
}: HistoryPanelProps) {
    const entries = (recentHistory ?? []).filter(
        (entry) => entry.material_id === material.id,
    );

    return (
        <InlinePanel
            id={id}
            title={`History · ${material.name}`}
            description="Restocks, cutting, stock corrections and unit price changes, newest first."
            closeLabel="Close history"
            focusOnMount
            onClose={onClose}
        >
            <Deferred data="recent_history" fallback={<HistorySkeleton />}>
                <HistoryList entries={entries} unit={material.unit} />
            </Deferred>
            <Link
                href={showHref}
                className="w-fit text-sm font-semibold text-primary underline-offset-4 hover:underline"
            >
                Open material page
            </Link>
        </InlinePanel>
    );
}
