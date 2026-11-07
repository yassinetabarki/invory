<?php

declare(strict_types=1);

namespace App\Http\Requests\Reference;

use App\Models\Size;
use App\Validation\WorkshopRule;
use Illuminate\Validation\Validator;

class UpdateProductCategoryRequest extends UpdateCategoryRequest
{
    protected function table(): string
    {
        return 'product_categories';
    }

    protected function parameter(): string
    {
        return 'product_category';
    }

    /**
     * INV-18-i, AC-4: `suggested_sizes` (edit only) is optional as a whole, so a rename or a move sent without it leaves the suggestion alone;
     * once the key is there it holds at least one distinct size of the workshop.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'suggested_sizes' => ['bail', 'sometimes', 'array', 'min:1'],
            'suggested_sizes.*' => ['bail', 'integer', 'distinct', WorkshopRule::exists(Size::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'suggested_sizes.array' => __('Choose at least one size.'),
            'suggested_sizes.min' => __('Choose at least one size.'),
        ];
    }

    /**
     * One size can't be combined with others here either (a stale page can send both); checked only when the list itself passed.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->has('suggested_sizes') || $validator->errors()->hasAny(['suggested_sizes', 'suggested_sizes.*'])) {
                return;
            }

            $ids = array_map(fn (mixed $id): int => (int) (is_scalar($id) ? $id : 0), $this->array('suggested_sizes'));

            if (count($ids) > 1 && Size::query()->whereIn('id', $ids)->where('is_one_size', true)->exists()) {
                $validator->errors()->add('suggested_sizes', __("One size can't be combined with other sizes. Choose it alone, or choose other sizes."));
            }
        }];
    }
}
