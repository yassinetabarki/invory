<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Size;
use Database\Factories\Concerns\DefaultWorkshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Valid by default: a size at the end of the current workshop's list, with a name no other size of the factory has (every factory
 * workshop already holds the 19 defaults, whose names are letters and 36 … 46, so "Size 1" … never clash with them). Override only what
 * a test is about.
 *
 * @extends Factory<Size>
 */
class SizeFactory extends Factory
{
    private static int $sequence = 0;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $number = ++self::$sequence;

        return [
            'workshop_id' => static fn (): int => DefaultWorkshop::id(),
            'label' => "Size {$number}",
            'position' => 1000 + $number % 1000,
            'is_one_size' => false,
        ];
    }
}
