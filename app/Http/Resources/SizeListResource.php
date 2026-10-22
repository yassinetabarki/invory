<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Size;
use App\Models\SizeList;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `SizeList` (S-58, ADR-0015): the fixed sizes of a product category. The list must carry its sizes (loaded in position order).
 *
 * @mixin SizeList
 */
final class SizeListResource extends JsonResource
{
    /** @return array{code: string, name: string, sizes: list<array{id: int, label: string}>} */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'sizes' => array_values($this->sizes
                ->map(fn (Size $size): array => ['id' => $size->id, 'label' => $size->label])
                ->all()),
        ];
    }
}
