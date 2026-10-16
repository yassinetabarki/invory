<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How far an order line, or a whole order, has got (S-49). Worked out on the server from the stage counts.
 */
enum OrderProgress: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Shipped = 'shipped';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => __('Not started'),
            self::InProgress => __('In progress'),
            self::Shipped => __('Shipped'),
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
