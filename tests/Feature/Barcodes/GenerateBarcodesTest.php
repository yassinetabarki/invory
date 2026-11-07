<?php

use App\Actions\Barcodes\GenerateBarcodes;
use App\Domain\Barcodes\Ean13;
use App\Domain\Barcodes\NameKey;
use App\Enums\ProductStatus;
use App\Models\Barcode;
use App\Models\BarcodeName;
use App\Models\Colour;
use App\Models\Product;
use App\Models\Size;
use App\Models\User;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Generate barcodes, all or nothing (INV-8-c, AC-3, ADR-0020)
|--------------------------------------------------------------------------
|
| `POST products/{product}/barcodes` gives each asked variant (a colour and a size) the next in-store number of the workshop, in colour
| order then size order, or says it is "already used" when its name, colour and size has a number. The Denim Jacket (PRD-JKT-001) has
| Black, Indigo and Light wash here; the Canvas Low Sneaker is a footwear product and the Leather Tote a "One size" one. The barcodes the
| tests plant by hand use the factory's numbers (outside the in-store range), so they never meet a number the counter gives.
*/

const GEN_REFUSAL = "Couldn't generate the barcodes. Try again.";

beforeEach(function () {
    $this->user = prepareDemo($this);
    $this->jacket = genColours(demoProduct('PRD-JKT-001'), 'BLK', 'IND', 'LWH');
});

/** Gives the product exactly these colours (by code). */
function genColours(Product $product, string ...$codes): Product
{
    $product->colours()->syncWithPivotValues(Colour::query()->whereIn('code', $codes)->pluck('id')->all(), ['workshop_id' => $product->workshop_id]);

    return $product;
}

/**
 * The variants asked for, in the order given: `['LWH' => ['S', 'M']]` is Light wash S and M (colour code → size labels).
 *
 * @param  array<string, list<string>>  $byColour
 * @return list<array{colour_id: int, size_id: int}>
 */
function genAsk(array $byColour): array
{
    $variants = [];

    foreach ($byColour as $code => $sizes) {
        foreach ($sizes as $label) {
            $variants[] = ['colour_id' => demoColour($code)->id, 'size_id' => Size::query()->where('label', $label)->value('id')];
        }
    }

    return $variants;
}

/** The Generate request of the signed-in person. */
function genPost(Product $product, array $variants, ?User $user = null)
{
    return actingAs($user ?? test()->user)->post(route('products.barcodes.store', $product), ['variants' => $variants]);
}

/** Every barcode of the current workshop as "LWH S 2000000000015", in the order they were made. */
function genMade(): array
{
    return Barcode::query()->with(['colour', 'size'])->orderBy('id')->get()
        ->map(fn (Barcode $barcode): string => "{$barcode->colour->code} {$barcode->size->label} {$barcode->number}")->all();
}

/** A barcode of the current workshop that stands for this name (a factory number, outside the in-store range). */
function genMake(string $name, string $colourCode, string $sizeLabel): Barcode
{
    $barcode = Barcode::factory()->create(['colour_id' => demoColour($colourCode)->id, 'size_id' => Size::query()->where('label', $sizeLabel)->value('id')]);
    BarcodeName::factory()->create(['barcode_id' => $barcode->id, 'name_key' => NameKey::of($name)]);

    return $barcode;
}

/** The workshop's counter, the last serial given. */
function genCounter(): int
{
    return Workshop::query()->findOrFail(test()->user->workshop_id)->last_barcode_serial;
}

it('gives the next numbers in colour then size order, goes on where it stopped, and starts again in another workshop (AC-3)', function () {
    // Light wash S..XL in a new workshop: 2000000000015 ... 2000000000046 in size order.
    genPost($this->jacket, genAsk(['LWH' => ['XL', 'S', 'L', 'M']]))
        ->assertRedirect(route('products.show', [$this->jacket, 'tab' => 'barcodes']))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '4 barcodes generated for Denim Jacket.']);

    expect(genMade())->toBe(['LWH S 2000000000015', 'LWH M 2000000000022', 'LWH L 2000000000039', 'LWH XL 2000000000046'])
        ->and(BarcodeName::query()->where('name_key', 'denim jacket')->count())->toBe(4)
        ->and(Barcode::query()->first()->created_by)->toBe($this->user->id)
        ->and(genCounter())->toBe(4);

    // Black M and L with Indigo S..XL, sent shuffled: Black first (list order), then Indigo, each in size order; goes on at 5.
    genPost($this->jacket, genAsk(['IND' => ['L', 'S', 'XL', 'M'], 'BLK' => ['L', 'M']]))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '6 barcodes generated for Denim Jacket.']);

    expect(array_slice(genMade(), 4))->toBe([
        'BLK M '.Ean13::inStore(5), 'BLK L '.Ean13::inStore(6),
        'IND S '.Ean13::inStore(7), 'IND M '.Ean13::inStore(8), 'IND L '.Ean13::inStore(9), 'IND XL '.Ean13::inStore(10),
    ])->and(genCounter())->toBe(10);

    // One asked reads the single form, also for a shoe (size, not letters) and for "One size"; several on "One size" read the plural.
    genPost(genColours(demoProduct('PRD-SNK-003'), 'BLK'), genAsk(['BLK' => ['38']]))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Barcode generated for Black size 38.']);
    genPost(genColours(demoProduct('PRD-TOT-004'), 'CAM', 'RST'), genAsk(['CAM' => ['One size'], 'RST' => ['One size']]))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '2 barcodes generated for Leather Tote.']);
    genPost(genColours(Product::factory()->create(['sku' => 'PRD-TOT-020', 'name' => 'Beach Tote', 'category' => 'other']), 'CAM'), genAsk(['CAM' => ['One size']]))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Barcode generated for Camel.']);

    // An archived and a draft product generate like an active one.
    foreach ([[ProductStatus::Archived, 'S'], [ProductStatus::Draft, 'XL']] as [$status, $size]) {
        $this->jacket->update(['status' => $status]);
        genPost($this->jacket, genAsk(['BLK' => [$size]]))->assertInertiaFlash('toast', ['type' => 'success', 'message' => "Barcode generated for Black {$size}."]);
    }

    // Another workshop starts at 2000000000015 too, and cannot be reached from this one.
    $other = Workshop::factory()->create();
    $stranger = User::factory()->create(['workshop_id' => $other->id]);
    // Each workshop has its own sizes (INV-18): the S of the other one is read there.
    [$product, $colourId, $sizeId] = CurrentWorkshop::run($other, fn () => [
        Product::factory()->create(['sku' => 'PRD-JKT-001', 'name' => 'Denim Jacket', 'category' => 'apparel']),
        Colour::query()->where('code', 'IND')->value('id'),
        Size::query()->where('label', 'S')->value('id'),
    ]);
    $product->colours()->attach($colourId, ['workshop_id' => $other->id]);
    $mine = Workshop::query()->findOrFail($this->user->workshop_id);
    $count = Barcode::query()->count();

    genPost($product, [['colour_id' => $colourId, 'size_id' => $sizeId]], $this->user)->assertNotFound();
    expect(CurrentWorkshop::run($other, fn () => Barcode::query()->count()))->toBe(0);

    genPost($product, [['colour_id' => $colourId, 'size_id' => $sizeId]], $stranger)
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Barcode generated for Indigo S.']);

    expect(CurrentWorkshop::run($other, fn () => Barcode::query()->pluck('number')->all()))->toBe(['2000000000015'])
        ->and(CurrentWorkshop::run($mine, fn () => Barcode::query()->count()))->toBe($count);
});

it('counts the variants that already have a number as "already used", names who holds them, and makes no second barcode (AC-3)', function (array $holders, array $made, array $asked, string $message, bool $holdersDeleted = false) {
    foreach ($holders as $sku) {
        $holder = genColours(Product::factory()->create(['sku' => $sku, 'name' => 'Denim Jacket', 'category' => 'apparel']), 'LWH');
        $holdersDeleted && $holder->delete();
    }

    foreach ($made as $size) {
        genMake('Denim Jacket', 'LWH', $size);
    }

    $before = Barcode::query()->count();

    genPost($this->jacket, genAsk(['LWH' => $asked]))->assertInertiaFlash('toast', ['type' => 'success', 'message' => $message]);

    // Each name, colour and size still has exactly one number; only the variants without one were made, from the first in-store number.
    $created = count($asked) - count(array_intersect($asked, $made));

    expect(Barcode::query()->count())->toBe($before + $created)
        ->and(BarcodeName::query()->where('name_key', 'denim jacket')->count())->toBe(count(array_unique([...$made, ...$asked])))
        ->and(genCounter())->toBe($created);
})->with([
    'one holder' => [['PRD-JKT-002'], ['M'], ['S', 'M', 'L', 'XL'], '4 barcodes ready for Denim Jacket: 3 new, 1 already used by PRD-JKT-002.'],
    'two holders, named once each in SKU order, however many numbers they show' => [['PRD-JKT-007', 'PRD-JKT-002'], ['M', 'L'], ['S', 'M', 'L', 'XL'], '4 barcodes ready for Denim Jacket: 2 new, 2 already used by PRD-JKT-002 and PRD-JKT-007.'],
    'five holders' => [['PRD-JKT-010', 'PRD-JKT-007', 'PRD-JKT-009', 'PRD-JKT-002', 'PRD-JKT-008'], ['M'], ['S', 'M', 'L', 'XL'], '4 barcodes ready for Denim Jacket: 3 new, 1 already used by PRD-JKT-002, PRD-JKT-007 and 3 more.'],
    'all already made' => [['PRD-JKT-002'], ['S', 'M', 'L', 'XL'], ['S', 'M', 'L', 'XL'], '4 barcodes ready for Denim Jacket: all already used by PRD-JKT-002.'],
    'one asked and already made' => [['PRD-JKT-002'], ['S'], ['S'], 'Barcode ready for Light wash S: already used by PRD-JKT-002.'],
    'the holder deleted meanwhile, some made' => [['PRD-JKT-002'], ['M'], ['S', 'M', 'L', 'XL'], '4 barcodes ready for Denim Jacket: 3 new, 1 already used.', true],
    'the holder deleted meanwhile, all made' => [['PRD-JKT-002'], ['S', 'M', 'L', 'XL'], ['S', 'M', 'L', 'XL'], '4 barcodes ready for Denim Jacket: all already used.', true],
    'the holder deleted meanwhile, one asked' => [['PRD-JKT-002'], ['S'], ['S'], 'Barcode ready for Light wash S: already used.', true],
]);

it('saves nothing and leaves the counter as it was when anything fails (AC-3)', function () {
    $user = $this->user;
    $generate = fn () => app(GenerateBarcodes::class)->handle(demoProduct('PRD-JKT-001'), genAsk(['LWH' => ['S', 'M', 'L', 'XL']]), $user);
    $nothingSaved = fn (int $barcodes) => expect(Barcode::query()->count())->toBe($barcodes)
        ->and(BarcodeName::query()->count())->toBe(0)
        ->and(genCounter())->toBe(0);

    actingAs($user);

    // A number the workshop already holds, planted where the third barcode goes: the insert is refused.
    $planted = Barcode::factory()->create(['number' => Ean13::inStore(3), 'colour_id' => demoColour('BLK')->id]);

    expect($generate)->toThrow(QueryException::class);
    $nothingSaved(1);

    // A failure after the barcodes, their names and ahead of the counter are written: all of it is taken back.
    $planted->delete();
    $fail = true;
    DB::listen(function (QueryExecuted $query) use (&$fail): void {
        if ($fail && str_starts_with($query->sql, 'insert into "barcode_names"')) {
            throw new RuntimeException('The disk is full.');
        }
    });

    expect($generate)->toThrow(RuntimeException::class);
    $nothingSaved(0);

    // The next try starts at 2000000000015.
    $fail = false;
    $generate();

    expect(genMade())->toBe(['LWH S 2000000000015', 'LWH M 2000000000022', 'LWH L 2000000000039', 'LWH XL 2000000000046']);
});

it('refuses a request that does not ask for variants of the product, on `variants`, and saves nothing (AC-3)', function (Closure $variants) {
    $other = Workshop::factory()->create();
    $foreignColour = CurrentWorkshop::run($other, fn () => Colour::query()->where('code', 'IND')->value('id'));

    actingAs($this->user)->from(route('products.show', $this->jacket))
        ->post(route('products.barcodes.store', $this->jacket), ['variants' => $variants($foreignColour)])
        ->assertRedirect(route('products.show', $this->jacket))
        ->assertSessionHasErrors(['variants' => GEN_REFUSAL]);

    // Only `variants` is reported, whatever field was wrong; nothing is made and the counter stays.
    expect(array_keys(session('errors')->getBag('default')->messages()))->toBe(['variants'])
        ->and(Barcode::query()->count())->toBe(0)
        ->and(genCounter())->toBe(0);
})->with([
    'nothing asked' => [fn () => []],
    'a colour that is not on the product' => [fn () => genAsk(['RED' => ['S']])],
    'a colour of another workshop' => [fn (int $foreign) => [['colour_id' => $foreign, 'size_id' => Size::query()->where('label', 'S')->value('id')]]],
    'a size of another list' => [fn () => genAsk(['LWH' => ['38']])],
    'a variant twice' => [fn () => genAsk(['LWH' => ['S', 'M', 'S']])],
    'not a number' => [fn () => [['colour_id' => 'light wash', 'size_id' => Size::query()->where('label', 'S')->value('id')]]],
    'a variant that is not a pair' => [fn () => ['LWH S']],
]);

it('checks the variants again under the product\'s lock, when a colour was taken off after the request, and saves nothing (AC-3)', function () {
    actingAs($this->user);
    $stale = demoProduct('PRD-JKT-001');
    $variants = genAsk(['IND' => ['S'], 'LWH' => ['S']]);

    // The request passed with Light wash on the product; before the save it was taken off.
    $this->jacket->colours()->detach(demoColour('LWH')->id);

    expect(fn () => app(GenerateBarcodes::class)->handle($stale, $variants, $this->user))
        ->toThrow(fn (ValidationException $e) => expect($e->errors())->toBe(['variants' => [GEN_REFUSAL]]));

    expect(Barcode::query()->count())->toBe(0)
        ->and(BarcodeName::query()->count())->toBe(0)
        ->and(genCounter())->toBe(0);
});
