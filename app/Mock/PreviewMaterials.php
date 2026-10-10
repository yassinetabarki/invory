<?php

/** MOCK — UI-first preview data (ADR-0006). Removed by S-17. */

declare(strict_types=1);

namespace App\Mock;

use App\Models\Material;

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
}
