<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Sales\DeleteSale;
use App\Actions\Sales\RecordSale;
use App\Actions\Sales\UpdateSale;
use App\Enums\SalesPeriod;
use App\Http\Requests\Sales\IndexSaleRequest;
use App\Http\Requests\Sales\StoreSaleRequest;
use App\Http\Requests\Sales\UpdateSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\Sales\SalesReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Sales are real since S-14-BE: Eloquent reads through {@see SalesReport} (the log, the totals over the whole filtered set, the
 * product options), the sale Actions for writes, `SalePolicy` on every route, and `{sale}` route-model bound.
 */
final class SaleController extends Controller
{
    /** Query parameters that only drive what the page opens with: they are not carried into the pagination links. */
    private const TRANSIENT_QUERY = ['record', 'record_product_id'];

    public function index(IndexSaleRequest $request, SalesReport $report): Response
    {
        Gate::authorize('viewAny', Sale::class);

        $filters = $request->filters();
        $period = $request->period();

        // Every prop that costs a query is a closure, so a partial reload skips what it does not list.
        return Inertia::render('sales/index', [
            'sales' => function () use ($report, $filters, $period, $request): AnonymousResourceCollection {
                $page = $report->filtered($filters, $period)
                    ->newestFirst()
                    ->with('product:id,sku,name,photo_path')
                    ->paginate((int) config('invory.per_page'))
                    ->appends(Arr::except($request->query(), self::TRANSIENT_QUERY));

                return SaleResource::collection($page);
            },
            'filters' => $filters,
            // For the whole filtered set, not just this page.
            'totals' => fn (): array => $report->totals($report->filtered($filters, $period)),
            'periods' => SalesPeriod::options(),
            'products' => function () use ($report): array {
                // `SaleProductOption[]`.
                return $report->productOptions()->map(static fn (Product $product): array => [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'photo_url' => $product->photo_url,
                    'status' => $product->status->value,
                    'selling_price' => $product->selling_price,
                ])->all();
            },
            // Non-null opens "Record a sale" on load; the product is pre-selected only if it can be sold.
            'record' => fn (): ?array => $request->record() ? ['product_id' => $report->sellableProductId($request->recordProductId())] : null,
        ]);
    }

    public function store(StoreSaleRequest $request, RecordSale $recordSale): RedirectResponse
    {
        $recordSale->handle($request->validated(), $this->actor($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sale recorded.')]);

        return back();
    }

    public function update(UpdateSaleRequest $request, Sale $sale, UpdateSale $updateSale): RedirectResponse
    {
        $updateSale->handle($sale, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sale updated.')]);

        return back();
    }

    public function destroy(Sale $sale, DeleteSale $deleteSale): RedirectResponse
    {
        Gate::authorize('delete', $sale);

        $deleteSale->handle($sale);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sale deleted.')]);

        return back();
    }

    /** The signed-in user, for `sales.user_id`. The `auth` middleware and the request authorization guarantee one. */
    private function actor(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : abort(401);
    }
}
