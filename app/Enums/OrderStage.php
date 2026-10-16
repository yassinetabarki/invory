<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where the units of an order line are (S-49). To start is never stored: it is the quantity minus the three counters.
 */
enum OrderStage: string
{
    case ToStart = 'to_start';
    case InCutting = 'in_cutting';
    case ReadyToShip = 'ready_to_ship';
    case Shipped = 'shipped';

    public function label(): string
    {
        return match ($this) {
            self::ToStart => __('To start'),
            self::InCutting => __('In cutting'),
            self::ReadyToShip => __('Ready to ship'),
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
