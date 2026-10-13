<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Sales\ProductSales;
use App\Domain\Sales\SaleLine;
use App\Enums\SalesPeriod;
use App\Http\Requests\DashboardRequest;
use App\Http\Resources\MaterialRefResource;
use App\Http\Resources\MaterialResource;
use App\Http\Resources\SaleResource;
use App\Models\Material;
use App\Models\Product;
use App\Models\Sale;
use App\Services\Inventory\InventorySummary;
use App\Services\Products\ProductCatalog;
use App\Services\Reference\ReferenceData;
use App\Services\Sales\SalesReport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The dashboard on real data (S-06-BE): sales through {@see SalesReport}, the costed products through {@see ProductCatalog},
 * inventory totals and the restock list from materials. Authorized in {@see DashboardRequest} (`viewAny` on materials, products
 * and sales). Sends the DashboardProps contract.
 */
final class DashboardController extends Controller
{
    /** Rows of the top-products rankings and of the "needs restock" and "recent sales" lists. */
    private const LIST_SIZE = 5;

    public function __invoke(
        DashboardRequest $request,
        InventorySummary $inventory,
        SalesReport $report,
        ProductCatalog $catalog,
        ReferenceData $reference,
    ): Response {
        $period = $request->period();

        // Every section is a closure, so a partial reload (the period switch) skips the ones it does not list. The sales of the
        // period feed the stats and both rankings: a visit reads them once.
        /** @var list<SaleLine>|null $periodLines */
        $periodLines = null;
        $lines = function () use (&$periodLines, $report, $period): array {
            return $periodLines ??= $report->periodLines($period);
        };

        return Inertia::render('dashboard', [
            'period' => $period->value,
            'periods' => SalesPeriod::options(),
            'stats' => fn (): array => [
                ...$report->periodSummary($lines()),
                ...$inventory->summarize(),
                'unproducible_count' => $catalog->unproducibleCount(),
                'products_count' => $catalog->count(),
            ],
            'top_products' => function () use ($report, $lines, $catalog): array {
                $top = $report->topProducts($lines(), self::LIST_SIZE);

                return ['by_units' => $this->rankingRows($top['by_units'], $catalog), 'by_revenue' => $this->rankingRows($top['by_revenue'], $catalog)];
            },
            'needs_restock' => fn (): array => MaterialResource::collection($this->needsRestock($reference))->resolve(),
            // The newest sales, whatever the period.
            'recent_sales' => function () use ($report, $catalog): array {
                $sales = $report->recent(self::LIST_SIZE);
                $products = $catalog->refs(array_map(static fn (Sale $sale): int => $sale->product_id, $sales));

                foreach ($sales as $sale) {
                    $sale->setRelation('product', $products[$sale->product_id]);
                }

                return SaleResource::collection($sales)->resolve();
            },
            'alerts' => fn (): array => $this->alerts($catalog->alerts(), $request),
        ]);
    }

    /**
     * `TopProduct[]`.
     *
     * @param  list<ProductSales>  $ranking
     * @return list<array<string, mixed>>
     */
    private function rankingRows(array $ranking, ProductCatalog $catalog): array
    {
        $products = $catalog->refs(array_map(static fn (ProductSales $ranked): int => $ranked->productId, $ranking));

        return array_map(fn (ProductSales $ranked): array => [
            'product' => $this->productRef($products[$ranked->productId]),
            'units_sold' => $ranked->unitsSold,
            'revenue' => $ranked->revenue,
            'percent_of_top' => $ranked->percentOfTop,
        ], $ranking);
    }

    /**
     * `UnproducibleAlert[]`: the product and the material that limits it, with the stock status of that material.
     *
     * @param  list<array{product: Product, bottleneck: Material}>  $alerts
     * @return list<array<string, mixed>>
     */
    private function alerts(array $alerts, Request $request): array
    {
        return array_map(fn (array $alert): array => [
            'product' => $this->productRef($alert['product']),
            'bottleneck' => [
                ...(new MaterialRefResource($alert['bottleneck']))->resolve($request),
                'stock_status' => $alert['bottleneck']->stockStatus()->value,
            ],
        ], $alerts);
    }

    /**
     * `ProductRef`. The product must carry `photo_path` (a narrow select includes it).
     *
     * @return array{id: int, sku: string, name: string, photo_url: string|null}
     */
    private function productRef(Product $product): array
    {
        return ['id' => $product->id, 'sku' => $product->sku, 'name' => $product->name, 'photo_url' => $product->photo_url];
    }

    /**
     * Materials to reorder: out of stock first, then low, each by name (a real query).
     *
     * @param  ReferenceData  $reference  its units and categories (read once per request, shared with the `reference` prop) are set on
     *                                    the materials instead of an eager load of their own
     * @return list<Material>
     */
    private function needsRestock(ReferenceData $reference): array
    {
        $materials = array_values(Material::query()
            ->without(['unit', 'category'])
            ->withUsedInCount()
            ->needsRestock()
            ->orderByRaw('CASE WHEN quantity_on_hand <= 0 THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->orderBy('id')
            ->limit(self::LIST_SIZE)
            ->get()
            ->all());

        $units = $reference->units();
        $categories = $reference->materialCategories();

        foreach ($materials as $material) {
            $material->setRelation('unit', $units->get($material->unit_id));
            $material->setRelation('category', $categories->get($material->category_id));
        }

        return $materials;
    }
}
