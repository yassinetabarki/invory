<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Barcodes\Ean13;
use App\Models\Barcode;
use App\Models\Colour;
use App\Models\Size;
use Database\Factories\Concerns\DefaultWorkshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Valid by default: a barcode of the current workshop for its first colour (list order) and the first size, with a valid EAN-13 number
 * of its own. The default number starts with 590, outside the in-store range (it starts with 2), so it never meets a number the
 * workshop's counter gives; a test about the in-store numbers passes `number` ({@see Ean13::inStore()}). It stands for no name yet:
 * use `BarcodeName::factory()` for that.
 *
 * @extends Factory<Barcode>
 */
class BarcodeFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $digits = fake()->unique()->numerify('590#########');

        return [
            'workshop_id' => static fn (): int => DefaultWorkshop::id(),
            'number' => $digits.Ean13::checkDigit($digits),
            // After `workshop_id`: a closure sees the attributes resolved before it.
            'colour_id' => static fn (array $attributes): mixed => Colour::query()->where('workshop_id', $attributes['workshop_id'])->ordered()->value('id'),
            'size_id' => static fn (): mixed => Size::query()->orderBy('id')->value('id'),
        ];
    }
}
