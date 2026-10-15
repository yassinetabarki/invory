<?php

declare(strict_types=1);

namespace App\Http\Requests\Orders;

use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Edit an order (S-48-c): the rules of {@see StoreOrderRequest}, and a product already on the order stays valid even once it is no
 * longer active or was deleted.
 */
class UpdateOrderRequest extends StoreOrderRequest
{
    /** @var array<int, int>|null */
    private ?array $currentProducts = null;

    /**
     * `{order}` is route-model bound (an unknown or other workshop's order is a 404 before anything is validated), then the policy decides.
     */
    public function authorize(): Response
    {
        $order = $this->route('order');

        // The Gate's own response, so a policy's `denyAsNotFound()` stays a 404 (ADR-0013 decision 5).
        return $order instanceof Order ? Gate::inspect('update', $order) : Response::denyAsNotFound();
    }

    /** @return array<int, int> */
    protected function currentProductIds(): array
    {
        $order = $this->route('order');

        if ($this->currentProducts === null) {
            $this->currentProducts = $order instanceof Order
                ? OrderLine::query()->where('order_id', $order->id)->pluck('product_id')->map(intval(...))->all()
                : [];
        }

        return $this->currentProducts;
    }
}
