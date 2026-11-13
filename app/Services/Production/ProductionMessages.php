<?php

declare(strict_types=1);

namespace App\Services\Production;

use App\Domain\Stock\CutPlan;
use App\Domain\Stock\MaterialTake;
use App\Models\Colour;
use App\Models\ProductionBatch;
use App\Services\Orders\OrderMessages;
use Illuminate\Support\Arr;
use Illuminate\Support\Number;

/**
 * Every message and toast of the production pages (INV-5): Settings › Production phases, Start batch, the moves, Put in stock and
 * Undo batch, so the requests, the Actions and their tests say the same words. Counts are printed grouped ("1,000,000").
 */
final readonly class ProductionMessages
{
    public const IN_CUTTING = 'In cutting';

    public const PUT_IN_STOCK = 'Put in stock';

    public const FROM_PRODUCTION = 'From production';

    // Settings › Production phases

    public static function nameRequired(): string
    {
        return __('Enter the phase\'s name.');
    }

    public static function nameTooLong(): string
    {
        return __('Keep the name to 24 characters or fewer.');
    }

    /** 'You already have a phase named "Sewing".' */
    public static function nameTaken(string $name): string
    {
        return __('You already have a phase named ":name".', ['name' => $name]);
    }

    public static function nameReserved(): string
    {
        return __('In cutting and Put in stock are already there. Choose another name.');
    }

    public static function tooManyPhases(): string
    {
        return __('You can have up to 6 phases.');
    }

    /** "Sewing holds 15 units, so it can't be moved or removed. Move them on or back first." */
    public static function phaseHolds(string $phase, int $units): string
    {
        return trans_choice(
            '{1} :phase holds :count unit, so it can\'t be moved or removed. Move it on or back first.'
            .'|[2,*] :phase holds :count units, so it can\'t be moved or removed. Move them on or back first.',
            $units,
            ['phase' => $phase, 'count' => self::count($units)],
        );
    }

    /** "Quality check now holds 2 units, so it can't be removed." */
    public static function phaseNowHolds(string $phase, int $units): string
    {
        return trans_choice(
            '{1} :phase now holds :count unit, so it can\'t be removed.|[2,*] :phase now holds :count units, so it can\'t be removed.',
            $units,
            ['phase' => $phase, 'count' => self::count($units)],
        );
    }

    public static function phaseAdded(): string
    {
        return __('Phase added.');
    }

    public static function phaseUpdated(): string
    {
        return __('Phase updated.');
    }

    public static function phaseRemoved(): string
    {
        return __('Phase removed.');
    }

    public static function phasesReordered(): string
    {
        return __('Phases reordered.');
    }

    // Start batch

    public static function chooseProduct(): string
    {
        return __('Choose a product.');
    }

    public static function nothingToStart(): string
    {
        return __('Enter at least one unit to start.');
    }

    /** "The colours or sizes of Denim Jacket have changed. Enter the units again." */
    public static function startChanged(string $product): string
    {
        return __('The colours or sizes of :product have changed. Enter the units again.', ['product' => $product]);
    }

    /** "Denim Jacket was deleted, so it can't be started." */
    public static function startDeleted(string $product): string
    {
        return __(':product was deleted, so it can\'t be started.', ['product' => $product]);
    }

    /**
     * Why a start is refused for short stock (INV-16-k, AC-6): a grid of several colours and sizes of a product that varies by colour and
     * size says which materials are short, in composition order, without a number to try; anything else keeps ADR-0014's sentence.
     *
     * @param  array<int, string>|null  $colourNames  the names of the plan's colours by id, when the caller already read them; read here when null
     */
    public static function notEnoughStock(CutPlan $plan, ?array $colourNames = null): string
    {
        // A stock of a per-colour material is named with its colour (INV-21, ADR-0029 section 6).
        $ids = array_values(array_unique(array_filter(array_map(static fn (MaterialTake $take): ?int => $take->colourId, $plan->takes))));
        $colours = $colourNames ?? ($ids === [] ? [] : Colour::query()->whereIn('id', $ids)->pluck('name', 'id')->all());

        if (! $plan->mixed) {
            foreach ($plan->takes as $take) {
                if ($take->colourId !== null && $take->materialId === $plan->bottleneckMaterialId) {
                    $plan = new CutPlan(
                        $plan->units, $plan->takes, $plan->maxCuttable, $plan->bottleneckMaterialId,
                        self::inColours($take->materialName, [$colours[$take->colourId] ?? '']), $plan->allowed, $plan->mixed, $plan->variants,
                    );
                }
            }

            return OrderMessages::notEnoughStock($plan);
        }

        $short = [];
        $stocks = 0;

        foreach ($plan->takes as $take) {
            if ($take->shortBy === null) {
                continue;
            }

            $stocks++;
            $short[$take->materialId]['name'] = $take->materialName;

            if ($take->colourId !== null) {
                $short[$take->materialId]['colours'][] = $colours[$take->colourId] ?? '';
            }
        }

        return self::shortOn(array_map(
            static fn (array $material): string => isset($material['colours']) ? self::inColours($material['name'], $material['colours']) : $material['name'],
            array_values($short),
        ), $stocks);
    }

    /**
     * "Denim 12oz in Black" · "Denim 12oz in Black and Light wash" (colours in list order).
     *
     * @param  list<string>  $colours
     */
    private static function inColours(string $material, array $colours): string
    {
        return __(':material in :colours', ['material' => $material, 'colours' => Arr::join($colours, ', ', ' and ')]);
    }

    /**
     * "Short on Denim 12oz Black. Restock it first, or lower the units." · "Short on Denim 12oz Black and Metal shank button 17mm.
     * Restock them first, or lower the units." (three or more: "A, B and C"). A per-colour material is named with its colours: "Short
     * on Denim 12oz in Black and Light wash. Restock them first, or lower the units." (`$stocks` counts the colours, so it reads "them").
     *
     * @param  list<string>  $materialNames  at least one, in composition order
     * @param  int|null  $stocks  how many stocks are short (a material in two colours is two); the names' count when null
     */
    public static function shortOn(array $materialNames, ?int $stocks = null): string
    {
        return trans_choice(
            '{1} Short on :materials. Restock it first, or lower the units.|[2,*] Short on :materials. Restock them first, or lower the units.',
            $stocks ?? count($materialNames),
            ['materials' => Arr::join($materialNames, ', ', ' and ')],
        );
    }

    // Moves, Put in stock and Undo batch

    /** "You can move at most 6 units of this batch." */
    public static function moveLimit(int $units): string
    {
        return trans_choice(
            '{1} You can move at most :count unit of this batch.|[0,*] You can move at most :count units of this batch.',
            $units,
            ['count' => self::count($units)],
        );
    }

    public static function moveAtLeastOne(): string
    {
        return __('Enter at least one unit to move.');
    }

    public static function wholeUnits(): string
    {
        return __('Enter a whole number of units.');
    }

    public static function movedOn(): string
    {
        return __('These units have moved on since you opened this page.');
    }

    /** "4 units of Denim Jacket · Indigo M moved to Finishing." */
    public static function moved(int $units, string $what, string $to): string
    {
        return trans_choice(
            '{1} :count unit of :what moved to :to.|[2,*] :count units of :what moved to :to.',
            $units,
            ['count' => self::count($units), 'what' => $what, 'to' => $to],
        );
    }

    /** "… Batch B-0149 was split: the units moved are on B-0155." */
    public static function split(string $from, string $to): string
    {
        return __('Batch :from was split: the units moved are on :to.', ['from' => $from, 'to' => $to]);
    }

    public static function stockAtLeastOne(): string
    {
        return __('Enter at least one unit to put in stock.');
    }

    /** "You can put at most 4 units of this batch in stock." */
    public static function stockLimit(int $units): string
    {
        return trans_choice(
            '{1} You can put at most :count unit of this batch in stock.|[0,*] You can put at most :count units of this batch in stock.',
            $units,
            ['count' => self::count($units)],
        );
    }

    /** "Indigo M can't go over 1,000,000 units on the shelf." */
    public static function shelfLimit(string $variant): string
    {
        return __(':variant can\'t go over 1,000,000 units on the shelf.', ['variant' => $variant]);
    }

    /** "Denim Jacket was deleted, so it can't be put in stock. Move the batch back to In cutting and undo it to return its materials." */
    public static function stockDeleted(string $product): string
    {
        return __(':product was deleted, so it can\'t be put in stock. Move the batch back to In cutting and undo it to return its materials.', ['product' => $product]);
    }

    /** "4 units of Denim Jacket · Indigo M put in stock." */
    public static function putInStock(int $units, string $what): string
    {
        return trans_choice(
            '{1} :count unit of :what put in stock.|[2,*] :count units of :what put in stock.',
            $units,
            ['count' => self::count($units), 'what' => $what],
        );
    }

    /** The print action of a batch's ticket (INV-15): "Print ticket B-0155". */
    public static function printTicket(string $batch): string
    {
        return "Print ticket {$batch}";
    }

    public static function seeTheRestock(): string
    {
        return __('See the restock');
    }

    /** "B-0150 has moved on since you opened this page, so it can't be undone. Move it back to In cutting first." */
    public static function undoMovedOn(string $batch): string
    {
        return __(':batch has moved on since you opened this page, so it can\'t be undone. Move it back to In cutting first.', ['batch' => $batch]);
    }

    /** "Batch B-0150 undone. Materials returned to stock." */
    public static function undone(string $batch): string
    {
        return __('Batch :batch undone. Materials returned to stock.', ['batch' => $batch]);
    }

    /** "Denim Jacket · Indigo M" (a "One size" product: "Leather Tote · Camel"). */
    public static function what(ProductionBatch $batch, string $variant): string
    {
        return $batch->product->name.' · '.$variant;
    }

    private static function count(int $units): string
    {
        return (string) Number::format($units, locale: 'en');
    }
}
