<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\SalesPeriod;
use App\Http\Requests\DashboardRequest;
use App\Http\Resources\MaterialResource;
use App\Mock\PreviewMaterials;
use App\Mock\PreviewProducts;
use App\Mock\PreviewSales;
use App\Models\Material;
use App\Models\Unit;
use App\Services\Inventory\InventorySummary;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * MOCK data layer (ADR-0006, removed by S-17): the sales come from App\Mock and the products are the real ones, costed by
 * ProductCostingService; inventory totals and the restock list are real queries on materials. Sends the DashboardProps contract.
 *
 * @phpstan-import-type DatedSale from PreviewSales
 */
final class DashboardController extends Controller
{
    /** Rows of the top-products rankings and of the "needs restock" and "recent sales" lists. */
    private const LIST_SIZE = 5;

    public function __invoke(DashboardRequest $request, InventorySummary $inventory): Response
    {
        Gate::authorize('access-preview');

        $period = $request->period();

        // Every section is a closure, so a partial reload (the period switch) skips the ones it does not list. The sales, the costed
        // products and the units are shared by several sections, so a full visit builds each of them once (the units are read
        // once for both the compositions and the restock list: that keeps the page within its query budget).
        /** @var Collection<int, Unit>|null $unitMap */
        $unitMap = null;
        $units = function () use (&$unitMap): Collection {
            return $unitMap ??= Unit::query()->get()->keyBy('id');
        };
        $catalog = null;
        $products = function () use (&$catalog, $units): PreviewProducts {
            return $catalog ??= PreviewProducts::load($units());
        };
        /** @var list<DatedSale>|null $everySale */
        $everySale = null;
        // The fixture sales of the real products: resolved from the catalog when the page has already loaded it (no query), else by SKU.
        $allSales = function () use (&$everySale, &$catalog): array {
            return $everySale ??= $catalog !== null ? PreviewSales::resolve($catalog->products()) : PreviewSales::all();
        };
        $sales = fn (): array => PreviewSales::inPeriod($allSales(), $period);

        return Inertia::render('dashboard', [
            'period' => $period->value,
            'periods' => SalesPeriod::options(),
            'stats' => function () use ($products, $sales, $inventory): array {
                // First, so that the sales below are resolved from the products it loads.
                $catalog = $products();

                return [
                    ...PreviewSales::summary($sales()),
                    ...$inventory->summarize(),
                    'unproducible_count' => $catalog->unproducibleCount(),
                    'products_count' => $catalog->products()->count(),
                ];
            },
            'top_products' => fn (): array => [
                'by_units' => PreviewSales::topProducts($sales(), 'units'),
                'by_revenue' => PreviewSales::topProducts($sales(), 'revenue'),
            ],
            'needs_restock' => fn (): array => MaterialResource::collection($this->needsRestock($units()))->resolve(),
            // The newest sales, whatever the period.
            'recent_sales' => fn (): array => PreviewSales::recent($allSales(), self::LIST_SIZE),
            'alerts' => fn (): array => $products()->alerts(),
        ]);
    }

    /**
     * Materials to reorder: out of stock first, then low, each by name (a real query).
     *
     * @param  Collection<int, Unit>  $units  every unit by id, so the materials don't load theirs again
     * @return list<Material>
     */
    private function needsRestock(Collection $units): array
    {
        $materials = array_values(Material::query()
            ->without('unit')
            ->withUsedInCount()
            ->needsRestock()
            ->orderByRaw('CASE WHEN quantity_on_hand <= 0 THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->orderBy('id')
            ->limit(self::LIST_SIZE)
            ->get()
            ->all());

        foreach ($materials as $material) {
            $material->setRelation('unit', $units->get($material->unit_id));
        }

        PreviewMaterials::decorate($materials);

        return $materials;
    }
}
