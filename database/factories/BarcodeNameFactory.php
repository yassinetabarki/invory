<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Barcodes\NameKey;
use App\Models\Barcode;
use App\Models\BarcodeName;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Valid by default: a new name of its own standing for a new {@see Barcode}, in that barcode's workshop, colour and size (pass
 * `barcode_id`, or `->for($barcode)`, to name an existing one: it then takes that barcode's workshop, colour and size). Override
 * `name_key` to say which name; the key must already be a {@see NameKey::of()} result.
 *
 * @extends Factory<BarcodeName>
 */
class BarcodeNameFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'barcode_id' => Barcode::factory(),
            // After `barcode_id`: a closure sees the attributes resolved before it.
            'workshop_id' => static fn (array $attributes): mixed => Barcode::query()->whereKey($attributes['barcode_id'])->value('workshop_id'),
            'name_key' => NameKey::of(fake()->unique()->lexify('?????? ??????')),
            'colour_id' => static fn (array $attributes): mixed => Barcode::query()->whereKey($attributes['barcode_id'])->value('colour_id'),
            'size_id' => static fn (array $attributes): mixed => Barcode::query()->whereKey($attributes['barcode_id'])->value('size_id'),
        ];
    }
}
