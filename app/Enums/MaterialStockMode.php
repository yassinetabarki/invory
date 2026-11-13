<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How a material keeps its stock (INV-21, ADR-0029 decision 1): one quantity for all colours (every material before INV-21), or a
 * quantity per colour of Settings › Colours (`material_colours`).
 */
enum MaterialStockMode: string
{
    case Single = 'single';
    case PerColour = 'per_colour';

    /** The words of the material form's switch. */
    public function label(): string
    {
        return match ($this) {
            self::Single => __('One stock for all colours'),
            self::PerColour => __('Stock per colour'),
        };
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
