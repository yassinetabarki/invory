import { Link } from '@inertiajs/react';
import { CircleCheck } from 'lucide-react';
import { useId } from 'react';
import MaterialController from '@/actions/App/Http/Controllers/MaterialController';
import { StockBadge } from '@/components/materials/stock-badge';
import { PhotoThumb } from '@/components/shared/photo-thumb';
import { Quantity } from '@/components/shared/quantity';
import type { Material } from '@/types/domain';

type NeedsRestockCardProps = {
    /** The server's shortlist (out of stock first, then low; max 5). */
    materials: Material[];
};

/** Materials at or below their reorder level, each with its stock status. */
export function NeedsRestockCard({ materials }: NeedsRestockCardProps) {
    const headingId = useId();

    return (
        <section
            aria-labelledby={headingId}
            className="flex min-w-0 flex-col gap-3 rounded-xl border bg-card p-5 text-card-foreground"
        >
            <div className="flex items-center justify-between gap-3">
                <h2 id={headingId} className="text-base font-bold">
                    Needs restock
                </h2>
                <Link
                    href={MaterialController.index({
                        query: { low_stock: 1 },
                    })}
                    className="text-[0.8125rem] font-semibold text-primary hover:underline focus-visible:underline"
                >
                    View all
                </Link>
            </div>

            {materials.length === 0 ? (
                <p className="flex items-center gap-2 py-6 text-sm text-muted-foreground">
                    <CircleCheck
                        className="size-4 shrink-0"
                        aria-hidden="true"
                    />
                    All materials are above their reorder level.
                </p>
            ) : (
                <ul>
                    {materials.map((material) => (
                        <li
                            key={material.id}
                            className="flex items-center gap-3 border-t py-2.5"
                        >
                            <PhotoThumb
                                src={material.photo_url}
                                alt=""
                                size="md"
                            />
                            <span className="flex min-w-0 flex-1 flex-col">
                                <Link
                                    href={MaterialController.show(material.id)}
                                    className="font-semibold hover:underline focus-visible:underline"
                                >
                                    {material.name}
                                </Link>
                                <span className="text-xs text-muted-foreground tabular-nums">
                                    <Quantity
                                        value={material.quantity_on_hand}
                                        unit={material.unit}
                                    />
                                    {material.reorder_level !== null && (
                                        <>
                                            {' · reorder at '}
                                            <Quantity
                                                value={material.reorder_level}
                                                unit={material.unit}
                                            />
                                        </>
                                    )}
                                </span>
                            </span>
                            <StockBadge status={material.stock_status} />
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
