<?php

use App\Enums\ProductStatus;
use App\Enums\SalesPeriod;
use App\Enums\StockMovementType;
use Carbon\CarbonImmutable;
use Tests\TestCase;

// Labels go through __(), so these tests need the application booted.
uses(TestCase::class);

dataset('uiFirstEnums', [
    'ProductStatus' => [ProductStatus::class],
    'SalesPeriod' => [SalesPeriod::class],
    'StockMovementType' => [StockMovementType::class],
]);

it('exposes options as a list of value/label pairs', function (string $enum) {
    $options = $enum::options();

    expect($options)->toBeArray()
        ->and(array_is_list($options))->toBeTrue()
        ->and($options)->toHaveCount(count($enum::cases()));

    foreach ($enum::cases() as $index => $case) {
        expect($options[$index])->toBe(['value' => $case->value, 'label' => $case->label()]);
    }
})->with('uiFirstEnums');

it('gives every case a non-empty label and a unique value', function (string $enum) {
    $values = [];
    $labels = [];

    foreach ($enum::cases() as $case) {
        expect($case->label())->toBeString()->not->toBeEmpty();
        $values[] = $case->value;
        $labels[] = $case->label();
    }

    expect($values)->toBe(array_values(array_unique($values)))
        ->and($labels)->toBe(array_values(array_unique($labels)));
})->with('uiFirstEnums');

it('matches the domain skill values and the TypeScript unions, in order', function () {
    expect(array_column(ProductStatus::options(), 'value'))
        ->toBe(['draft', 'active', 'archived'])
        ->and(array_column(SalesPeriod::options(), 'value'))
        ->toBe(['last_7_days', 'last_30_days', 'this_month', 'this_year'])
        ->and(array_column(StockMovementType::options(), 'value'))
        ->toBe(['created', 'restock', 'correction', 'price_change']);
});

it('uses the decided labels', function () {
    expect(array_column(ProductStatus::options(), 'label'))
        ->toBe(['Draft', 'Active', 'Archived'])
        ->and(array_column(SalesPeriod::options(), 'label'))
        ->toBe(['Last 7 days', 'Last 30 days', 'This month', 'This year'])
        ->and(array_column(StockMovementType::options(), 'label'))
        ->toBe(['Created', 'Restock', 'Correction', 'Price change']);
});

it('can be created from its backing value', function () {
    expect(ProductStatus::from('archived'))->toBe(ProductStatus::Archived)
        ->and(SalesPeriod::from('this_year'))->toBe(SalesPeriod::ThisYear)
        ->and(StockMovementType::from('price_change'))->toBe(StockMovementType::PriceChange)
        ->and(SalesPeriod::tryFrom('last_90_days'))->toBeNull()
        ->and(ProductStatus::tryFrom('sold'))->toBeNull();
});

it('defaults the sales period to the last 30 days', function () {
    expect(SalesPeriod::default())->toBe(SalesPeriod::LastThirtyDays)
        ->and(SalesPeriod::default()->value)->toBe('last_30_days');
});

/**
 * @param  array{0: CarbonImmutable, 1: CarbonImmutable}  $range
 * @return array{0: string, 1: string}
 */
function periodDates(array $range): array
{
    return [$range[0]->toDateString(), $range[1]->toDateString()];
}

it('computes inclusive start-of-day ranges ending today', function (string $today, SalesPeriod $period, string $start) {
    $range = $period->range(CarbonImmutable::parse($today));

    expect(periodDates($range))->toBe([$start, CarbonImmutable::parse($today)->toDateString()])
        ->and($range[0])->toBeInstanceOf(CarbonImmutable::class)
        ->and($range[1])->toBeInstanceOf(CarbonImmutable::class)
        ->and($range[0]->toTimeString())->toBe('00:00:00')
        ->and($range[1]->toTimeString())->toBe('00:00:00');
})->with([
    // The plan's reference day.
    'last 7 days' => ['2026-10-08', SalesPeriod::LastSevenDays, '2026-10-02'],
    'last 30 days' => ['2026-10-08', SalesPeriod::LastThirtyDays, '2026-09-09'],
    'this month' => ['2026-10-08', SalesPeriod::ThisMonth, '2026-10-01'],
    'this year' => ['2026-10-08', SalesPeriod::ThisYear, '2026-01-01'],
    // Month boundary: the window reaches into February (28 days in 2026).
    'last 7 days across a month boundary' => ['2026-03-03', SalesPeriod::LastSevenDays, '2026-02-25'],
    'last 30 days across a month boundary' => ['2026-03-03', SalesPeriod::LastThirtyDays, '2026-02-02'],
    // Non-leap versus leap February.
    'last 30 days, 1 March of a non-leap year' => ['2027-03-01', SalesPeriod::LastThirtyDays, '2027-01-31'],
    'last 30 days, 1 March of a leap year' => ['2028-03-01', SalesPeriod::LastThirtyDays, '2028-02-01'],
    // First day of the month and of the year: the range is one day long for the calendar presets.
    'this month on the 1st' => ['2026-10-01', SalesPeriod::ThisMonth, '2026-10-01'],
    'this month on the 31st' => ['2026-12-31', SalesPeriod::ThisMonth, '2026-12-01'],
    'this year on 1 January' => ['2026-01-01', SalesPeriod::ThisYear, '2026-01-01'],
    'this year on 31 December' => ['2026-12-31', SalesPeriod::ThisYear, '2026-01-01'],
    // Rolling windows cross the year boundary.
    'last 7 days on 1 January' => ['2026-01-01', SalesPeriod::LastSevenDays, '2025-12-26'],
    'last 30 days on 1 January' => ['2026-01-01', SalesPeriod::LastThirtyDays, '2025-12-03'],
    'this month on 1 January' => ['2026-01-01', SalesPeriod::ThisMonth, '2026-01-01'],
]);

it('covers exactly 7 and 30 calendar days for the rolling presets', function () {
    $today = CarbonImmutable::parse('2026-10-08');

    foreach ([[SalesPeriod::LastSevenDays, 7], [SalesPeriod::LastThirtyDays, 30]] as [$period, $days]) {
        [$start, $end] = $period->range($today);

        // inclusive: start + (days - 1) is today, start + days is not
        expect($start->addDays($days - 1)->equalTo($end))->toBeTrue()
            ->and($start->addDays($days)->equalTo($end))->toBeFalse();
    }
});

it('ignores the time of day of the given date', function () {
    $range = SalesPeriod::LastSevenDays->range(CarbonImmutable::parse('2026-10-08 23:59:59'));

    expect(periodDates($range))->toBe(['2026-10-02', '2026-10-08'])
        ->and($range[0]->toTimeString())->toBe('00:00:00')
        ->and($range[1]->toTimeString())->toBe('00:00:00');
});

it('keeps the timezone of the given date', function () {
    $today = CarbonImmutable::parse('2026-10-08 23:30:00', 'Africa/Tunis');

    foreach (SalesPeriod::cases() as $period) {
        [$start, $end] = $period->range($today);

        expect($start->getTimezone()->getName())->toBe('Africa/Tunis')
            ->and($end->getTimezone()->getName())->toBe('Africa/Tunis')
            ->and($end->toDateString())->toBe('2026-10-08');
    }
});

it('does not mutate the date it is given', function () {
    $today = CarbonImmutable::parse('2026-10-08 15:42:10');

    SalesPeriod::ThisYear->range($today);

    expect($today->toDateTimeString())->toBe('2026-10-08 15:42:10');
});
