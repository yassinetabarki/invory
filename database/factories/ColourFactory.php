<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Colour;
use Database\Factories\Concerns\DefaultWorkshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Valid by default: a colour at the end of the current workshop's list, with a code and a name no other colour of the factory has
 * (every factory workshop already holds the 20 defaults, whose codes are three letters, so `C01`… and "Colour 1"… never clash with them).
 * Override only what a test is about.
 *
 * @extends Factory<Colour>
 */
class ColourFactory extends Factory
{
    private static int $sequence = 0;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $number = ++self::$sequence;

        return [
            'workshop_id' => static fn (): int => DefaultWorkshop::id(),
            'code' => 'C'.str_pad((string) ($number % 1000), 3, '0', STR_PAD_LEFT),
            'name' => "Colour {$number}",
            'swatch' => '#AABBCC',
            'position' => 1000 + $number % 1000,
        ];
    }
}
