<?php

declare(strict_types=1);

namespace App\Actions\Sizes;

use App\Models\Size;
use App\Services\Sizes\SizeClash;
use Illuminate\Validation\ValidationException;

final readonly class CreateSize
{
    /**
     * A name taken a moment ago by someone else (a lost race) comes back as a validation error on `label`, with the very sentence
     * the request rule gives. A new size is never One size and is on no product.
     *
     * @param  array<string, mixed>  $data  validated input: `label`, optional `position` (empty = last)
     *
     * @throws ValidationException
     */
    public function handle(array $data): Size
    {
        return SizeClash::saving(fn (): Size => Size::query()->create([
            'label' => $data['label'],
            'position' => isset($data['position']) ? (int) $data['position'] : $this->nextPosition(),
        ]), $data['label']);
    }

    /** After the last size of the workshop, never past the column's 32,767. */
    private function nextPosition(): int
    {
        return min(((int) Size::query()->max('position')) + 1, 32767);
    }
}
