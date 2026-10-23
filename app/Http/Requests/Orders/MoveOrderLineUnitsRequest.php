<?php

declare(strict_types=1);

namespace App\Http\Requests\Orders;

use App\Enums\OrderLineMove;
use App\Enums\OrderLineSizeMode;
use App\Models\Order;
use App\Models\OrderLine;
use App\Services\Orders\OrderLineSizing;
use App\Services\Orders\OrderMessages;
use App\Services\Products\SizeLists;
use App\Services\Products\SizeNames;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Move units of an order line (S-49-b, S-61-a). The stage limit is not checked here: it needs the count saved under the lock, so the
 * Action checks it.
 *
 * A line counted by size sends `sizes` (size id → a whole number, or empty; 0 counts as empty). A size that is not one of the line's
 * rows is refused under `sizes`, the whole window, with "The sizes ordered for <product> have changed…" (a stale or forged window). A line that counts as
 * one size sends `units`, as before.
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
        if ($this->bySize()) {
            return [
                'move' => ['required', Rule::enum(OrderLineMove::class)],
                'sizes' => ['nullable', 'array'],
                'sizes.*' => ['nullable', 'integer', 'min:0'],
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
        $size = OrderMessages::wholeUnits();
        $messages = [];

        // A size of the line's list names itself (S-70-a). They come first: the validator takes the first message that matches, and
        // `sizes.*` also matches these keys. A key that is not a size of the line keeps the bare message.
        foreach ($this->sizeLabels() as $id => $label) {
            $messages["sizes.{$id}.integer"] = $messages["sizes.{$id}.min"] = SizeNames::prefixed($label, $size);
        }

        return $messages + [
            'units.required' => $units,
            'units.integer' => $units,
            'units.min' => $units,
            'sizes.array' => OrderMessages::atLeastOneToMove(),
            'sizes.*.integer' => $size,
            'sizes.*.min' => $size,
        ];
    }

    /**
     * The labels of the line's size list by size id; none for a line that counts as one size. The list is the request's own cached one.
     *
     * @return array<int, string>
     */
    private function sizeLabels(): array
    {
        $line = $this->orderLine();

        return $this->bySize() && $line->size_list_id !== null
            ? app(SizeLists::class)->list($line->size_list_id)->sizes->pluck('label', 'id')->all()
            : [];
    }

    /**
     * A line counted by size must name only its own sizes, and move at least one unit.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->bySize() || $validator->errors()->isNotEmpty()) {
                return;
            }

            $line = $this->orderLine();
            $rows = $line->sizes()->pluck('size_id')->all();

            foreach ($this->typed() as $key => $units) {
                if ($units >= 1 && ! in_array($key, $rows, true)) {
                    $validator->errors()->add('sizes', OrderMessages::moveSizesChanged($line->product->name));

                    break;
                }
            }

            if ($validator->errors()->isEmpty() && $this->sizeUnits() === []) {
                $validator->errors()->add('sizes', OrderMessages::atLeastOneToMove());
            }
        }];
    }

    /** Whether the line counts its units by size, so the window sends `sizes` and not `units`. */
    public function bySize(): bool
    {
        $line = $this->orderLine();
        $line->loadMissing('product');

        return app(OrderLineSizing::class)->forLine($line) === OrderLineSizeMode::BySize;
    }

    /**
     * What a line counted by size moves: size id → units, only the sizes with 1 or more. Call it once the request has passed (every key
     * is then one of the line's sizes).
     *
     * @return array<int, int>
     */
    public function sizeUnits(): array
    {
        $units = [];

        foreach ($this->typed() as $key => $value) {
            if ($value >= 1) {
                $units[(int) $key] = $value;
            }
        }

        return $units;
    }

    /**
     * The `sizes` input as whole numbers (an empty field is 0), keyed as sent.
     *
     * @return array<int|string, int>
     */
    private function typed(): array
    {
        $sizes = $this->input('sizes');

        return array_map(static fn (mixed $value): int => is_scalar($value) ? (int) $value : 0, is_array($sizes) ? $sizes : []);
    }

    private function orderLine(): OrderLine
    {
        $line = $this->route('line');

        return $line instanceof OrderLine ? $line : abort(404);
    }
}
