<?php

declare(strict_types=1);

namespace App\Enums;

enum UnitOfMeasure: string
{
    case Metre = 'm';
    case Centimetre = 'cm';
    case Yard = 'yd';
    case SquareMetre = 'sqm';
    case SquareFoot = 'sqft';
    case Kilogram = 'kg';
    case Gram = 'g';
    case Litre = 'l';
    case Millilitre = 'ml';
    case Piece = 'pc';
    case Pair = 'pair';
    case Roll = 'roll';
    case Cone = 'cone';

    public function label(): string
    {
        return match ($this) {
            self::Metre => __('Metre (m)'),
            self::Centimetre => __('Centimetre (cm)'),
            self::Yard => __('Yard (yd)'),
            self::SquareMetre => __('Square metre (sqm)'),
            self::SquareFoot => __('Square foot (sqft)'),
            self::Kilogram => __('Kilogram (kg)'),
            self::Gram => __('Gram (g)'),
            self::Litre => __('Litre (l)'),
            self::Millilitre => __('Millilitre (ml)'),
            self::Piece => __('Piece (pc)'),
            self::Pair => __('Pair (pair)'),
            self::Roll => __('Roll (roll)'),
            self::Cone => __('Cone (cone)'),
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
