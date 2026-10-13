<?php

declare(strict_types=1);

namespace App\Actions\Sales;

use App\Models\Sale;

final readonly class DeleteSale
{
    /**
     * Hard delete: a sale has no history of its own, and deleting it puts nothing back in stock (decision Q8).
     */
    public function handle(Sale $sale): void
    {
        $sale->delete();
    }
}
