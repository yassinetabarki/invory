<?php

declare(strict_types=1);

namespace App\Http\Requests\Orders;

use App\Actions\Orders\SyncOrderLines;
use App\Domain\Clients\PhoneNumber;
use App\Enums\ProductStatus;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Services\Orders\OrderMessages;
use App\Validation\ClientFieldRules;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Record an order (S-48-c). {@see UpdateOrderRequest} reuses every rule, with the products already on the order.
 */
class StoreOrderRequest extends FormRequest
{
    /** An order holds up to this many products. */
    private const MAX_LINES = 50;

    /** Most ids resolved in one query: more lines than this are refused by the `max` rule anyway. */
    private const MAX_RESOLVED_IDS = 100;

    /** A line holds up to this many units, whatever the sizes. */
    private const MAX_UNITS = 1000000;

    /** @var Collection<int, Product>|null The submitted products by id, loaded once per request. */
    private ?Collection $submittedProducts = null;

    /** @var array<int, int|string>|null The first line each submitted product appears on, by product id. */
    private ?array $firstLineOf = null;

    public function authorize(): bool|Response
    {
        return $this->user()?->can('create', Order::class) ?? false;
    }

    /**
     * The Client card (INV-4, AC-5): a phone always; Name and City only when no client of the workshop has that phone, since a known
     * phone uses the saved client unchanged (the other fields are then ignored). {@see UpdateOrderRequest} has its own client rule.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Missing, the card's three required fields each say so ("Check the client: 3 fields can't be saved.").
            'client' => ['nullable', 'array'],
            ...ClientFieldRules::rules('client.', newClient: ! $this->phoneIsKnown()),
            'ordered_on' => ['bail', 'required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:1000'],
            'lines' => ['bail', 'required', 'array', 'min:1', 'max:'.self::MAX_LINES],
            'lines.*.product_id' => ['bail', 'required', $this->orderableProduct()],
            // INV-13: the units of a line per colour and size (colour id → size id → as typed); `after()` checks them against the
            // product's colours and sizes. These rules only keep the fields in `validated()`.
            'lines.*.units' => ['nullable'],
            'lines.*.units.*' => ['nullable'],
            'lines.*.units.*.*' => ['nullable'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['ordered_on' => 'order date', 'lines.*.product_id' => 'product'];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'client.array' => __("Enter the client's phone."),
            ...ClientFieldRules::messages('client.'),
            'ordered_on.before_or_equal' => __("The order date can't be in the future."),
            'lines.required' => OrderMessages::noProduct(),
            'lines.min' => OrderMessages::noProduct(),
            'lines.max' => __('An order can hold up to :max products.', ['max' => self::MAX_LINES]),
            'lines.*.product_id.required' => __('Choose a product.'),
        ];
    }

    /**
     * The units of every line, once the lines are known (INV-13): `units`, colour id → size id → as typed.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach ($this->submittedLines() as $key => $line) {
                $id = is_array($line) ? self::productId($line['product_id'] ?? null) : null;
                $product = $id === null ? null : $this->submittedProducts()->get($id);

                // A line without a valid product is refused on its product already.
                if ($product !== null) {
                    $this->checkUnits($validator, "lines.{$key}", $line, $product);
                }
            }
        }];
    }

    /** True when the typed phone is valid and already the phone of a client of the workshop. */
    private function phoneIsKnown(): bool
    {
        $phone = $this->input('client.phone');
        $parsed = is_string($phone) ? PhoneNumber::parse($phone) : null;

        return $parsed !== null && Client::query()->where('phone_key', $parsed->key)->exists();
    }

    /**
     * The order's saved line of this product, with its `product` and `sizes` loaded: it decides how the line counts and which colours
     * it may keep. None for a new order.
     */
    protected function savedLine(int $productId): ?OrderLine
    {
        return null;
    }

    /**
     * The products this order already has: they stay valid when no longer active or deleted. None for a new order.
     *
     * @return array<int, int>
     */
    protected function currentProductIds(): array
    {
        return [];
    }

    /**
     * The product exists in the workshop (a deleted one only when it is already on the order), is active (or already on the order),
     * and is not on an earlier line. Every message goes on the line it is about: the later line of a repeated product.
     */
    private function orderableProduct(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $id = self::productId($value);
            $product = $id === null ? null : $this->submittedProducts()->get($id);
            $current = $id !== null && in_array($id, $this->currentProductIds(), true);

            if ($product === null || ($product->trashed() && ! $current)) {
                $fail('validation.exists')->translate();
            } elseif ($product->status !== ProductStatus::Active && ! $current) {
                $fail(__('Only active products can be ordered.'));
            } elseif ($this->isRepeated($attribute, $product->id)) {
                $fail(__(':product is already on this order.', ['product' => $product->name]));
            }
        };
    }

    /** True when an earlier line of the submitted order has this product: the line named by `$attribute` is then the repeat. */
    private function isRepeated(string $attribute, int $productId): bool
    {
        if ($this->firstLineOf === null) {
            $this->firstLineOf = [];

            foreach ($this->submittedLines() as $key => $line) {
                $id = self::productId(is_array($line) ? ($line['product_id'] ?? null) : null);

                if ($id !== null) {
                    $this->firstLineOf[$id] ??= $key;
                }
            }
        }

        return isset($this->firstLineOf[$productId]) && (string) $this->firstLineOf[$productId] !== explode('.', $attribute)[1];
    }

    /**
     * Every submitted product in one query (an order can hold 50 lines), soft-deleted ones included. The workshop scope hides
     * another workshop's products: they are simply not found.
     *
     * @return Collection<int, Product>
     */
    private function submittedProducts(): Collection
    {
        if ($this->submittedProducts === null) {
            $ids = [];

            foreach ($this->submittedLines() as $line) {
                $id = self::productId(is_array($line) ? ($line['product_id'] ?? null) : null);

                if ($id !== null) {
                    $ids[$id] = $id;
                }
            }

            $this->submittedProducts = $ids === []
                ? new Collection
                : Product::query()->withTrashed()->whereKey(array_slice(array_values($ids), 0, self::MAX_RESOLVED_IDS))
                    ->get(['id', 'name', 'status', 'category_id', 'deleted_at'])->keyBy('id');
        }

        return $this->submittedProducts;
    }

    /**
     * The units of one line (INV-13): `units`, colour id → size id → as typed. Every colour must be one of the product's (or, on Edit
     * order, one the line already holds) and every size one of the line's sizes (the product's), else the form was built for other colours or sizes and
     * the line is refused as a whole. Each cell is empty or a whole number from 0 to 1,000,000 ("Indigo M: Enter a whole number from 0
     * to 1,000,000."), then the line's total runs from 1 to 1,000,000.
     *
     * @param  array<string, mixed>  $line
     */
    private function checkUnits(Validator $validator, string $field, array $line, Product $product): void
    {
        $saved = $this->savedLine($product->id);
        [, $sizes] = app(SyncOrderLines::class)->counting($product, $saved);
        $colours = app(SyncOrderLines::class)->allowedColours($product, $saved);
        $oneSize = $sizes->oneSize;
        $labels = $sizes->sizes->pluck('label', 'id')->all();
        $units = $line['units'] ?? null;
        $total = 0;
        $foreign = ! is_array($units);
        $failed = false;

        foreach (is_array($units) ? $units : [] as $colourId => $sizes) {
            if (! isset($colours[$colourId]) || ! is_array($sizes)) {
                $foreign = true;

                continue;
            }

            foreach ($sizes as $sizeId => $value) {
                if (! array_key_exists($sizeId, $labels)) {
                    $foreign = true;
                } elseif ($value !== null && $value !== '') {
                    if (self::wholeNumber($value, 0)) {
                        $total += (int) $value;
                    } else {
                        $failed = true;
                        $variant = OrderMessages::variant($colours[$colourId], $labels[$sizeId], $oneSize);
                        $validator->errors()->add("{$field}.units.{$colourId}.{$sizeId}", "{$variant}: ".OrderMessages::sizeQuantity());
                    }
                }
            }
        }

        if ($foreign) {
            $validator->errors()->add("{$field}.units", OrderMessages::orderSizesChanged($product->name));
        } elseif (! $failed && $total < 1) {
            $validator->errors()->add("{$field}.units", OrderMessages::atLeastOneUnit($product->name));
        } elseif (! $failed && $total > self::MAX_UNITS) {
            $validator->errors()->add("{$field}.units", OrderMessages::lineTooBig());
        }
    }

    /** A whole number (an int, or its digits as typed) from `$min` to 1,000,000. */
    private static function wholeNumber(mixed $value, int $min): bool
    {
        $number = is_int($value) || is_string($value) ? filter_var($value, FILTER_VALIDATE_INT) : false;

        return $number !== false && $number >= $min && $number <= self::MAX_UNITS;
    }

    /** @return array<int|string, mixed> */
    private function submittedLines(): array
    {
        $lines = $this->input('lines');

        return is_array($lines) ? $lines : [];
    }

    /** A product id as sent: a whole number (an int, or up to 18 digits). Anything else never reaches the database. */
    private static function productId(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        return is_string($value) && preg_match('/^[0-9]{1,18}$/', $value) === 1 && (int) $value > 0 ? (int) $value : null;
    }
}
