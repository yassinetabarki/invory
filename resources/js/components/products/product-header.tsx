import type { ReactNode } from 'react';
import { ColourTag } from '@/components/shared/colour-tag';
import { ProductStatusBadge } from '@/components/shared/product-status-badge';
import { Badge } from '@/components/ui/badge';
import type { Product } from '@/types/domain';

type ProductHeaderProps = {
    product: Pick<
        Product,
        | 'name'
        | 'sku'
        | 'category_label'
        | 'sizes_line'
        | 'colours'
        | 'status'
        | 'status_label'
    >;
    /** Record sale, Edit, Delete. */
    actions?: ReactNode;
};

/** Name as the page title, with SKU, category and status under it, then the product's colours. */
export function ProductHeader({ product, actions }: ProductHeaderProps) {
    return (
        <header className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div className="min-w-0 space-y-2">
                <h1 className="text-2xl font-semibold tracking-tight break-words">
                    {product.name}
                </h1>
                <div className="flex flex-wrap items-center gap-2">
                    <span className="font-mono text-xs text-muted-foreground">
                        {product.sku}
                    </span>
                    <Badge
                        variant="outline"
                        className="rounded-full border-transparent bg-neutral font-semibold text-neutral-foreground"
                    >
                        {product.category_label}
                    </Badge>
                    <Badge
                        variant="outline"
                        className="rounded-full font-semibold"
                    >
                        {product.sizes_line}
                    </Badge>
                    <ProductStatusBadge
                        status={product.status}
                        label={product.status_label}
                    />
                </div>
                {product.colours.length > 0 && (
                    <p className="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                        {/* Screen readers hear one sentence; the swatches and tags are for the eyes. */}
                        <span className="sr-only">
                            Colours:{' '}
                            {product.colours
                                .map((colour) => colour.name)
                                .join(', ')}
                        </span>
                        <span
                            aria-hidden="true"
                            className="flex flex-wrap items-center gap-x-4 gap-y-1"
                        >
                            <span className="text-muted-foreground">
                                Colours
                            </span>
                            {product.colours.map((colour) => (
                                <ColourTag key={colour.id} colour={colour} />
                            ))}
                        </span>
                    </p>
                )}
            </div>
            {actions && (
                <div className="flex shrink-0 flex-wrap items-center gap-2">
                    {actions}
                </div>
            )}
        </header>
    );
}
