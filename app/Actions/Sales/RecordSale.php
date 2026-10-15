<?php

declare(strict_types=1);

namespace App\Actions\Sales;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class RecordSale
{
    /**
     * Records a sale. Sales are stock-neutral (decision Q8): no material and no stock movement is touched.
     *
     * @param  array<string, mixed>  $data  validated input: `product_id`, `quantity`, `unit_price`, `sold_on` (Y-m-d), optional `note`
     */
    public function handle(array $data, User $actor): Sale
    {
        return DB::transaction(function () use ($data, $actor): Sale {
            $sale = new Sale($data);

            // Never mass-assignable: the workshop comes from the product (null when it doesn't exist: the foreign key then refuses the sale),
            // and who recorded it from the actor, not from the request.
            $sale->workshop_id = Product::query()->whereKey($sale->product_id)->value('workshop_id');
            $sale->user_id = $actor->id;
            $sale->save();

            return $sale;
        });
    }
}
