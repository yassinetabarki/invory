<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Material */
final class MaterialResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            // Codes, as before the reference tables (ADR-0008); the labels are the row's name ("Metre (m)" for units).
            'category' => $this->category->code,
            'category_label' => $this->category->name,
            'unit' => $this->unit->code,
            'unit_label' => $this->unit->label(),
            'unit_cost' => $this->unit_cost,
            'quantity_on_hand' => $this->quantity_on_hand,
            'reorder_level' => $this->reorder_level,
            'stock_value' => $this->stockValue(),
            'stock_status' => $this->stockStatus()->value,
            'supplier_name' => $this->supplier_name,
            'supplier_reference' => $this->supplier_reference,
            'color' => $this->color,
            'composition' => $this->composition,
            'notes' => $this->notes,
            // Not columns: `photo_url` is the model's accessor over `photo_path` and `used_in_count` comes from `withUsedInCount()`
            // (strict mode throws when a narrow select left either source out).
            'photo_url' => $this->photo_url,
            'used_in_count' => $this->used_in_count,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
