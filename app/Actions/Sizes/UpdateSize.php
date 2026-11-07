<?php

declare(strict_types=1);

namespace App\Actions\Sizes;

use App\Models\Size;
use App\Services\Sizes\SizeClash;
use Illuminate\Validation\ValidationException;

final readonly class UpdateSize
{
    /**
     * Renames and moves a size, nothing else: variant SKUs are built on read and barcodes point at the id. `is_one_size` is never
     * touched, so renaming One size keeps its flag. A name taken a moment ago comes back as a validation error on `label`.
     *
     * @param  array<string, mixed>  $data  validated input: `label` and optional `position` (empty lists the size last, as Add size does)
     *
     * @throws ValidationException
     */
    public function handle(Size $size, array $data): Size
    {
        SizeClash::saving(fn (): bool => $size->update([
            'label' => $data['label'],
            'position' => isset($data['position']) ? (int) $data['position'] : $this->nextPosition(),
        ]), $data['label'], $size);

        return $size;
    }

    /** After the highest position of the workshop (the size's own included), never past the column's 32,767. */
    private function nextPosition(): int
    {
        return min(((int) Size::query()->max('position')) + 1, 32767);
    }
}
