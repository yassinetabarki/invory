<?php

declare(strict_types=1);

namespace App\Actions\Colours;

use App\Models\Colour;
use App\Services\Colours\ColourClash;
use Illuminate\Validation\ValidationException;

final readonly class UpdateColour
{
    /**
     * A name taken a moment ago by someone else (a lost race, F-33) comes back as a validation error on `name`.
     *
     * @param  array<string, mixed>  $data  validated input: `name`, `swatch` and optional `position` (the code never changes)
     *
     * @throws ValidationException
     */
    public function handle(Colour $colour, array $data): Colour
    {
        ColourClash::saving(fn (): bool => $colour->update([
            'name' => $data['name'],
            'swatch' => $data['swatch'],
            'position' => isset($data['position']) ? (int) $data['position'] : $colour->position,
        ]), $data['name'], ignore: $colour);

        return $colour;
    }
}
