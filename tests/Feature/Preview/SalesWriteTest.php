<?php

use App\Http\Requests\Sales\StoreSaleRequest;
use App\Models\Material;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Record / edit / delete a sale on the preview fixture (ADR-0006, S-13c, S-04b)
|--------------------------------------------------------------------------
|
| The writes go through the final FormRequests (validation is real, the product is checked against the products table), then
| nothing is saved: an `info` toast says so. Rules: `invory-costing-domain` skill, "Validation rules".
|
| A `product_id` of 1 to 7 in a dataset is the number of a demo product (1 = Denim Jacket, 6 = the draft Chore Jacket ...):
| `saleInput()` swaps in its database id.
*/

beforeEach(fn () => $this->user = preparePreview($this));

const SALE_SAVED_TOAST = ['type' => 'info', 'message' => "Preview: the sale passed validation but wasn't saved."];

/** A valid sale payload with overrides, where a `product_id` of 1 to 7 stands for the demo product of that number. */
function saleInput(array $override = []): array
{
    if (array_key_exists('product_id', $override)) {
        $id = $override['product_id'];

        if (is_int($id) && $id >= 1 && $id <= 7) {
            $override['product_id'] = pid($id);
        } elseif (is_string($id) && ctype_digit($id) && (int) $id >= 1 && (int) $id <= 7) {
            $override['product_id'] = (string) pid((int) $id);
        } elseif ($id === [1]) {
            $override['product_id'] = [pid(1)];
        }
    }

    return validSalePayload($override);
}

dataset('invalidSaleInput', [
    'missing product' => [['product_id' => null], 'product_id'],
    'text product' => [['product_id' => 'abc'], 'product_id'],
    'decimal product' => [['product_id' => '1.5'], 'product_id'],
    'unknown product' => [['product_id' => NO_SUCH_ID], 'product_id'],
    'product zero' => [['product_id' => 0], 'product_id'],
    'a draft product' => [['product_id' => 6], 'product_id'],
    'array product' => [['product_id' => [1]], 'product_id'],
    'missing quantity' => [['quantity' => null], 'quantity'],
    'zero quantity' => [['quantity' => '0'], 'quantity'],
    'negative quantity' => [['quantity' => '-1'], 'quantity'],
    'fractional quantity' => [['quantity' => '1.5'], 'quantity'],
    'text quantity' => [['quantity' => 'three'], 'quantity'],
    'quantity over a million' => [['quantity' => '1000001'], 'quantity'],
    'missing unit price' => [['unit_price' => null], 'unit_price'],
    'negative unit price' => [['unit_price' => '-0.0001'], 'unit_price'],
    'unit price with 5 decimals' => [['unit_price' => '1.12345'], 'unit_price'],
    'text unit price' => [['unit_price' => 'free'], 'unit_price'],
    'exponent unit price' => [['unit_price' => '1e3'], 'unit_price'],
    'unit price over the column' => [['unit_price' => '100000000'], 'unit_price'],
    'missing date' => [['sold_on' => null], 'sold_on'],
    'tomorrow' => [['sold_on' => '2026-10-09'], 'sold_on'],
    'a date far in the future' => [['sold_on' => '2030-01-01'], 'sold_on'],
    'a non date' => [['sold_on' => 'yesterday'], 'sold_on'],
    'a date in the wrong format' => [['sold_on' => '08/10/2026'], 'sold_on'],
    'an impossible date' => [['sold_on' => '2026-02-30'], 'sold_on'],
    'a note over 255 characters' => [['note' => str_repeat('a', 256)], 'note'],
    'an array note' => [['note' => ['x']], 'note'],
]);

dataset('validSaleBoundaries', [
    'a million units' => [['quantity' => '1000000']],
    'one unit' => [['quantity' => '1']],
    'a free sale' => [['unit_price' => '0']],
    'four decimals' => [['unit_price' => '12.3456']],
    'integer price' => [['unit_price' => '89']],
    'the biggest price' => [['unit_price' => '99999999.9999']],
    'today' => [['sold_on' => '2026-10-08']],
    'a date in the past' => [['sold_on' => '2020-02-29']],
    'a note of 255 characters' => [['note' => str_repeat('a', 255)]],
    'no note' => [['note' => null]],
    'an empty note' => [['note' => '']],
    'the last active product' => [['product_id' => 7]],
    'a numeric string product' => [['product_id' => '3']],
]);

describe('store', function () {
    it('validates, saves nothing and flashes the preview toast', function () {
        actingAs($this->user)
            ->from(route('sales.index'))
            ->post(route('sales.store'), saleInput())
            ->assertRedirect(route('sales.index'))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', SALE_SAVED_TOAST);
    });

    it('goes back to the page it came from, with the query string', function () {
        $from = route('sales.index', ['period' => 'this_year', 'page' => 2]);

        actingAs($this->user)->from($from)->post(route('sales.store'), saleInput())->assertRedirect($from);
    });

    it('rejects invalid input with an error on the field and no toast', function (array $override, string $field) {
        $response = actingAs($this->user)
            ->from(route('sales.index'))
            ->post(route('sales.store'), saleInput($override));

        $response->assertRedirect(route('sales.index'))
            ->assertSessionHasErrors($field)
            ->assertInertiaFlashMissing('toast');
    })->with('invalidSaleInput');

    it('rejects only the invalid fields', function () {
        actingAs($this->user)
            ->post(route('sales.store'), saleInput(['quantity' => '0', 'sold_on' => '2030-01-01']))
            ->assertSessionHasErrors(['quantity', 'sold_on'])
            ->assertSessionDoesntHaveErrors(['product_id', 'unit_price', 'note']);
    });

    it('accepts the boundary values', function (array $override) {
        actingAs($this->user)
            ->post(route('sales.store'), saleInput($override))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', SALE_SAVED_TOAST);
    })->with('validSaleBoundaries');

    it('says that only active products can be sold', function () {
        actingAs($this->user)
            ->post(route('sales.store'), saleInput(['product_id' => 6]))
            ->assertSessionHasErrors(['product_id' => 'Only active products can be sold.']);
    });

    it('says an unknown product does not exist', function () {
        actingAs($this->user)
            ->post(route('sales.store'), saleInput(['product_id' => NO_SUCH_ID]))
            ->assertSessionHasErrors(['product_id' => 'The selected product id is invalid.']);
    });

    it('refuses a soft-deleted product and an archived one', function () {
        [$jeans, $belt] = [previewProduct('PRD-JNS-005'), previewProduct('PRD-BLT-007')];
        $jeans->delete();
        $belt->update(['status' => 'archived']);

        // Real ids, so `validSalePayload()` and not `saleInput()` (which reads 1 to 7 as the numbers of the plan).
        actingAs($this->user)
            ->post(route('sales.store'), validSalePayload(['product_id' => $jeans->id]))
            ->assertSessionHasErrors(['product_id' => 'The selected product id is invalid.']);

        actingAs($this->user)
            ->post(route('sales.store'), validSalePayload(['product_id' => $belt->id]))
            ->assertSessionHasErrors(['product_id' => 'Only active products can be sold.']);
    });

    it('does not change the stock of any material, nor add a row anywhere', function () {
        $stock = Material::query()->orderBy('id')->pluck('quantity_on_hand', 'sku')->all();

        actingAs($this->user)->post(route('sales.store'), saleInput(['quantity' => '1000000']));

        expect(Material::query()->orderBy('id')->pluck('quantity_on_hand', 'sku')->all())->toBe($stock)
            ->and(Material::query()->count())->toBe(12);
    });
});

describe('update', function () {
    it('validates, saves nothing and flashes the preview toast', function () {
        actingAs($this->user)
            ->from(route('sales.index'))
            ->put(route('sales.update', 57), saleInput(['quantity' => '9', 'note' => 'Edited']))
            ->assertRedirect(route('sales.index'))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', SALE_SAVED_TOAST);
    });

    it('rejects invalid input with an error on the field and no toast', function (array $override, string $field) {
        // Sale 57 is a sale of the Denim Jacket (1): the "a draft product" row is still refused (the current product is active).
        actingAs($this->user)
            ->from(route('sales.index'))
            ->put(route('sales.update', 57), saleInput($override))
            ->assertRedirect(route('sales.index'))
            ->assertSessionHasErrors($field)
            ->assertInertiaFlashMissing('toast');
    })->with('invalidSaleInput');

    it('accepts the boundary values', function (array $override) {
        actingAs($this->user)
            ->put(route('sales.update', 57), saleInput($override))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', SALE_SAVED_TOAST);
    })->with('validSaleBoundaries');

    it('lets the sale move to another active product', function () {
        actingAs($this->user)
            ->put(route('sales.update', 57), saleInput(['product_id' => 4]))
            ->assertSessionHasNoErrors();
    });

    it('keeps the current product of the sale valid even when it is no longer active', function () {
        // No fixture sale is of a draft product, so the rule is asserted on the request's rules (what the controller applies).
        [$draft, $jacket] = [pid(6), pid(1)];
        $currentIsDraft = Validator::make(saleInput(['product_id' => $draft]), StoreSaleRequest::fieldRules(currentProductId: $draft));
        $currentIsActive = Validator::make(saleInput(['product_id' => $draft]), StoreSaleRequest::fieldRules(currentProductId: $jacket));
        $createRules = Validator::make(saleInput(['product_id' => $draft]), StoreSaleRequest::fieldRules(currentProductId: null));

        expect($currentIsDraft->passes())->toBeTrue()
            // It is only the sale's own product that gets the exemption.
            ->and($currentIsActive->fails())->toBeTrue()
            ->and($currentIsActive->errors()->first('product_id'))->toBe('Only active products can be sold.')
            ->and($createRules->fails())->toBeTrue();
    });

    it('keeps the product of the edited sale valid once it was archived', function () {
        // Sale 57 is a sale of the Denim Jacket: archiving the Jacket doesn't block editing its own sale.
        previewProduct('PRD-JKT-001')->update(['status' => 'archived']);

        actingAs($this->user)->put(route('sales.update', 57), saleInput(['product_id' => 1]))->assertSessionHasNoErrors();
        actingAs($this->user)->put(route('sales.update', 57), saleInput(['product_id' => 4, 'quantity' => '2']))->assertSessionHasNoErrors();
    });

    it('still checks that the current product exists', function () {
        $rules = Validator::make(saleInput(['product_id' => NO_SUCH_ID]), StoreSaleRequest::fieldRules(currentProductId: NO_SUCH_ID));

        expect($rules->fails())->toBeTrue()
            ->and($rules->errors()->first('product_id'))->toBe('The selected product id is invalid.');
    });

    it('answers 404 for an unknown sale before it validates anything', function () {
        actingAs($this->user)->put(route('sales.update', 61), saleInput(['quantity' => '0']))
            ->assertNotFound()
            ->assertSessionHasNoErrors();
    });
});

describe('destroy', function () {
    it('deletes nothing and says so', function () {
        actingAs($this->user)
            ->from(route('sales.index', ['page' => 2]))
            ->delete(route('sales.destroy', 13))
            ->assertRedirect(route('sales.index', ['page' => 2]))
            ->assertInertiaFlash('toast', ['type' => 'info', 'message' => 'Preview: nothing was deleted.']);
    });

    it('answers 404 for an unknown sale', function () {
        actingAs($this->user)->delete(route('sales.destroy', 61))->assertNotFound();
    });
});
