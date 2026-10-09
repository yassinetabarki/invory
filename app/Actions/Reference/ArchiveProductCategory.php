<?php

declare(strict_types=1);

namespace App\Actions\Reference;

use App\Models\ProductCategory;
use App\Services\Reference\ReferenceVersion;

/**
 * Hides the item from pickers and filters; records that already use it keep showing it. Allowed any time.
 */
final readonly class ArchiveProductCategory
{
    public function __construct(private ReferenceVersion $version) {}

    public function handle(ProductCategory $item): ProductCategory
    {
        $item->update(['archived_at' => now()]);

        $this->version->bump();

        return $item;
    }
}
