<?php

/** MOCK — UI-first preview data (ADR-0006). Removed by S-17. */

declare(strict_types=1);

namespace App\Mock;

use App\Enums\StockMovementType;
use App\Models\Material;
use Carbon\CarbonImmutable;

/**
 * Builds the `StockMovement` props from the fixture history, keyed by material SKU.
 *
 * Entry ids run 1, 2, 3 … across the whole fixture in the order of PreviewFixture::history() (whether or not the material
 * exists in the database, so ids are stable); a material that is missing from the database has no history.
 *
 * @phpstan-import-type FixtureMovement from PreviewFixture
 */
final class PreviewHistory
{
    /**
     * Every movement of the given materials, newest first within a material (date descending, then id descending).
     * Materials that are not in the fixture have an empty list.
     *
     * @param  iterable<Material>  $materials
     * @return array<int, list<array<string, mixed>>> by material id
     */
    public static function byMaterial(iterable $materials): array
    {
        $idsBySku = [];

        foreach ($materials as $material) {
            $idsBySku[$material->sku] = $material->id;
        }

        $movements = [];
        $today = CarbonImmutable::today();
        $entryId = 0;

        foreach (PreviewFixture::history() as $sku => $entries) {
            foreach ($entries as $entry) {
                $entryId++;

                if (isset($idsBySku[$sku])) {
                    $movements[$idsBySku[$sku]][] = self::shape($entryId, $idsBySku[$sku], $today, $entry);
                }
            }
        }

        foreach ($idsBySku as $materialId) {
            $list = $movements[$materialId] ?? [];
            usort($list, static fn (array $a, array $b): int => [$b['occurred_on'], $b['id']] <=> [$a['occurred_on'], $a['id']]);
            $movements[$materialId] = $list;
        }

        return $movements;
    }

    /**
     * The newest movements of each material, flattened in the order of the materials.
     *
     * @param  iterable<Material>  $materials
     * @return list<array<string, mixed>>
     */
    public static function recent(iterable $materials, int $perMaterial = 5): array
    {
        $materials = [...$materials];
        $byMaterial = self::byMaterial($materials);
        $recent = [];

        foreach ($materials as $material) {
            array_push($recent, ...array_slice($byMaterial[$material->id] ?? [], 0, $perMaterial));
        }

        return $recent;
    }

    /**
     * `StockMovement`.
     *
     * @param  FixtureMovement  $entry
     * @return array<string, mixed>
     */
    private static function shape(int $id, int $materialId, CarbonImmutable $today, array $entry): array
    {
        $type = $entry['type'];

        return [
            'id' => $id,
            'material_id' => $materialId,
            'type' => $type->value,
            'type_label' => $type->label(),
            'occurred_on' => $today->subDays($entry['days_ago'])->toDateString(),
            'quantity' => PreviewDecimal::scale4($entry['quantity'] ?? null),
            'quantity_before' => PreviewDecimal::scale4($entry['quantity_before'] ?? null),
            // For `created`, the stock after the entry is the opening stock.
            'quantity_after' => PreviewDecimal::scale4($entry['quantity_after'] ?? ($type === StockMovementType::Created ? ($entry['quantity'] ?? null) : null)),
            'unit_price' => PreviewDecimal::scale4($entry['unit_price'] ?? null),
            'unit_cost_before' => PreviewDecimal::scale4($entry['unit_cost_before'] ?? null),
            'unit_cost_after' => PreviewDecimal::scale4($entry['unit_cost_after'] ?? null),
            'reference' => $entry['reference'] ?? null,
            'note' => $entry['note'] ?? null,
            'user_name' => PreviewFixture::USER_NAME,
        ];
    }
}
