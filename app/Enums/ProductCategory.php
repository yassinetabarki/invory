<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductCategory: string
{
    case Apparel = 'apparel';
    case Footwear = 'footwear';
    case Bag = 'bag';
    case Accessory = 'accessory';
    case Jewelry = 'jewelry';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Apparel => __('Apparel'),
            self::Footwear => __('Footwear'),
            self::Bag => __('Bag'),
            self::Accessory => __('Accessory'),
            self::Jewelry => __('Jewelry'),
            self::Other => __('Other'),
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
