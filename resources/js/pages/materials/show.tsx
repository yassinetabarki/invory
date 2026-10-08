import { Head } from '@inertiajs/react';
import MaterialController from '@/actions/App/Http/Controllers/MaterialController';
import { CategoryBadge } from '@/components/materials/category-badge';
import { HistoryCard } from '@/components/materials/history-card';
import { MaterialActions } from '@/components/materials/material-actions';
import { MaterialDetailsCard } from '@/components/materials/material-details-card';
import { MaterialKpis } from '@/components/materials/material-kpis';
import { MaterialPhotoCard } from '@/components/materials/material-photo-card';
import { MaterialStockCallout } from '@/components/materials/material-stock-callout';
import { MaterialUsageCard } from '@/components/materials/material-usage';
import { StockBadge } from '@/components/materials/stock-badge';
import type { MaterialShowProps } from '@/types/domain';

export default function MaterialsShow({
    material,
    usage,
    history,
}: MaterialShowProps) {
    return (
        <>
            <Head title={material.name} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <header className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="min-w-0 space-y-2">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {material.name}
                        </h1>
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="font-mono text-sm text-muted-foreground">
                                {material.sku}
                            </span>
                            <CategoryBadge label={material.category_label} />
                            <StockBadge status={material.stock_status} />
                        </div>
                    </div>
                    <MaterialActions material={material} />
                </header>

                <div className="flex flex-col gap-5 lg:flex-row lg:items-start">
                    <MaterialPhotoCard
                        name={material.name}
                        photoUrl={material.photo_url}
                        editHref={MaterialController.edit.url(material)}
                    />
                    <div className="flex min-w-0 flex-1 flex-col gap-4">
                        <MaterialKpis material={material} />
                        <MaterialStockCallout material={material} />
                        <MaterialDetailsCard material={material} />
                    </div>
                </div>

                <MaterialUsageCard material={material} usage={usage} />

                <HistoryCard material={material} history={history} />
            </div>
        </>
    );
}

MaterialsShow.layout = (props: MaterialShowProps) => ({
    breadcrumbs: [
        {
            title: 'Materials',
            href: MaterialController.index(),
        },
        {
            title: props.material.name,
            href: MaterialController.show(props.material),
        },
    ],
});
