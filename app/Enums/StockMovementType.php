<?php

declare(strict_types=1);

namespace App\Enums;

enum StockMovementType: string
{
    case Created = 'created';
    case Restock = 'restock';
    case Correction = 'correction';
    case PriceChange = 'price_change';

    public function label(): string
    {
        return match ($this) {
            self::Created => __('Created'),
            self::Restock => __('Restock'),
            self::Correction => __('Correction'),
            self::PriceChange => __('Price change'),
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
