<?php

declare(strict_types=1);

namespace App\Http\Requests\Materials;

use App\Enums\MaterialCostMode;
use App\Enums\MaterialStockMode;
use App\Models\Material;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class StoreRestockRequest extends FormRequest
{
    /** Decimals fit the columns they will end up in: `quantity_on_hand` is `decimal(14,4)`, `unit_cost` is `decimal(12,4)`. */
    private const QUANTITY_RULES = ['decimal:0,4', 'gt:0', 'max:9999999999.9999'];

    private const PRICE_RULES = ['decimal:0,4', 'min:0', 'max:99999999.9999'];

    /** Books a receipt of stock: the right to update the material (MaterialPolicy). */
    public function authorize(): Response
    {
        $material = $this->route('material');

        // The Gate's own response, so a policy's `denyAsNotFound()` stays a 404 (ADR-0013 decision 5).
        return $material instanceof Material ? Gate::inspect('update', $material) : Response::denyAsNotFound();
    }

    /**
     * `update_unit_cost` is optional and means true when omitted (F-20, costing skill "Restock"). A value that is sent must still be
     * a boolean, so a typo can't silently keep the old cost.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('update_unit_cost')) {
            $this->merge(['update_unit_cost' => true]);
        }
    }

    /**
     * A one-stock material takes one `quantity` at one `unit_price`. A material kept per colour takes `lines[colourId][quantity]` for
     * each of its colours (empty = not received) and, with cost per colour, `lines[colourId][unit_price]`, else one `unit_price`; what
     * the colours' lines say is checked in {@see self::after()}, with the colour named in each message.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $receipt = [
            'received_on' => ['bail', 'required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'reference' => ['nullable', 'string', 'max:64'],
            'update_unit_cost' => ['required', 'boolean'],
        ];

        $material = $this->perColourMaterial();

        if ($material === null) {
            return ['quantity' => ['required', ...self::QUANTITY_RULES], 'unit_price' => ['required', ...self::PRICE_RULES], ...$receipt];
        }

        $lines = ['lines' => ['array'], 'lines.*.quantity' => ['nullable']];

        if ($material->cost_mode === MaterialCostMode::PerColour) {
            $lines['lines.*.unit_price'] = ['nullable'];
        } else {
            $lines['unit_price'] = $this->receivedLines() === [] ? ['nullable'] : ['required', ...self::PRICE_RULES];
        }

        return [...$lines, ...$receipt];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $material = $this->perColourMaterial();

            if ($material !== null) {
                $this->validateLines($validator, $material);
            }
        }];
    }

    /**
     * The colours must be exactly the material's now; each quantity is empty or a number above 0; at least one is typed; with cost
     * per colour each typed colour has its price. Messages name the colour, as the Restock sheet shows them under the field.
     */
    private function validateLines(Validator $validator, Material $material): void
    {
        $lines = $this->input('lines');

        if ($lines !== null && ! is_array($lines)) {
            return; // the `array` rule already said so
        }

        $lines ??= [];
        $ids = $material->colours->map(fn ($row): int => (int) $row->colour_id)->sort()->values()->all();
        $keys = array_map('intval', array_keys($lines));
        sort($keys);

        if ($keys !== $ids) {
            $validator->errors()->add('lines', __('The colours of :name have changed. Enter the quantities again.', ['name' => $material->name]));

            return;
        }

        $perColourCost = $material->cost_mode === MaterialCostMode::PerColour;
        $received = 0;

        foreach ($material->colours as $row) {
            $line = $lines[$row->colour_id];
            $colour = $row->colour->name;

            if (! $this->isFilled(is_array($line) ? ($line['quantity'] ?? null) : null)) {
                continue;
            }

            $received++;

            if (! $this->passes($line['quantity'], self::QUANTITY_RULES)) {
                $validator->errors()->add("lines.{$row->colour_id}.quantity", __(':colour: Enter a number above 0, with up to 4 decimals.', ['colour' => $colour]));
            }

            if ($perColourCost && ! $this->passes($line['unit_price'] ?? null, ['required', ...self::PRICE_RULES])) {
                $validator->errors()->add("lines.{$row->colour_id}.unit_price", __(':colour: Enter the price paid.', ['colour' => $colour]));
            }
        }

        if ($received === 0) {
            $validator->errors()->add('lines', __('Enter a quantity for at least one colour.'));
        }
    }

    /** The material of the route when its stock is kept per colour, with its colours (and their names) loaded. */
    private function perColourMaterial(): ?Material
    {
        $material = $this->route('material');

        if (! $material instanceof Material || $material->stock_mode !== MaterialStockMode::PerColour) {
            return null;
        }

        return $material->loadMissing('colours.colour');
    }

    /** @return list<int|string> the keys of `lines` whose quantity is typed */
    private function receivedLines(): array
    {
        $lines = $this->input('lines');

        return is_array($lines)
            ? array_keys(array_filter($lines, fn (mixed $line): bool => is_array($line) && $this->isFilled($line['quantity'] ?? null)))
            : [];
    }

    private function isFilled(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }

    /** @param  list<string>  $rules */
    private function passes(mixed $value, array $rules): bool
    {
        return validator(['value' => $value], ['value' => $rules])->passes();
    }
}
