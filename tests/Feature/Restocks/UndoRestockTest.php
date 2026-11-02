<?php

use App\Models\ProductRestock;
use App\Models\SizeList;
use App\Models\User;
use App\Models\Workshop;
use App\Services\Restocks\UndoCheck;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Undo restock (INV-3-c, AC-6)
|--------------------------------------------------------------------------
|
| `POST restocks/{restock}/undo` takes the whole restock back off the shelf while every colour and size it added still holds at least
| that many units; the restock stays, marked undone. The card's catalogue and its 34-unit restock (Denim Jacket 24, Denim Jeans
| 5-pocket 10) are recorded through the real route first.
*/

const JACKET_BEFORE = ['Black' => ['S' => 1, 'L' => 2, 'XL' => 1], 'Indigo' => ['S' => 2, 'L' => 3, 'XL' => 1]];

beforeEach(function () {
    $this->user = prepareDemo($this);
    ['jacket' => $this->jacket, 'jeans' => $this->jeans, 'tote' => $this->tote] = restockCatalogue();

    actingAs($this->user)->post(route('restocks.store'), restockBody([
        restockLine($this->jacket, ['Indigo' => ['S' => 4, 'M' => 8, 'L' => 6, 'XL' => 2], 'Black' => ['M' => 4]]),
        restockLine($this->jeans, ['Indigo' => ['M' => 6, 'L' => 4]]),
    ], ['reference' => 'BL-0412']))->assertSessionHasNoErrors();

    $this->restock = ProductRestock::query()->sole();
});

it('takes every unit back off the shelf at once and marks the restock undone (AC-6)', function () {
    $sami = User::factory()->create(['name' => 'Sami']);
    $this->travel(1)->days();

    actingAs($sami)
        ->from(route('restocks.index', ['restock' => $this->restock->id]))
        ->post(route('restocks.undo', $this->restock))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('restocks.index', ['restock' => $this->restock->id]))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Restock undone.']);

    expect(shelfByColour($this->jacket))->toBe(JACKET_BEFORE)
        ->and(shelfByColour($this->jeans))->toBe([]);

    $undone = $this->restock->fresh();
    expect($undone->undone_by)->toBe($sami->id)
        ->and($undone->undone_at?->toDateString())->toBe('2026-10-09')
        ->and($undone->units)->toBe(34)
        ->and($undone->lines()->count())->toBe(7);
});

it('refuses, saying which variant is short, when the shelf holds fewer than the restock added; nothing changes (AC-6)', function () {
    setShelf($this->jacket, ['Indigo' => ['M' => 2]]);
    $message = "This restock can't be undone: Denim Jacket in Indigo, size M, has 2 units on the shelf, fewer than the 8 it added. Set the right count with Update stock instead.";

    expect(app(UndoCheck::class)->refusal($this->restock))->toBe($message);

    actingAs($this->user)
        ->post(route('restocks.undo', $this->restock))
        ->assertSessionHasErrors(['undo' => $message]);

    expect($this->restock->fresh()->undone_at)->toBeNull()
        ->and(shelfByColour($this->jacket)['Indigo'])->toBe(['S' => 6, 'M' => 2, 'L' => 9, 'XL' => 3])
        ->and(shelfByColour($this->jeans))->toBe(['Indigo' => ['M' => 6, 'L' => 4]]);
});

it('names the first short variant in order, and "1 unit" for one', function () {
    // The jeans come second on the restock, so the jacket's Black M is named first.
    setShelf($this->jeans, ['Indigo' => ['M' => 1]]);
    DB::table('product_size_stocks')->where('product_id', $this->jacket->id)->where('colour_id', demoColour('BLK')->id)
        ->where('size_id', array_key_first(sizeIds(['M' => 0])))->delete();

    expect(app(UndoCheck::class)->refusal($this->restock))
        ->toBe("This restock can't be undone: Denim Jacket in Black, size M, has 0 units on the shelf, fewer than the 4 it added. Set the right count with Update stock instead.");

    setShelf($this->jacket, ['Black' => ['M' => 4]]);

    expect(app(UndoCheck::class)->refusal($this->restock))
        ->toBe("This restock can't be undone: Denim Jeans 5-pocket in Indigo, size M, has 1 unit on the shelf, fewer than the 6 it added. Set the right count with Update stock instead.");
});

it('names a "One size" product by its colour alone', function () {
    actingAs($this->user)->post(route('restocks.store'), restockBody([restockLine($this->tote, ['Camel' => ['One size' => 6]])]))->assertSessionHasNoErrors();
    $tote = ProductRestock::query()->latest('id')->firstOrFail();
    setShelf($this->tote, ['Camel' => ['One size' => 5]], SizeList::ONE_SIZE);

    expect(app(UndoCheck::class)->refusal($tote))
        ->toBe("This restock can't be undone: Leather Tote in Camel has 5 units on the shelf, fewer than the 6 it added. Set the right count with Update stock instead.");
});

it('can\'t be undone once a colour it added to was taken off (its stock is then 0)', function () {
    DB::table('product_size_stocks')->where('product_id', $this->jacket->id)->where('colour_id', demoColour('BLK')->id)->delete();
    $this->jacket->colours()->detach(demoColour('BLK')->id);

    actingAs($this->user)->post(route('restocks.undo', $this->restock))
        ->assertSessionHasErrors(['undo' => "This restock can't be undone: Denim Jacket in Black, size M, has 0 units on the shelf, fewer than the 4 it added. Set the right count with Update stock instead."]);
});

it('refuses a second undo', function () {
    actingAs($this->user)->post(route('restocks.undo', $this->restock))->assertSessionHasNoErrors();

    actingAs($this->user)->post(route('restocks.undo', $this->restock))
        ->assertSessionHasErrors(['undo' => 'This restock was already undone.']);

    expect(shelfByColour($this->jacket))->toBe(JACKET_BEFORE)
        ->and(app(UndoCheck::class)->refusal($this->restock->fresh()))->toBeNull();
});

it('skips a product deleted since and takes the other products\' units back (AC-6)', function () {
    $this->jacket->delete();

    expect(app(UndoCheck::class)->refusal($this->restock))->toBeNull();

    actingAs($this->user)->post(route('restocks.undo', $this->restock))->assertSessionHasNoErrors();

    expect(shelfByColour($this->jeans))->toBe([])
        ->and($this->restock->fresh()->undone_at)->not->toBeNull();
});

it('answers 404 for another workshop\'s restock', function () {
    $theirs = CurrentWorkshop::run(Workshop::factory()->create(), fn (): ProductRestock => ProductRestock::factory()->create());

    actingAs($this->user)->post(route('restocks.undo', $theirs->id))->assertNotFound();
    actingAs($this->user)->post('/restocks/abc/undo')->assertNotFound();
});
