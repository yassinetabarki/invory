<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Models\MaterialCategory;
use App\Models\Unit;

/**
 * Forms and filters carry codes (`category: 'fabric'`, `unit: 'm'`); the table holds ids. The FormRequests have already
 * checked the codes, so a code with no row can't reach this point.
 */
final readonly class ResolveReferenceCodes
{
    /**
     * @param  array<string, mixed>  $data  validated input
     * @return array<string, mixed> the same data with `category` and `unit` replaced by `category_id` and `unit_id`
     */
    public function handle(array $data): array
    {
        if (array_key_exists('category', $data)) {
            $data['category_id'] = MaterialCategory::query()->where('code', $data['category'])->value('id');
            unset($data['category']);
        }

        if (array_key_exists('unit', $data)) {
            $data['unit_id'] = Unit::query()->where('code', $data['unit'])->value('id');
            unset($data['unit']);
        }

        return $data;
    }
}
