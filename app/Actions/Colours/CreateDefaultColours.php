<?php

declare(strict_types=1);

namespace App\Actions\Colours;

use App\Models\Colour;
use App\Models\Workshop;

/**
 * Gives a new workshop the 20 default colours (INV-1, ADR-0018): sign-up and the demo seeder through `CreateWorkshop`, and every
 * test workshop through `WorkshopFactory`. Workshops that existed before INV-1 got the same list from the migration's own frozen
 * copy; a test keeps the two equal. Changing this list changes new workshops only.
 */
final readonly class CreateDefaultColours
{
    /** @var list<array{string, string, string}> name, code and swatch in list order (the position is the index + 1). */
    public const DEFAULTS = [
        ['White', 'WHT', '#FFFFFF'],
        ['Ecru', 'ECR', '#EFE6D2'],
        ['Beige', 'BEI', '#D8C3A0'],
        ['Camel', 'CAM', '#B98A57'],
        ['Brown', 'BRN', '#6B4A32'],
        ['Grey', 'GRY', '#8C8C8C'],
        ['Charcoal', 'CHA', '#3D3D42'],
        ['Black', 'BLK', '#1F1F23'],
        ['Navy', 'NAV', '#1F2A44'],
        ['Indigo', 'IND', '#3E4C7E'],
        ['Light wash', 'LWH', '#9DB3D1'],
        ['Lilac', 'LIL', '#B3A2D3'],
        ['Pink', 'PNK', '#E8A9B8'],
        ['Red', 'RED', '#B8282E'],
        ['Burgundy', 'BUR', '#6E1F2E'],
        ['Rust', 'RST', '#B5552B'],
        ['Mustard', 'MUS', '#D4A12A'],
        ['Khaki', 'KHA', '#A69F74'],
        ['Olive', 'OLV', '#5F6B3A'],
        ['Forest green', 'FGR', '#2F5A43'],
    ];

    /** One insert, with the workshop and the timestamps written out: a bulk insert skips the model's own events and defaults. */
    public function handle(Workshop $workshop): void
    {
        $now = now();

        Colour::query()->insert(array_map(
            static fn (array $colour, int $index): array => [
                'workshop_id' => $workshop->id,
                'code' => $colour[1],
                'name' => $colour[0],
                'swatch' => $colour[2],
                'position' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            self::DEFAULTS,
            array_keys(self::DEFAULTS),
        ));
    }
}
