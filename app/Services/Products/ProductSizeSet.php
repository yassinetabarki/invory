<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Models\Size;
use Illuminate\Support\Collection;

/**
 * The sizes a product comes in (INV-18, ADR-0028), in list order (position, then id), and whether it is "One size". What
 * {@see ProductSizes} hands every reader; nothing else says which sizes a product has.
 */
final readonly class ProductSizeSet
{
    /** @param Collection<int, Size> $sizes in list order */
    public function __construct(public Collection $sizes, public bool $oneSize) {}

    /** @return list<int> */
    public function ids(): array
    {
        return array_values($this->sizes->map(fn (Size $size): int => $size->id)->all());
    }

    public function has(int $sizeId): bool
    {
        return $this->sizes->contains('id', $sizeId);
    }
}
