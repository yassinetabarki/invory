<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Colour;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `Colour`: what a product page and the product form show of a colour (INV-1). Settings > Colours has its own rows.
 *
 * @mixin Colour
 */
final class ColourResource extends JsonResource
{
    /** @return array{id: int, code: string, name: string, swatch: string} */
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'code' => $this->code, 'name' => $this->name, 'swatch' => $this->swatch];
    }
}
