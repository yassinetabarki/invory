<?php

declare(strict_types=1);

namespace App\Actions\Colours;

use App\Models\Colour;
use App\Services\Colours\ColourClash;
use Illuminate\Validation\ValidationException;

final readonly class CreateColour
{
    /**
     * A name or code taken a moment ago by someone else (a lost race, F-33) comes back as a validation error on `name` or `code`.
     *
     * @param  array<string, mixed>  $data  validated input: `code`, `name`, `swatch`, optional `position` (empty = last)
     *
     * @throws ValidationException
     */
    public function handle(array $data): Colour
    {
        return ColourClash::saving(fn (): Colour => Colour::query()->create([
            'code' => $data['code'],
            'name' => $data['name'],
            'swatch' => $data['swatch'],
            'position' => isset($data['position']) ? (int) $data['position'] : $this->nextPosition(),
        ]), $data['name'], $data['code']);
    }

    /** After the last colour of the workshop, never past the column's 32,767. */
    private function nextPosition(): int
    {
        return min(((int) Colour::query()->max('position')) + 1, 32767);
    }
}
