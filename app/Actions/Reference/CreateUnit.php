<?php

declare(strict_types=1);

namespace App\Actions\Reference;

use App\Enums\UnitDimension;
use App\Models\Unit;
use App\Services\Reference\ReferenceVersion;

final readonly class CreateUnit
{
    public function __construct(private ReferenceVersion $version) {}

    /**
     * A user-made unit is never a system unit. A `package` unit has no factor.
     *
     * @param  array<string, mixed>  $data  validated input: `code`, `name`, `dimension`, `factor_to_base`, optional `position` (empty = last)
     */
    public function handle(array $data): Unit
    {
        $dimension = UnitDimension::from($data['dimension']);

        $unit = Unit::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'dimension' => $dimension,
            'factor_to_base' => $dimension->hasFactor() ? $data['factor_to_base'] : null,
            'is_system' => false,
            'position' => isset($data['position']) ? (int) $data['position'] : $this->nextPosition(),
        ]);

        $this->version->bump();

        return $unit;
    }

    private function nextPosition(): int
    {
        return min(((int) Unit::query()->max('position')) + 1, 32767);
    }
}
