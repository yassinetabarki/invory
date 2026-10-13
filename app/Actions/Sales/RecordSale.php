<?php

declare(strict_types=1);

namespace App\Actions\Sales;

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

            // Never mass-assignable: who recorded it comes from the actor, not from the request.
            $sale->user_id = $actor->id;
            $sale->save();

            return $sale;
        });
    }
}
