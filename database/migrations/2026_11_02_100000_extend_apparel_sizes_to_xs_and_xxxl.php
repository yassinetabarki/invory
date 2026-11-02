<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The apparel size list (code `s_to_xl`, ADR-0015) grows from S, M, L, XL to XS, S, M, L, XL, XXL, XXXL (the human's choice, 2026-11-02).
 *
 * The list is the frozen snapshot of 2026_10_21_100000, so it changes here and not there: a database that already ran that migration
 * gets the new sizes too. The four existing sizes keep their ids (finished stock, order lines and barcodes point at them) and only move
 * one position down; XS takes position 1, XXL 6 and XXXL 7. The code stays `s_to_xl` (it is immutable); the name the screens show
 * becomes "XS to XXXL", like "36 to 46". Positions move in two steps (+100, then back) so the unique (list, position) index never
 * sees two sizes at one position. Inserts use `insertOrIgnore`, so re-running is harmless.
 */
return new class extends Migration
{
    private const CODE = 's_to_xl';

    /** @var list<string> */
    private const KEPT = ['S', 'M', 'L', 'XL'];

    /** @var array<string, int> label => position */
    private const ADDED = ['XS' => 1, 'XXL' => 6, 'XXXL' => 7];

    public function up(): void
    {
        $listId = DB::table('size_lists')->where('code', self::CODE)->value('id');

        if ($listId === null) {
            return;
        }

        DB::transaction(function () use ($listId): void {
            $now = now();

            DB::table('sizes')->where('size_list_id', $listId)->whereIn('label', self::KEPT)
                ->update(['position' => DB::raw('position + 100')]);

            foreach (self::KEPT as $index => $label) {
                DB::table('sizes')->where('size_list_id', $listId)->where('label', $label)
                    ->update(['position' => $index + 2, 'updated_at' => $now]);
            }

            DB::table('sizes')->insertOrIgnore(array_map(
                fn (string $label, int $position): array => [
                    'size_list_id' => $listId,
                    'label' => $label,
                    'position' => $position,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                array_keys(self::ADDED),
                self::ADDED,
            ));

            DB::table('size_lists')->where('id', $listId)->update(['name' => 'XS to XXXL', 'updated_at' => $now]);
        });
    }

    /** Fails on the size's foreign keys (restrict) when XS, XXL or XXXL already hold stock, order lines or barcodes: nothing is lost. */
    public function down(): void
    {
        $listId = DB::table('size_lists')->where('code', self::CODE)->value('id');

        if ($listId === null) {
            return;
        }

        DB::transaction(function () use ($listId): void {
            $now = now();

            DB::table('sizes')->where('size_list_id', $listId)->whereIn('label', array_keys(self::ADDED))->delete();

            DB::table('sizes')->where('size_list_id', $listId)->whereIn('label', self::KEPT)
                ->update(['position' => DB::raw('position + 100')]);

            foreach (self::KEPT as $index => $label) {
                DB::table('sizes')->where('size_list_id', $listId)->where('label', $label)
                    ->update(['position' => $index + 1, 'updated_at' => $now]);
            }

            DB::table('size_lists')->where('id', $listId)->update(['name' => 'S, M, L, XL', 'updated_at' => $now]);
        });
    }
};
