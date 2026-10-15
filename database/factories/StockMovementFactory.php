<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Ledger rows for tests that need a history without running the Actions. The default is a restock; the states fill the
 * columns of each type as the Actions do. A factory row does NOT move the material's balance: the ledger of a material built
 * this way only balances if the test makes it so.
 *
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'material_id' => Material::factory(),
            'workshop_id' => static fn (array $attributes): mixed => Material::withTrashed()->without('category', 'unit')->whereKey($attributes['material_id'])->value('workshop_id'),
            'type' => StockMovementType::Restock,
            'occurred_on' => now()->toDateString(),
            'quantity' => '100.0000',
            'quantity_before' => null,
            'quantity_after' => '100.0000',
            'unit_price' => '1.0000',
            'unit_cost_before' => null,
            'unit_cost_after' => null,
            'reference' => null,
            'note' => null,
            'user_id' => null,
        ];
    }

    /** The opening entry: `$quantity` of stock at `$unitCost`. */
    public function created(string $quantity = '100.0000', string $unitCost = '1.0000'): static
    {
        return $this->state(fn (): array => [
            'type' => StockMovementType::Created,
            'quantity' => $quantity,
            'quantity_after' => $quantity,
            'unit_price' => $unitCost,
        ]);
    }

    /** A receipt of `$quantity` at `$unitPrice` that brought the stock to `$after`. */
    public function restock(string $quantity, string $after, string $unitPrice = '1.0000', ?string $reference = null): static
    {
        return $this->state(fn (): array => [
            'type' => StockMovementType::Restock,
            'quantity' => $quantity,
            'quantity_after' => $after,
            'unit_price' => $unitPrice,
            'reference' => $reference,
        ]);
    }

    /** A manual change of the stock from `$before` to `$after`. */
    public function correction(string $before, string $after, ?string $note = null): static
    {
        return $this->state(fn (): array => [
            'type' => StockMovementType::Correction,
            'quantity' => null,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'unit_price' => null,
            'note' => $note,
        ]);
    }

    /** A change of the unit cost, with the stock at that moment. */
    public function priceChange(string $before, string $after, string $stock = '100.0000'): static
    {
        return $this->state(fn (): array => [
            'type' => StockMovementType::PriceChange,
            'quantity' => null,
            'quantity_after' => $stock,
            'unit_price' => null,
            'unit_cost_before' => $before,
            'unit_cost_after' => $after,
        ]);
    }
}
