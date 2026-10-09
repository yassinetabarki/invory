<?php

/** MOCK — UI-first preview data (ADR-0006). Removed by S-17. */

declare(strict_types=1);

namespace App\Mock;

use App\Models\Material;
use Illuminate\Database\Eloquent\Collection;

/**
 * Shapes real Material rows for the preview contract.
 *
 * `photo_url` has no column yet (S-15-BE). The controllers set it on the model as an attribute, which is exactly what a photo
 * accessor will provide later, so the Resources never depend on App\Mock. `used_in_count` is real since S-04b
 * (`Material::withUsedInCount()`).
 */
final class PreviewMaterials
{
    /**
     * Set `photo_url` on every material that a Resource is about to serialise (MaterialResource, and the lines of a product).
     * Strict models throw on a missing attribute, so every place that serialises one calls this first.
     *
     * @param  iterable<Material>  $materials
     */
    public static function decorate(iterable $materials): void
    {
        foreach ($materials as $material) {
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
            'unit' => $material->unit->code,
        ];
    }

    /**
     * Every non-deleted material by name: the one query behind the composition editor's picker.
     *
     * @return Collection<int, Material>
     */
    public static function byName(): Collection
    {
        // The picker shows the unit (always eager-loaded) and never the category: skip that query.
        return Material::query()->without('category')->orderBy('name')->orderBy('id')->get();
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
