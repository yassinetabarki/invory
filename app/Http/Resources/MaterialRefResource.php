<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `MaterialRef` in resources/js/types/domain.ts: enough to name a material and its unit. Needs the `unit` relation, which a
 * material always carries (see `Material::$with`).
 *
 * @mixin Material
 */
final class MaterialRefResource extends JsonResource
{
    /** @return array{id: int, sku: string, name: string, unit: string} */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'unit' => $this->unit->code,
        ];
    }
}
