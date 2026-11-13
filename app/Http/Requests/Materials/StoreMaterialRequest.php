<?php

declare(strict_types=1);

namespace App\Http\Requests\Materials;

use App\Enums\MaterialCostMode;
use App\Enums\MaterialStockMode;
use App\Models\Colour;
use App\Models\Material;
use App\Services\Materials\MaterialStockSentences;
use App\Services\Reference\ReferenceCode;
use App\Validation\WorkshopRule;
use Brick\Math\BigDecimal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Validator;

class StoreMaterialRequest extends FormRequest
{
    /** The largest balance `materials.quantity_on_hand` (decimal(14,4)) can hold: the total of the colours must fit it too. */
    private const MAX_STOCK = '9999999999.9999';

    public function authorize(): bool
    {
        return $this->user()?->can('create', Material::class) ?? false;
    }

    /** `stock_mode` and `cost_mode` are optional: today's payloads are one stock with the same cost. */
    protected function prepareForValidation(): void
    {
        self::defaultModes($this, MaterialStockMode::Single, MaterialCostMode::Same);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return self::fieldRules(
            WorkshopRule::unique(Material::class, 'sku'),
            stockMode: $this->string('stock_mode')->value(),
            costMode: $this->string('cost_mode')->value(),
        );
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return self::fieldMessages();
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [fn (Validator $validator) => self::colourRows($this, $validator)];
    }

    /**
     * Fills `stock_mode` and `cost_mode` when the request has none (or an empty one).
     */
    public static function defaultModes(FormRequest $request, MaterialStockMode $stockMode, MaterialCostMode $costMode): void
    {
        foreach (['stock_mode' => $stockMode->value, 'cost_mode' => $costMode->value] as $key => $default) {
            if (blank($request->input($key))) {
                $request->merge([$key => $default]);
            }
        }
    }

    /**
     * Rules shared with UpdateMaterialRequest.
     *
     * `category` and `unit` are codes of non-archived rows (ADR-0008). On update, the material's own current code stays
     * valid even when it has been archived since.
     *
     * Stock per colour (INV-21): the `colours` rows replace `quantity_on_hand` (the total is theirs), and with cost per colour
     * `unit_cost` too; their quantity and cost are checked by {@see self::colourRows()}, which names the colour in each message.
     *
     * @return array<string, mixed>
     */
    public static function fieldRules(Unique $uniqueSku, ?string $currentCategory = null, ?string $currentUnit = null, string $stockMode = 'single', string $costMode = 'same'): array
    {
        $perColour = $stockMode === MaterialStockMode::PerColour->value;

        $rules = [
            'sku' => ['required', 'string', 'max:64', $uniqueSku],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', ReferenceCode::active('material_categories', $currentCategory)],
            'unit' => ['required', 'string', ReferenceCode::active('units', $currentUnit)],
            'stock_mode' => ['required', Rule::enum(MaterialStockMode::class)],
            // One stock is always the same cost.
            'cost_mode' => ['required', $perColour ? Rule::enum(MaterialCostMode::class) : Rule::in([MaterialCostMode::Same->value])],
            'unit_cost' => ['required', 'decimal:0,4', 'min:0', 'max:99999999.9999'],
            'quantity_on_hand' => ['required', 'decimal:0,4', 'min:0', 'max:9999999999.9999'],
            'reorder_level' => ['nullable', 'decimal:0,4', 'min:0', 'max:9999999999.9999'],
            'supplier_name' => ['nullable', 'string', 'max:150'],
            'supplier_reference' => ['nullable', 'string', 'max:64'],
            'color' => ['nullable', 'string', 'max:64'],
            'composition' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            // Not columns: the Actions hand `photo` and `remove_photo` to PhotoStorage, which stores or removes the file.
            'photo' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(4096)],
            'remove_photo' => ['nullable', 'boolean'],
        ];

        if (! $perColour) {
            return $rules;
        }

        unset($rules['quantity_on_hand']);

        if ($costMode === MaterialCostMode::PerColour->value) {
            unset($rules['unit_cost']);
        }

        return $rules + [
            'colours' => ['bail', 'required', 'array', 'min:1'],
            'colours.*' => ['bail', 'array'],
            'colours.*.colour_id' => ['bail', 'required', 'integer', 'distinct', WorkshopRule::exists(Colour::class)],
            // Only listed so that they are validated data: colourRows() checks them.
            'colours.*.quantity_on_hand' => ['nullable'],
            'colours.*.unit_cost' => ['nullable'],
        ];
    }

    /** @return array<string, string> */
    public static function fieldMessages(): array
    {
        $none = __('Add at least one colour, or choose One stock for all colours.');

        return ['colours.required' => $none, 'colours.array' => $none, 'colours.min' => $none];
    }

    /**
     * The quantity (and, with cost per colour, the unit cost) of each colour row, with its message led by the colour's name
     * ("Black: Enter a unit cost."), on `colours.{i}.quantity_on_hand` / `colours.{i}.unit_cost`. Shared with UpdateMaterialRequest.
     */
    public static function colourRows(FormRequest $request, Validator $validator): void
    {
        $rows = $request->input('colours');

        if ($request->input('stock_mode') !== MaterialStockMode::PerColour->value || ! is_array($rows)) {
            return;
        }

        $costPerColour = $request->input('cost_mode') === MaterialCostMode::PerColour->value;
        $ids = [];

        foreach ($rows as $row) {
            if (is_array($row) && is_scalar($row['colour_id'] ?? null) && ctype_digit((string) $row['colour_id'])) {
                $ids[] = (int) $row['colour_id'];
            }
        }

        $names = Colour::query()->whereIn('id', $ids)->pluck('name', 'id');
        $number = __('Enter a number from 0, with up to 4 decimals.');
        $total = BigDecimal::zero();

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $id = is_scalar($row['colour_id'] ?? null) ? (int) $row['colour_id'] : 0;
            $lead = $names->has($id) ? $names->get($id).': ' : '';
            $quantity = self::decimal($row['quantity_on_hand'] ?? null, 10);

            if ($quantity === null) {
                $validator->errors()->add("colours.{$index}.quantity_on_hand", $lead.$number);
            } else {
                $total = $total->plus($quantity);
            }

            if ($costPerColour) {
                $given = $row['unit_cost'] ?? null;

                if ($given === null || $given === '') {
                    $validator->errors()->add("colours.{$index}.unit_cost", $lead.__('Enter a unit cost.'));
                } elseif (self::decimal($given, 8) === null) {
                    $validator->errors()->add("colours.{$index}.unit_cost", $lead.$number);
                }
            }
        }

        if ($total->isGreaterThan(self::MAX_STOCK)) {
            $validator->errors()->add('colours', MaterialStockSentences::overflow(self::MAX_STOCK));
        }
    }

    /** A number from 0 with at most 4 decimals and `$whole` whole digits, or null. */
    private static function decimal(mixed $value, int $whole): ?BigDecimal
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        return preg_match('/^\d{1,'.$whole.'}(\.\d{1,4})?$/', (string) $value) === 1 ? BigDecimal::of((string) $value) : null;
    }
}
