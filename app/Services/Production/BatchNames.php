<?php

declare(strict_types=1);

namespace App\Services\Production;

use App\Models\ProductionBatch;
use App\Models\SizeList;
use App\Services\Products\SizeLists;
use App\Services\Restocks\RestockMessages;

/** How the production messages name a batch's variant (INV-5): "Indigo M", "Black, size 38", "Camel" (a "One size" product). */
final readonly class BatchNames
{
    public function __construct(private SizeLists $sizeLists) {}

    public function variant(ProductionBatch $batch): string
    {
        $sizeList = $this->sizeLists->category($batch->product->category_id)->sizeList;

        return RestockMessages::variant($batch->colour->name, $batch->size->label, $sizeList->code === SizeList::ONE_SIZE);
    }

    /** A ticket's and the scan confirm's "Indigo · S"; "Camel" for a "One size" product (INV-15). */
    public function dotted(ProductionBatch $batch): string
    {
        return $this->isOneSize($batch) ? $batch->colour->name : "{$batch->colour->name} · {$batch->size->label}";
    }

    public function isOneSize(ProductionBatch $batch): bool
    {
        return $this->sizeLists->category($batch->product->category_id)->sizeList->code === SizeList::ONE_SIZE;
    }

    /** "Denim Jacket · Indigo M". */
    public function what(ProductionBatch $batch): string
    {
        return ProductionMessages::what($batch, $this->variant($batch));
    }
}
