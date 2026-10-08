<?php

/** MOCK — UI-first preview data (ADR-0006). Removed by S-17. */

declare(strict_types=1);

namespace App\Mock;

use App\Models\Material;
use Illuminate\Database\Eloquent\Collection;

/**
 * Shapes real Material rows for the preview contract.
 *
 * `used_in_count` and `photo_url` have no column yet. The controllers set them on the model as attributes, which is exactly
 * what the real `withCount()` and a photo accessor will provide later, so MaterialResource never depends on App\Mock.
 */
final class PreviewMaterials
{
    /**
     * Set `used_in_count` and `photo_url` on every material that MaterialResource is about to serialise.
     * Strict models throw on a missing attribute, so every place that serialises a MaterialResource calls this first.
     *
     * @param  iterable<Material>  $materials
     */
    public static function decorate(iterable $materials): void
    {
        foreach ($materials as $material) {
            $material->setAttribute('used_in_count', PreviewFixture::usedInCount($material->sku));
            $material->setAttribute('photo_url', PreviewFixture::photoUrl($material->sku));
        }
    }

    /**
     * `MaterialRef` in resources/js/types/domain.ts.
     *
     * @return array{id: int, sku: string, name: string, unit: string}
     */
    public static function ref(Material $material): array
    {
        return [
            'id' => $material->id,
            'sku' => $material->sku,
            'name' => $material->name,
            'unit' => $material->unit->value,
        ];
    }

    /**
     * Every non-deleted material by name: the one query behind the composition editor's picker.
     *
     * @return Collection<int, Material>
     */
    public static function byName(): Collection
    {
        return Material::query()->orderBy('name')->orderBy('id')->get();
    }

    /**
     * `MaterialOption[]`: the given materials (from `byName()`), for the composition editor's picker.
     *
     * @param  iterable<Material>  $materials
     * @return list<array<string, mixed>>
     */
    public static function options(iterable $materials): array
    {
        $options = [];

        foreach ($materials as $material) {
            $options[] = self::option($material);
        }

        return $options;
    }

    /**
     * `MaterialOption` (the composition editor's picker).
     *
     * @return array<string, mixed>
     */
    public static function option(Material $material): array
    {
        return [
            ...self::ref($material),
            'unit_label' => $material->unit->label(),
            'unit_cost' => $material->unit_cost,
            'quantity_on_hand' => $material->quantity_on_hand,
            'stock_status' => $material->stockStatus()->value,
        ];
    }
}
