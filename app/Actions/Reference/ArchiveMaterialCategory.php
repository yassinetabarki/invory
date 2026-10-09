<?php

declare(strict_types=1);

namespace App\Actions\Reference;

use App\Models\MaterialCategory;
use App\Services\Reference\ReferenceVersion;

/**
 * Hides the item from pickers and filters; records that already use it keep showing it. Allowed any time.
 */
final readonly class ArchiveMaterialCategory
{
    public function __construct(private ReferenceVersion $version) {}

    public function handle(MaterialCategory $item): MaterialCategory
    {
        $item->update(['archived_at' => now()]);

        $this->version->bump();

        return $item;
    }
}
