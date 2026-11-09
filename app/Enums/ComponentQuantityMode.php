<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How a composition line gets its quantity (INV-16, ADR-0027 decision 1): one quantity for every colour and size (every line before
 * INV-16), or a quantity per colour and size (`product_component_quantities`), where a variant with no cell doesn't use the material.
 */
enum ComponentQuantityMode: string
{
    case Same = 'same';
    case ByVariant = 'by_variant';

    /** The words of the composition editor's switch. */
    public function label(): string
    {
        return match ($this) {
            self::Same => 'Same for every variant',
            self::ByVariant => 'By colour and size',
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
