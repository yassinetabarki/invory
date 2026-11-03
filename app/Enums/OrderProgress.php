<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How far an order line, or a whole order, has got (S-49; INV-14: from its delivered and cancelled units, see `HasDeliveryCounts`).
 */
enum OrderProgress: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => __('Not started'),
            self::InProgress => __('In progress'),
            self::Delivered => __('Delivered'),
            self::Cancelled => __('Cancelled'),
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
