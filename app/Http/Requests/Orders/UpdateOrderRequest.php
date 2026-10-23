<?php

declare(strict_types=1);

namespace App\Http\Requests\Orders;

use App\Actions\Orders\SyncOrderLines;
use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

/**
 * Edit an order (S-48-c): the rules of {@see StoreOrderRequest}, and a product already on the order stays valid even once it is no
 * longer active or was deleted, and a line keeps the way it counts (S-60): a line recorded before sizes keeps its single quantity, also
 * when its product is removed and added back in the same visit. A line that has started (S-49) keeps its product and, per size, at
 * least its started units.
 */
class UpdateOrderRequest extends StoreOrderRequest
{
    /** @var Collection<int, OrderLine>|null The order's saved lines by product id, with their product and sizes, read once per request. */
    private ?Collection $currentLines = null;

    /**
     * `{order}` is route-model bound (an unknown or other workshop's order is a 404 before anything is validated), then the policy decides.
     */
    public function authorize(): Response
    {
        $order = $this->route('order');

        // The Gate's own response, so a policy's `denyAsNotFound()` stays a 404 (ADR-0013 decision 5).
        return $order instanceof Order ? Gate::inspect('update', $order) : Response::denyAsNotFound();
    }

    /**
     * The units of every line, then the started-line rules once the lines themselves are valid. This runs on unlocked data, to answer early; `UpdateOrder` repeats
     * it on the counts read under the order lock, which is the check that counts.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [...parent::after(), function (Validator $validator): void {
            $lines = $this->input('lines');

            if ($validator->errors()->isNotEmpty() || ! is_array($lines)) {
                return;
            }

            foreach (app(SyncOrderLines::class)->refusals($this->currentLines(), $lines) as $key => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add($key, $message);
                }
            }
        }];
    }

    protected function savedLine(int $productId): ?OrderLine
    {
        return $this->currentLines()->get($productId);
    }

    /** @return array<int, int> */
    protected function currentProductIds(): array
    {
        return $this->currentLines()->keys()->map(intval(...))->all();
    }

    /** @return Collection<int, OrderLine> */
    private function currentLines(): Collection
    {
        $order = $this->route('order');

        return $this->currentLines ??= $order instanceof Order
            ? OrderLine::query()->where('order_id', $order->id)->with(['product', 'sizes'])->get()->keyBy('product_id')
            : new Collection;
    }
}
