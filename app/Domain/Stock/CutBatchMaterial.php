<?php

declare(strict_types=1);

namespace App\Domain\Stock;

/** One remembered material of a past cut (a row of order_line_cut_materials). */
final readonly class CutBatchMaterial
{
    public const DELETED = 'deleted';

    public const UNIT_CHANGED = 'unit_changed';

    /** @param ?string $notReturned null, 'deleted' or 'unit_changed': decided by the caller from today's material */
    public function __construct(
        public int $materialId,
        public string $perUnitExact,  // decimal(20,8) as stored
        public string $taken,         // decimal(14,4) as stored
        public ?string $notReturned,
    ) {}
}
