<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The unit cost of a material that keeps its stock per colour (INV-21, ADR-0029 decision 1): one cost for every colour, or a cost per
 * colour. A one-stock material is always `same`.
 */
enum MaterialCostMode: string
{
    case Same = 'same';
    case PerColour = 'per_colour';

    /** The words of the material form's switch. */
    public function label(): string
    {
        return match ($this) {
            self::Same => __('Same cost for every colour'),
            self::PerColour => __('Cost per colour'),
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
