<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\SalesPeriod;
use App\Http\Requests\DashboardRequest;
use App\Http\Resources\MaterialResource;
use App\Mock\PreviewFixture;
use App\Mock\PreviewMaterials;
use App\Mock\PreviewProducts;
use App\Mock\PreviewSales;
use App\Models\Material;
use App\Services\Inventory\InventorySummary;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * MOCK data layer (ADR-0006, removed by S-17): sales and products come from App\Mock and are costed by the production
 * domain code; inventory totals and the restock list are real queries on materials. Sends the DashboardProps contract.
 */
final class DashboardController extends Controller
{
    /** Rows of the top-products rankings and of the "needs restock" and "recent sales" lists. */
    private const LIST_SIZE = 5;

    public function __invoke(DashboardRequest $request, InventorySummary $inventory): Response
    {
        Gate::authorize('access-preview');

        $period = $request->period();

        // Every section is a closure, so a partial reload (the period switch) skips the ones it does not list. The period's
        // sales and the costed products are shared by several sections, so a full visit builds each of them once.
        /** @var list<array{id: int, date: string, product_id: int, quantity: int, unit_price: string, note: string|null}>|null $periodSales */
        $periodSales = null;
        $sales = function () use (&$periodSales, $period): array {
            return $periodSales ??= PreviewSales::inPeriod(PreviewSales::all(), $period);
        };
        $catalog = null;
        $products = function () use (&$catalog): PreviewProducts {
            return $catalog ??= PreviewProducts::load();
        };

        return Inertia::render('dashboard', [
            'period' => $period->value,
            'periods' => SalesPeriod::options(),
            'stats' => fn (): array => [
                ...PreviewSales::summary($sales()),
                ...$inventory->summarize(),
                'unproducible_count' => $products()->unproducibleCount(),
                'products_count' => count(PreviewFixture::products()),
            ],
            'top_products' => fn (): array => [
                'by_units' => PreviewSales::topProducts($sales(), 'units'),
                'by_revenue' => PreviewSales::topProducts($sales(), 'revenue'),
            ],
            'needs_restock' => fn (): array => MaterialResource::collection($this->needsRestock())->resolve(),
            // The newest sales, whatever the period.
            'recent_sales' => fn (): array => PreviewSales::recent(PreviewSales::all(), self::LIST_SIZE),
            'alerts' => fn (): array => $products()->alerts(),
        ]);
    }

    /**
     * Materials to reorder: out of stock first, then low, each by name (a real query).
     *
     * @return list<Material>
     */
    private function needsRestock(): array
    {
        $materials = array_values(Material::query()
            ->needsRestock()
            ->orderByRaw('CASE WHEN quantity_on_hand <= 0 THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->orderBy('id')
            ->limit(self::LIST_SIZE)
            ->get()
            ->all());

        PreviewMaterials::decorate($materials);

        return $materials;
    }
}
