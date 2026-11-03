<?php

declare(strict_types=1);

namespace App\Http\Requests\Orders;

use App\Enums\OrderLineMove;
use App\Enums\OrderLineSizeMode;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\OrderLineSize;
use App\Models\SizeList;
use App\Services\Orders\OrderLineSizing;
use App\Services\Orders\OrderMessages;
use App\Services\Products\SizeLists;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Move units of an order line (S-49-b, S-61-a, INV-13-e). The stage limit is not checked here: it needs the count saved under the lock,
 * so the Action checks it.
 *
 * A line counted by size, or holding several colours, sends `cells` (colour id → size id → a whole number, or empty; 0 counts as
 * empty). A cell that is not one of the line's rows is refused under `cells`, the whole window, with "The sizes ordered for <product>
 * have changed…" (a stale or forged window). A line with a single cell sends `units`, as before.
 */
final class MoveOrderLineUnitsRequest extends FormRequest
{
    /**
     * `{order}` and `{line}` are route-model bound (an unknown or other workshop's order, or a line of another order, is a 404 before
     * anything is validated), then the policy decides.
     */
    public function authorize(): Response
    {
        $order = $this->route('order');

        // The Gate's own response, so a policy's `denyAsNotFound()` stays a 404 (ADR-0013 decision 5).
        return $order instanceof Order ? Gate::inspect('update', $order) : Response::denyAsNotFound();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        if ($this->byCell()) {
            return [
                'move' => ['required', Rule::enum(OrderLineMove::class)],
                'cells' => ['nullable', 'array'],
                'cells.*' => ['nullable', 'array'],
                'cells.*.*' => ['nullable', 'integer', 'min:0'],
            ];
        }

        return [
            'move' => ['required', Rule::enum(OrderLineMove::class)],
            'units' => ['required', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $units = __('Enter a whole number of units, at least 1.');
        $whole = OrderMessages::wholeUnits();
        $messages = [];

        // A cell of the line names itself ("Indigo M: …"). They come first: the validator takes the first message that matches, and
        // `cells.*.*` also matches these keys. A key that is not a cell of the line keeps the bare message.
        foreach ($this->cellNames() as $key => $name) {
            $messages["cells.{$key}.integer"] = $messages["cells.{$key}.min"] = "{$name}: {$whole}";
        }

        return $messages + [
            'units.required' => $units,
            'units.integer' => $units,
            'units.min' => $units,
            'cells.array' => OrderMessages::atLeastOneToMove(),
            'cells.*.array' => OrderMessages::atLeastOneToMove(),
            'cells.*.*.integer' => $whole,
            'cells.*.*.min' => $whole,
        ];
    }

    /**
     * The name of each row of the line in a sentence ("Indigo M", "Black, size 38", "Camel"), keyed "<colour id>.<size id>".
     *
     * @return array<string, string>
     */
    public function cellNames(): array
    {
        if (! $this->byCell()) {
            return [];
        }

        $line = $this->orderLine();
        $oneSize = app(SizeLists::class)->list((int) $line->size_list_id)->code === SizeList::ONE_SIZE;
        $names = [];

        foreach ($this->rows() as $row) {
            $names["{$row->colour_id}.{$row->size_id}"] = OrderMessages::variant($row->colour->name, $row->size->label, $oneSize);
        }

        return $names;
    }

    /**
     * A window by cell must name only the line's own rows, and move at least one unit.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->byCell() || $validator->errors()->isNotEmpty()) {
                return;
            }

            $line = $this->orderLine();
            $known = $this->cellNames();

            foreach ($this->typed() as $key => $units) {
                if ($units >= 1 && ! isset($known[$key])) {
                    $validator->errors()->add('cells', OrderMessages::moveSizesChanged($line->product->name));

                    break;
                }
            }

            if ($validator->errors()->isEmpty() && $this->cellUnits() === []) {
                $validator->errors()->add('cells', OrderMessages::atLeastOneToMove());
            }
        }];
    }

    /** Whether the window sends `cells`: the line counts by size, or holds more than one colour (INV-13). */
    public function byCell(): bool
    {
        $line = $this->orderLine();
        $line->loadMissing('product');

        return app(OrderLineSizing::class)->forLine($line) === OrderLineSizeMode::BySize || $this->rows()->count() > 1;
    }

    /**
     * What a window by cell moves: "<colour id>:<size id>" → units, only the cells with 1 or more. Call it once the request has passed
     * (every key is then one of the line's rows).
     *
     * @return array<string, int>
     */
    public function cellUnits(): array
    {
        $units = [];

        foreach ($this->typed() as $key => $value) {
            if ($value >= 1) {
                $units[str_replace('.', ':', $key)] = $value;
            }
        }

        return $units;
    }

    /**
     * The `cells` input as whole numbers (an empty field is 0), keyed "<colour id>.<size id>" as sent.
     *
     * @return array<string, int>
     */
    private function typed(): array
    {
        $cells = $this->input('cells');
        $typed = [];

        foreach (is_array($cells) ? $cells : [] as $colourId => $sizes) {
            foreach (is_array($sizes) ? $sizes : [] as $sizeId => $value) {
                $typed["{$colourId}.{$sizeId}"] = is_scalar($value) ? (int) $value : 0;
            }
        }

        return $typed;
    }

    /** @return Collection<int, OrderLineSize> the line's rows with their colour and size, read once */
    private function rows(): Collection
    {
        $line = $this->orderLine();

        if (! $line->relationLoaded('sizes')) {
            $line->setRelation('sizes', $line->sizes()->with(['colour', 'size'])->get());
        }

        return $line->sizes;
    }

    private function orderLine(): OrderLine
    {
        $line = $this->route('line');

        return $line instanceof OrderLine ? $line : abort(404);
    }
}
