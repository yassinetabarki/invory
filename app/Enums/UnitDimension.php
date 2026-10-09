<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What a unit measures (ADR-0008). Units of one dimension convert to each other through `units.factor_to_base`;
 * `package` units (roll, cone) hold a different amount per material, so they have no generic factor.
 */
enum UnitDimension: string
{
    case Length = 'length';
    case Area = 'area';
    case Mass = 'mass';
    case Volume = 'volume';
    case Count = 'count';
    case Package = 'package';

    public function label(): string
    {
        return match ($this) {
            self::Length => __('Length'),
            self::Area => __('Area'),
            self::Mass => __('Mass'),
            self::Volume => __('Volume'),
            self::Count => __('Count'),
            self::Package => __('Package'),
        };
    }

    /** Whether a unit of this dimension has a factor to the base unit. */
    public function hasFactor(): bool
    {
        return $this !== self::Package;
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
