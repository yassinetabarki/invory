import { Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { BomTable } from '@/components/products/bom-table';
import { EmptyState } from '@/components/shared/empty-state';
import { Button } from '@/components/ui/button';
import { CACHE_TAGS, prefetchProps } from '@/lib/cache-tags';
import type {
    Decimal,
    MaterialRef,
    ProductComponentLine,
} from '@/types/domain';

type CompositionCardProps = {
    lines: ProductComponentLine[];
    materialsTotal: Decimal;
    capacity: number | null;
    materialHref: (material: MaterialRef) => string;
    /** The product's edit page, where the composition is changed. */
    editHref: string;
};

/** The "Composition" section of the product page: heading, edit link and table. */
export function CompositionCard({
    lines,
    materialsTotal,
    capacity,
    materialHref,
    editHref,
}: CompositionCardProps) {
    return (
        <section
            aria-labelledby="composition-title"
            className="overflow-hidden rounded-xl border bg-card text-card-foreground"
        >
            <div className="flex flex-wrap items-end justify-between gap-3 px-4 pt-5 pb-3 md:px-5">
                <div className="space-y-0.5">
                    <h2 id="composition-title" className="text-lg font-bold">
                        Composition
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Materials needed to make one unit.
                    </p>
                </div>
                {lines.length > 0 && (
                    <Link
                        {...prefetchProps(CACHE_TAGS.products)}
                        href={editHref}
                        className="text-sm font-semibold text-primary underline-offset-4 hover:underline"
                    >
                        Edit composition
                    </Link>
                )}
            </div>

            {lines.length === 0 ? (
                <div className="px-4 pb-5 md:px-5">
                    <EmptyState
                        title="No materials yet"
                        description="Add the materials that make one unit to see the cost and how many can be produced."
                        action={
                            <Button asChild variant="outline">
                                <Link
                                    {...prefetchProps(CACHE_TAGS.products)}
                                    href={editHref}
                                >
                                    <Plus aria-hidden="true" />
                                    Add materials
                                </Link>
                            </Button>
                        }
                    />
                </div>
            ) : (
                <BomTable
                    lines={lines}
                    materialsTotal={materialsTotal}
                    capacity={capacity}
                    materialHref={materialHref}
                />
            )}
        </section>
    );
}
