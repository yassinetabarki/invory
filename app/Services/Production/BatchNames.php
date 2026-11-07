<?php

declare(strict_types=1);

namespace App\Services\Production;

use App\Models\ProductionBatch;
use App\Services\Products\ProductSizes;
use App\Services\Restocks\RestockMessages;

/** How the production messages name a batch's variant (INV-5): "Indigo M", "Black, size 38", "Camel" (a "One size" product). */
final readonly class BatchNames
{
    public function __construct(private ProductSizes $productSizes) {}

    public function variant(ProductionBatch $batch): string
    {
        return RestockMessages::variant($batch->colour->name, $batch->size->label, $this->isOneSize($batch));
    }

    /** A ticket's and the scan confirm's "Indigo · S"; "Camel" for a "One size" product (INV-15). */
    public function dotted(ProductionBatch $batch): string
    {
        return $this->isOneSize($batch) ? $batch->colour->name : "{$batch->colour->name} · {$batch->size->label}";
    }

    /**
     * Reads the sizes of the batches' products in one query, so a page or a sheet of many batches asks nothing per batch.
     *
     * @param  iterable<ProductionBatch>  $batches  with their `product` loaded
     */
    public function preload(iterable $batches): void
    {
        $products = [];

        foreach ($batches as $batch) {
            $products[] = $batch->product;
        }

        $this->productSizes->forProducts($products);
    }

    public function isOneSize(ProductionBatch $batch): bool
    {
        return $this->productSizes->of($batch->product)->oneSize;
    }

    /** "Denim Jacket · Indigo M". */
    public function what(ProductionBatch $batch): string
    {
        return ProductionMessages::what($batch, $this->variant($batch));
    }
}
