<?php

declare(strict_types=1);

namespace App\Actions\Reference;

use App\Models\Unit;
use App\Services\Reference\ReferenceVersion;

/**
 * Hides the item from pickers and filters; records that already use it keep showing it. Allowed any time, and a no-op on an archived row.
 */
final readonly class ArchiveUnit
{
    public function __construct(private ReferenceVersion $version) {}

    public function handle(Unit $item): Unit
    {
        // Archiving an archived row changes nothing: no new timestamp, no version bump (F-27).
        if ($item->archived_at !== null) {
            return $item;
        }

        $item->update(['archived_at' => now()]);

        $this->version->bump();

        return $item;
    }
}
