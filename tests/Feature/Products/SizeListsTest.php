<?php

use App\Models\ProductCategory;
use App\Models\Size;
use App\Models\SizeList;
use App\Models\User;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| The size lists and the category each one belongs to (S-58-a, AC-1, ADR-0015)
|--------------------------------------------------------------------------
|
| A product has the sizes of its category, so what is proved here is which list each category points at, and that nothing done in
| Settings (create, rename, archive, restore) moves that link.
*/

/**
 * The code, name and labels (in order) of the size list of the category with this code.
 *
 * @return array{string, string, list<string>}
 */
function sizesOfCategory(string $code): array
{
    $list = ProductCategory::query()->with('sizeList.sizes')->where('code', $code)->firstOrFail()->sizeList;

    return [$list->code, $list->name, $list->sizes->pluck('label')->all()];
}

it('gives each default category its sizes, in order', function (string $category, string $listCode, string $listName, array $labels) {
    expect(sizesOfCategory($category))->toBe([$listCode, $listName, $labels]);
})->with([
    'apparel' => ['apparel', 's_to_xl', 'S, M, L, XL', ['S', 'M', 'L', 'XL']],
    'footwear' => ['footwear', 'eu_36_to_46', '36 to 46', ['36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46']],
    'bag' => ['bag', 'one_size', 'One size', ['One size']],
    'accessory' => ['accessory', 'one_size', 'One size', ['One size']],
    'jewelry' => ['jewelry', 'one_size', 'One size', ['One size']],
    'other' => ['other', 'one_size', 'One size', ['One size']],
]);

it('stores each size once, at its own position', function () {
    $apparel = SizeList::query()->where('code', SizeList::S_TO_XL)->value('id');

    expect(SizeList::query()->count())->toBe(3)
        ->and(Size::query()->count())->toBe(4 + 11 + 1)
        ->and(Size::query()->where('size_list_id', $apparel)->orderBy('position')->pluck('position')->all())->toBe([1, 2, 3, 4]);
});

it('gives a category added in Settings the "One size" list', function () {
    actingAs(User::factory()->create())
        ->post(route('settings.product-categories.store'), ['code' => 'kids', 'name' => 'Kids'])
        ->assertSessionHasNoErrors();

    expect(sizesOfCategory('kids'))->toBe(['one_size', 'One size', ['One size']]);
});

it('keeps the sizes of Apparel through a rename, an archive and a restore', function (callable $change) {
    $apparelId = ProductCategory::query()->where('code', 'apparel')->value('id');
    $change(actingAs(User::factory()->create()), $apparelId);

    expect(sizesOfCategory('apparel'))->toBe(['s_to_xl', 'S, M, L, XL', ['S', 'M', 'L', 'XL']]);
})->with([
    'renamed Clothing' => [fn ($user, int $id) => $user->put(route('settings.product-categories.update', $id), ['name' => 'Clothing'])->assertSessionHasNoErrors()],
    'archived' => [fn ($user, int $id) => $user->patch(route('settings.product-categories.archive', $id))->assertSessionHasNoErrors()],
    'archived then restored' => [function ($user, int $id) {
        $user->patch(route('settings.product-categories.archive', $id))->assertSessionHasNoErrors();
        $user->patch(route('settings.product-categories.restore', $id))->assertSessionHasNoErrors();
    }],
]);
