<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The six ways units of an order line change stage (S-49): three forward, three back. Each one moves units from one stage to the
 * next or the previous one.
 */
enum OrderLineMove: string
{
    case StartCutting = 'start_cutting';
    case MarkReady = 'mark_ready';
    case Ship = 'ship';
    case BackToStart = 'back_to_start';
    case BackToCutting = 'back_to_cutting';
    case BackToReady = 'back_to_ready';

    /** The stage the units leave. */
    public function fromStage(): OrderStage
    {
        return match ($this) {
            self::StartCutting => OrderStage::ToStart,
            self::MarkReady, self::BackToStart => OrderStage::InCutting,
            self::Ship, self::BackToCutting => OrderStage::ReadyToShip,
            self::BackToReady => OrderStage::Shipped,
        };
    }

    /** The stage the units arrive in. */
    public function toStage(): OrderStage
    {
        return match ($this) {
            self::StartCutting, self::BackToCutting => OrderStage::InCutting,
            self::MarkReady, self::BackToReady => OrderStage::ReadyToShip,
            self::Ship => OrderStage::Shipped,
            self::BackToStart => OrderStage::ToStart,
        };
    }

    public function isBack(): bool
    {
        return match ($this) {
            self::BackToStart, self::BackToCutting, self::BackToReady => true,
            self::StartCutting, self::MarkReady, self::Ship => false,
        };
    }

    /** The words of the move window's title ("Start cutting · Denim Jacket"). */
    public function label(): string
    {
        return match ($this) {
            self::StartCutting => __('Start cutting'),
            self::MarkReady => __('Mark ready'),
            self::Ship => __('Ship'),
            self::BackToStart => __('Move back to To start'),
            self::BackToCutting => __('Move back to In cutting'),
            self::BackToReady => __('Move back to Ready to ship'),
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
