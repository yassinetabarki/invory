<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use Database\Factories\Concerns\DefaultWorkshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Valid by default: a client of the current workshop in Tunis, with no source and a phone no other factory client has ("20 000 001",
 * "20 000 002"…; the example clients of the cards use other numbers). Override only what a test is about.
 *
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    private static int $sequence = 0;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $number = ++self::$sequence;

        return [
            'workshop_id' => static fn (): int => DefaultWorkshop::id(),
            'name' => fake()->name(),
            'phone' => (string) (20000000 + $number % 10000000),
            'city' => 'Tunis',
            'source' => null,
            'source_other' => null,
        ];
    }
}
