<?php

declare(strict_types=1);

namespace App\Actions\Reference;

use App\Models\Unit;
use App\Services\Reference\ReferenceVersion;

/**
 * Puts an archived item back in the pickers. A no-op on an active row: no write, no new reference version (F-30).
 */
final readonly class RestoreUnit
{
    public function __construct(private ReferenceVersion $version) {}

    public function handle(Unit $item): Unit
    {
        if ($item->archived_at === null) {
            return $item;
        }

        $item->update(['archived_at' => null]);

        $this->version->bump();

        return $item;
    }
}
