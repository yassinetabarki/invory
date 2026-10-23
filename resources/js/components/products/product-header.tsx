import type { ReactNode } from 'react';
import { ProductStatusBadge } from '@/components/shared/product-status-badge';
import { Badge } from '@/components/ui/badge';
import type { Product } from '@/types/domain';

type ProductHeaderProps = {
    product: Pick<
        Product,
        | 'name'
        | 'sku'
        | 'category_label'
        | 'size_list'
        | 'status'
        | 'status_label'
    >;
    /** Record sale, Edit, Delete. */
    actions?: ReactNode;
};

/** Name as the page title, with SKU, category and status under it. */
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
                        {product.size_list.sizes.length > 1
                            ? `Sizes ${product.size_list.name}`
                            : product.size_list.name}
                    </Badge>
                    <ProductStatusBadge
                        status={product.status}
                        label={product.status_label}
                    />
                </div>
            </div>
            {actions && (
                <div className="flex shrink-0 flex-wrap items-center gap-2">
                    {actions}
                </div>
            )}
        </header>
    );
}
