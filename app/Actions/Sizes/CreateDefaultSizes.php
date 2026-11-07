<?php

declare(strict_types=1);

namespace App\Actions\Sizes;

use App\Models\ProductCategory;
use App\Models\Size;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;

/**
 * Gives a new workshop its 19 default sizes (INV-18, ADR-0028): XS … XXXL, 36 … 46 and One size (flagged), at positions 10, 20 … 190,
 * and the suggestions of Apparel (XS … XXXL) and Footwear (36 … 46). Any other category suggests nothing, which means One size.
 * Sign-up and the demo seeder through `CreateWorkshop`, and every test workshop through `WorkshopFactory`. Workshops that existed before
 * INV-18 got the same list from the migration's own frozen copy; a test keeps the two equal. Changing this list changes new workshops only.
 */
final readonly class CreateDefaultSizes
{
    /** The label of the flagged size. */
    public const ONE_SIZE = 'One size';

    /** @var list<string> in list order (the position is the index + 1, times 10) */
    public const DEFAULTS = [
        'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL',
        '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46',
        self::ONE_SIZE,
    ];

    /** @var array<string, list<string>> category code => the labels it suggests */
    public const SUGGESTIONS = [
        'apparel' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'],
        'footwear' => ['36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46'],
    ];

    /** One insert, with the workshop and the timestamps written out: a bulk insert skips the model's own events and defaults. */
    public function handle(Workshop $workshop): void
    {
        $now = now();

        Size::query()->insert(array_map(
            static fn (string $label, int $index): array => [
                'workshop_id' => $workshop->id,
                'label' => $label,
                'position' => ($index + 1) * 10,
                'is_one_size' => $label === self::ONE_SIZE,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            self::DEFAULTS,
            array_keys(self::DEFAULTS),
        ));

        CurrentWorkshop::run($workshop, function () use ($workshop): void {
            $ids = Size::query()->pluck('id', 'label');

            foreach (ProductCategory::query()->whereIn('code', array_keys(self::SUGGESTIONS))->get() as $category) {
                $category->suggestedSizes()->attach(
                    array_fill_keys(array_map(fn (string $label): int => (int) $ids[$label], self::SUGGESTIONS[$category->code]), ['workshop_id' => $workshop->id]),
                );
            }
        });
    }
}
