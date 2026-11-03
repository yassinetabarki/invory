<?php

declare(strict_types=1);

namespace App\Http\Requests\Orders;

use App\Enums\OrderUnitsChange;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\OrderLineSize;
use App\Models\SizeList;
use App\Services\Orders\OrderMessages;
use App\Services\Products\SizeLists;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * The cells of Mark delivered, its move back and Cancel units (INV-14-d): `cells`, colour id → size id → a whole number of units or
 * empty (0 counts as empty), and at least one unit in all. The limits need the counts and the shelf read under the locks, so the
 * Action checks them; a cell that is no longer a row of the line is refused there too ("… changed since you opened this page.").
 */
final class OrderCellsRequest extends FormRequest
{
    /** @var Collection<int, OrderLineSize>|null */
    private ?Collection $rows = null;

    /**
     * `{order}` and `{line}` are route-model bound with scoped bindings (an unknown or other workshop's order, or a line of another
     * order, is a 404 before anything is validated), then the policy decides.
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
        return [
            'cells' => ['nullable', 'array'],
            'cells.*' => ['nullable', 'array'],
            'cells.*.*' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $messages = [];

        // A row of the line names itself ("Indigo S: Enter a whole number of units."). They come first: the validator takes the first
        // message that matches, and `cells.*.*` matches these keys too.
        foreach ($this->rows() as $row) {
            $variant = OrderMessages::variant($row->colour->name, $row->size->label, $this->oneSize());
            $messages["cells.{$row->colour_id}.{$row->size_id}.integer"] = $messages["cells.{$row->colour_id}.{$row->size_id}.min"] = OrderMessages::wholeUnits($variant);
        }

        $any = OrderMessages::atLeastOne($this->change()->verb());

        return $messages + [
            'cells.array' => $any,
            'cells.*.array' => $any,
            'cells.*.*.integer' => __('Enter a whole number of units.'),
            'cells.*.*.min' => __('Enter a whole number of units.'),
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isEmpty() && $this->cellUnits() === []) {
                $validator->errors()->add('cells', OrderMessages::atLeastOne($this->change()->verb()));
            }
        }];
    }

    /** Which window posted: from the route's name. */
    public function change(): OrderUnitsChange
    {
        return match ($this->route()?->getName()) {
            'orders.lines.deliveries.destroy' => OrderUnitsChange::MoveBack,
            'orders.lines.cancellations.store' => OrderUnitsChange::Cancel,
            default => OrderUnitsChange::Deliver,
        };
    }

    /**
     * What the window moves: "<colour id>:<size id>" → units, only the cells with 1 or more.
     *
     * @return array<string, int>
     */
    public function cellUnits(): array
    {
        $cells = $this->input('cells');
        $units = [];

        foreach (is_array($cells) ? $cells : [] as $colourId => $sizes) {
            foreach (is_array($sizes) ? $sizes : [] as $sizeId => $value) {
                $count = is_scalar($value) ? (int) $value : 0;

                if ($count >= 1) {
                    $units["{$colourId}:{$sizeId}"] = $count;
                }
            }
        }

        return $units;
    }

    /** @return Collection<int, OrderLineSize> */
    private function rows(): Collection
    {
        return $this->rows ??= $this->orderLine()->sizes()->with(['colour', 'size'])->get();
    }

    private function oneSize(): bool
    {
        return app(SizeLists::class)->list((int) $this->orderLine()->size_list_id)->code === SizeList::ONE_SIZE;
    }

    private function orderLine(): OrderLine
    {
        $line = $this->route('line');

        return $line instanceof OrderLine ? $line : abort(404);
    }
}
