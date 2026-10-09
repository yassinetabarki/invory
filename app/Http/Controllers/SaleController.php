<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\SalesPeriod;
use App\Http\Requests\Sales\IndexSaleRequest;
use App\Http\Requests\Sales\StoreSaleRequest;
use App\Http\Requests\Sales\UpdateSaleRequest;
use App\Mock\PreviewFixture;
use App\Mock\PreviewPage;
use App\Mock\PreviewProducts;
use App\Mock\PreviewSales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * MOCK data layer (ADR-0006, removed by S-17): sales come from App\Mock (resolved against the real products) and every total from
 * SalesCalculator.
 * Writes are validated by their final FormRequests and persist nothing. {sale} is a plain integer id until S-14-BE adds
 * model binding; an unknown id is a 404.
 *
 * @phpstan-import-type DatedSale from PreviewSales
 */
final class SaleController extends Controller
{
    public function index(IndexSaleRequest $request): Response
    {
        Gate::authorize('access-preview');

        $filters = $request->filters();

        // Every prop that costs a computation is a closure, so a partial reload skips what it does not list. `sales` and
        // `totals` share the filtered set.
        /** @var list<DatedSale>|null $filtered */
        $filtered = null;
        $sales = function () use (&$filtered, $filters, $request): array {
            return $filtered ??= PreviewSales::filter(PreviewSales::all(), $filters['search'], $filters['product_id'], $request->period());
        };

        return Inertia::render('sales/index', [
            'sales' => fn (): AnonymousResourceCollection => PreviewPage::paginate(PreviewSales::shapeAll($sales()), $request, route('sales.index')),
            'filters' => $filters,
            // For the whole filtered set, not just this page.
            'totals' => fn (): array => PreviewSales::totals($sales()),
            'periods' => SalesPeriod::options(),
            'products' => fn (): array => PreviewProducts::saleOptions(),
            // Non-null opens "Record a sale" on load; the product is pre-selected only if it can be sold.
            'record' => fn (): ?array => $request->record() ? ['product_id' => PreviewProducts::sellableId($request->recordProductId())] : null,
        ]);
    }

    public function store(StoreSaleRequest $request): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'info', 'message' => __("Preview: the sale passed validation but wasn't saved.")]);

        return back();
    }

    public function update(UpdateSaleRequest $request, int $sale): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'info', 'message' => __("Preview: the sale passed validation but wasn't saved.")]);

        return back();
    }

    public function destroy(int $sale): RedirectResponse
    {
        Gate::authorize('access-preview');
        abort_if(PreviewFixture::sale($sale) === null, 404);

        Inertia::flash('toast', ['type' => 'info', 'message' => __('Preview: nothing was deleted.')]);

        return back();
    }
}
