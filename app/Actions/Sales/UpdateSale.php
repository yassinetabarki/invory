<?php

declare(strict_types=1);

namespace App\Actions\Sales;

use App\Models\Sale;
use Illuminate\Support\Facades\DB;

final readonly class UpdateSale
{
    /**
     * Edits a sale. Stock-neutral (decision Q8), and the sale keeps the user who recorded it.
     *
     * @param  array<string, mixed>  $data  validated input: `product_id`, `quantity`, `unit_price`, `sold_on` (Y-m-d), optional `note`
     */
    public function handle(Sale $sale, array $data): Sale
    {
        return DB::transaction(function () use ($sale, $data): Sale {
            $sale->fill($data)->save();

            return $sale;
        });
    }
}
