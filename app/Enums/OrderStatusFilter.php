<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The Status filter of the Orders list (S-50). "Open" is the default: an order is open until every one of its lines has shipped.
 */
enum OrderStatusFilter: string
{
    case Open = 'open';
    case Shipped = 'shipped';
    case All = 'all';

    public function label(): string
    {
        return match ($this) {
            self::Open => __('Open orders'),
            self::Shipped => __('Shipped'),
            self::All => __('All orders'),
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

    /** The filter shown when the request does not name one. */
    public static function default(): self
    {
        return self::Open;
    }
}
