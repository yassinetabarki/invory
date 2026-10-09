<?php

declare(strict_types=1);

namespace App\Actions\Reference;

use App\Enums\UnitDimension;
use App\Models\Unit;
use App\Services\Reference\ReferenceNameGuard;
use App\Services\Reference\ReferenceVersion;
use Illuminate\Validation\ValidationException;

final readonly class UpdateUnit
{
    public function __construct(private ReferenceVersion $version) {}

    /**
     * The code never changes. `dimension` and `factor_to_base` are only present when the request is allowed to change them
     * (custom units that no material uses, see UpdateUnitRequest); a unit moved to `package` loses its factor.
     * A name taken a moment ago by someone else (a lost race, F-33) comes back as a validation error on `name`.
     *
     * @param  array<string, mixed>  $data  validated input
     *
     * @throws ValidationException
     */
    public function handle(Unit $unit, array $data): Unit
    {
        $changes = [
            'name' => $data['name'],
            'position' => isset($data['position']) ? (int) $data['position'] : $unit->position,
        ];

        if (isset($data['dimension'])) {
            $dimension = UnitDimension::from($data['dimension']);

            $changes['dimension'] = $dimension;
            $changes['factor_to_base'] = $dimension->hasFactor() ? ($data['factor_to_base'] ?? $unit->factor_to_base) : null;
        } elseif (isset($data['factor_to_base'])) {
            $changes['factor_to_base'] = $data['factor_to_base'];
        }

        ReferenceNameGuard::saving('units', fn (): bool => $unit->update($changes));

        $this->version->bump();

        return $unit;
    }
}
