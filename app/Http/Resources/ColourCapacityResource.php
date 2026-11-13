<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Costing\ColourCapacity;
use App\Models\Colour;
use App\Models\Material;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `ColourCapacity`: what one colour of a product can make now, smallest (guaranteed) and largest (best) over its sizes, and what limits the
 * guaranteed figure at which size. All four figures are null for a colour that uses no material ("No composition"). The colour, the
 * binding size and the bottleneck come from the product's resource, which has them loaded.
 *
 * @mixin ColourCapacity
 */
final class ColourCapacityResource extends JsonResource
{
    /**
     * @param  Size|null  $bindingSize  Null for a "One size" product and for a colour with no composition.
     * @param  Material|null  $bottleneck  Null for a colour with no composition.
     */
    public function __construct(
        ColourCapacity $capacity,
        private readonly Colour $colour,
        private readonly ?Size $bindingSize,
        private readonly ?Material $bottleneck,
    ) {
        parent::__construct($capacity);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'colour' => (new ColourResource($this->colour))->resolve($request),
            'guaranteed' => $this->guaranteed,
            'best' => $this->best,
            'bottleneck' => $this->bottleneck === null ? null : (new MaterialRefResource($this->bottleneck))->resolve($request),
            'binding_size' => $this->bindingSize === null ? null : ['id' => $this->bindingSize->id, 'label' => $this->bindingSize->label],
            // INV-21: this colour when the bottleneck keeps its stock per colour (the stock that limits it), and whether it lacks the colour.
            'bottleneck_colour' => $this->bottleneckColourId === null ? null : (new ColourResource($this->colour))->resolve($request),
            'bottleneck_missing' => $this->bottleneckMissing,
        ];
    }
}
