<?php

declare(strict_types=1);

namespace App\Enums;

enum StockStatus: string
{
    case InStock = 'in_stock';
    case Low = 'low';
    case Out = 'out';

    public function label(): string
    {
        return match ($this) {
            self::InStock => __('In stock'),
            self::Low => __('Low stock'),
            self::Out => __('Out of stock'),
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
