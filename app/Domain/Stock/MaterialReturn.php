<?php

declare(strict_types=1);

namespace App\Domain\Stock;

/** What a move back does to one material, over every cut it touches. */
final readonly class MaterialReturn
{
    public function __construct(
        public int $materialId,
        public string $taken,         // what those units took, scale 4
        public string $returned,      // the same, over the returnable batch-materials only; "0.0000" if none
        public ?string $notReturned,  // 'deleted' | 'unit_changed' when no part is returnable (deleted wins); else null
        public ?string $inStock,      // scale 4; null when not returnable at all
        public ?string $after,        // inStock + returned, scale 4; null when notReturned
    ) {}
}
