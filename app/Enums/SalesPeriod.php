<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonImmutable;

enum SalesPeriod: string
{
    case LastSevenDays = 'last_7_days';
    case LastThirtyDays = 'last_30_days';
    case ThisMonth = 'this_month';
    case ThisYear = 'this_year';

    public function label(): string
    {
        return match ($this) {
            self::LastSevenDays => __('Last 7 days'),
            self::LastThirtyDays => __('Last 30 days'),
            self::ThisMonth => __('This month'),
            self::ThisYear => __('This year'),
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

    /** The period shown when the request does not name one. */
    public static function default(): self
    {
        return self::LastThirtyDays;
    }

    /**
     * Inclusive calendar-day range ending today (see "Sales" in the costing domain skill).
     * Both bounds are start-of-day, in the timezone of `$today`.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable} [first day, today]
     */
    public function range(CarbonImmutable $today): array
    {
        $end = $today->startOfDay();

        $start = match ($this) {
            self::LastSevenDays => $end->subDays(6),
            self::LastThirtyDays => $end->subDays(29),
            self::ThisMonth => $end->startOfMonth(),
            self::ThisYear => $end->startOfYear(),
        };

        return [$start, $end];
    }
}
