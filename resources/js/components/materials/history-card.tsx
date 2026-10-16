import { useId } from 'react';
import { HistoryList } from '@/components/materials/history-list';
import type { Material, StockMovement } from '@/types/domain';

type HistoryCardProps = {
    material: Material;
    /** Every movement of the material, newest first (the server's order). */
    history: StockMovement[];
};

/** The material's full history, under "What you can make with it". */
export function HistoryCard({ material, history }: HistoryCardProps) {
    const titleId = useId();

    return (
        <section
            aria-labelledby={titleId}
            className="rounded-xl border bg-card p-5 text-card-foreground shadow-sm"
        >
            <div className="mb-2 space-y-0.5">
                <h2 id={titleId} className="text-lg font-bold">
                    History
                </h2>
                <p className="text-sm text-muted-foreground">
                    Restocks, cutting, stock corrections and unit price changes,
                    newest first.
                </p>
            </div>
            <HistoryList entries={history} unit={material.unit} />
        </section>
    );
}
