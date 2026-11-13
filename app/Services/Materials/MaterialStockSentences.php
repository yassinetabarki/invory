<?php

declare(strict_types=1);

namespace App\Services\Materials;

use App\Enums\MaterialCostMode;
use App\Enums\MaterialStockMode;
use App\Enums\StockStatus;
use App\Models\Material;
use App\Models\MaterialColour;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * The sentences and figures that name the colours of a material kept per colour (INV-21, UI card: the material page's callout, the
 * Materials list's Status line, the dashboard's Needs restock row), written here and nowhere else so the screens never build text.
 *
 * Every method answers null for a one-stock material. It reads the loaded `colours` (with their `colour`) and the loaded `unit`; it never
 * queries. A colour "needs restock" when the status of that colour (`Material::colourStatus()`) is low or out; colours are always in
 * the order of Settings › Colours, which is the order of the loaded relation. Quantities read like the restock toast: trailing zeros
 * stripped, then the unit code ("40 m").
 */
final class MaterialStockSentences
{
    /** A material with more colours than this shows "3 of 20 colours need restock" (and the page's switch). */
    private const SUMMARY_OVER = 8;

    /** Colours the list's Status line names before it counts the rest ("Camel, Black, Light wash and 2 more"). */
    private const LISTED = 3;

    /**
     * Lowest and highest colour cost, with cost per colour only (a colour without a cost is left out), as scale-4 strings.
     *
     * @return array{min: string, max: string}|null
     */
    public function unitCostRange(Material $material): ?array
    {
        if ($material->stock_mode !== MaterialStockMode::PerColour || $material->cost_mode !== MaterialCostMode::PerColour) {
            return null;
        }

        $min = null;
        $max = null;

        foreach ($material->colours as $row) {
            if ($row->unit_cost === null) {
                continue;
            }

            $cost = BigDecimal::of($row->unit_cost);
            $min = $min === null || $cost->isLessThan($min) ? $cost : $min;
            $max = $max === null || $cost->isGreaterThan($max) ? $cost : $max;
        }

        return $min === null || $max === null
            ? null
            : ['min' => (string) $min->toScale(4, RoundingMode::HalfUp), 'max' => (string) $max->toScale(4, RoundingMode::HalfUp)];
    }

    /**
     * The warning callout of a per-colour material that needs restock: "Black is below reorder level." / "40 m on hand, reorder at 100 m.",
     * "3 colours are at or below reorder level." / "Black 40 m, Camel 0 m and Rust 12 m. Reorder at 100 m.", "Camel is out of stock." /
     * "0 m on hand, reorder at 100 m.". Null when the material is fine, is one stock, or has no reorder level (no wording in the cards for
     * a material that is out without one: the page keeps today's "Out of stock." callout).
     *
     * @return array{title: string, detail: string}|null
     */
    public function callout(Material $material): ?array
    {
        if ($material->stock_mode !== MaterialStockMode::PerColour || $material->reorder_level === null || $material->stockStatus() === StockStatus::InStock) {
            return null;
        }

        $rows = $this->needingRestock($material);
        $reorder = self::quantity($material->reorder_level, $material->unit->code);

        if ($rows === []) {
            return null;
        }

        if (count($rows) === 1) {
            $row = $rows[0];
            $out = BigDecimal::of($row->quantity_on_hand)->isZero();

            return [
                'title' => $out
                    ? __(':colour is out of stock.', ['colour' => $row->colour->name])
                    : __(':colour is below reorder level.', ['colour' => $row->colour->name]),
                'detail' => __(':quantity on hand, reorder at :reorder.', ['quantity' => self::quantity($row->quantity_on_hand, $material->unit->code), 'reorder' => $reorder]),
            ];
        }

        return [
            'title' => __(':count colours are at or below reorder level.', ['count' => count($rows)]),
            'detail' => __(':colours. Reorder at :reorder.', ['colours' => $this->joinAnd($this->colourQuantities($rows, $material)), 'reorder' => $reorder]),
        ];
    }

    /**
     * The toast after a receipt of a per-colour material: "Restocked Denim 12oz: Indigo +100 m, Black +20 m.", the colours that received
     * a quantity, in the order of Settings › Colours. Null for a one-stock material (its toast keeps today's words).
     *
     * @param  array<int|string, mixed>  $lines  the validated `lines`, by colour id
     */
    public function restocked(Material $material, array $lines): ?string
    {
        if ($material->stock_mode !== MaterialStockMode::PerColour) {
            return null;
        }

        $received = [];

        foreach ($material->colours as $row) {
            $line = $lines[$row->colour_id] ?? null;
            $quantity = is_array($line) ? ($line['quantity'] ?? null) : null;

            if ($quantity !== null && $quantity !== '') {
                $received[] = $row->colour->name.' +'.self::quantity((string) $quantity, $material->unit->code);
            }
        }

        return __('Restocked :name: :received.', ['name' => $material->name, 'received' => implode(', ', $received)]);
    }

    /** "3 of 20 colours need restock" ("1 of 12 colours needs restock", "0 of 12 colours need restock"), for a material with more than 8 colours only. */
    public function coloursSummary(Material $material): ?string
    {
        if ($material->stock_mode !== MaterialStockMode::PerColour || $material->colours->count() <= self::SUMMARY_OVER) {
            return null;
        }

        $count = count($this->needingRestock($material));

        return trans_choice('{1} :count of :total colours needs restock|[0,*] :count of :total colours need restock', $count, ['total' => $material->colours->count()]);
    }

    /**
     * The list's Status line: the colours that need restock, "Black", "Black and Camel", "Camel, Black and Light wash", and from four on
     * the first three and a count ("Camel, Black, Light wash and 2 more"). Null when the material is fine or is one stock.
     */
    public function restockColours(Material $material): ?string
    {
        $names = $this->restockingRows($material);

        if ($names === []) {
            return null;
        }

        $names = array_map(static fn (MaterialColour $row): string => $row->colour->name, $names);

        if (count($names) <= self::LISTED) {
            return $this->joinAnd($names);
        }

        return implode(', ', array_slice($names, 0, self::LISTED)).' '.__('and :count more', ['count' => count($names) - self::LISTED]);
    }

    /** The dashboard row's colours: "Black 40 m, Camel 0 m". Null when the material is fine or is one stock. */
    public function coloursText(Material $material): ?string
    {
        $rows = $this->restockingRows($material);

        return $rows === [] ? null : implode(', ', $this->colourQuantities($rows, $material));
    }

    /**
     * The colours to name next to a material's status: none while the material is in stock (a colour at 0 with no reorder level is not
     * a restock matter), else the colours that need restock.
     *
     * @return list<MaterialColour>
     */
    private function restockingRows(Material $material): array
    {
        if ($material->stock_mode !== MaterialStockMode::PerColour || $material->stockStatus() === StockStatus::InStock) {
            return [];
        }

        return $this->needingRestock($material);
    }

    /** @return list<MaterialColour> the colours whose status is low or out, in list order */
    private function needingRestock(Material $material): array
    {
        return array_values($material->colours
            ->filter(fn (MaterialColour $row): bool => $material->colourStatus($row) !== StockStatus::InStock)
            ->all());
    }

    /**
     * @param  list<MaterialColour>  $rows
     * @return list<string> "Black 40 m"
     */
    private function colourQuantities(array $rows, Material $material): array
    {
        return array_map(
            fn (MaterialColour $row): string => $row->colour->name.' '.self::quantity($row->quantity_on_hand, $material->unit->code),
            $rows,
        );
    }

    /**
     * "1,250": a quantity the way the screens write it, a "," between thousands, its decimals kept and their trailing zeros stripped
     * ("12.5", "250"). Worked from the decimal string: no float.
     */
    public static function number(string|BigDecimal $quantity): string
    {
        $plain = rtrim(rtrim((string) BigDecimal::of($quantity)->toScale(4, RoundingMode::HalfUp), '0'), '.');
        [$whole, $decimals] = array_pad(explode('.', $plain, 2), 2, null);
        $whole = (string) preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $whole);

        return $decimals === null ? $whole : $whole.'.'.$decimals;
    }

    /** "1,250 m": {@see number()}, then the unit code. */
    public static function quantity(string|BigDecimal $quantity, string $unit): string
    {
        return self::number($quantity).' '.$unit;
    }

    /** "The stock would exceed 9,999,999,999.9999.": a receipt or a save that takes a material's total over the column's limit. */
    public static function overflow(string $max): string
    {
        return __('The stock would exceed :max.', ['max' => self::number($max)]);
    }

    /**
     * What a switch back to one stock makes of the typed quantities: "Black 40 m + Indigo 250 m become one stock of 290 m." ("Black 40 m
     * becomes one stock of 40 m." for one colour).
     *
     * @param  list<array{name: string, quantity: string}>  $colours  in the order of Settings › Colours
     */
    public static function oneStock(array $colours, string|BigDecimal $total, string $unit): string
    {
        return trans_choice(':colours becomes one stock of :total.|:colours become one stock of :total.', count($colours), [
            'colours' => implode(' + ', array_map(static fn (array $colour): string => $colour['name'].' '.self::quantity($colour['quantity'], $unit), $colours)),
            'total' => self::quantity($total, $unit),
        ]);
    }

    /**
     * "A", "A and B", "A, B and C".
     *
     * @param  list<string>  $items
     */
    private function joinAnd(array $items): string
    {
        if (count($items) < 2) {
            return $items[0] ?? '';
        }

        return implode(', ', array_slice($items, 0, -1)).' '.__('and').' '.$items[count($items) - 1];
    }
}
