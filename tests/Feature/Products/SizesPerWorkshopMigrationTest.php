<?php

use App\Actions\Sizes\CreateDefaultSizes;
use App\Models\Barcode;
use App\Models\BarcodeName;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductionBatch;
use App\Models\ProductRestockLine;
use App\Models\ProductSizeStock;
use App\Models\Size;
use App\Models\Workshop;
use App\Services\Production\TicketSheet;
use App\Services\Products\ProductSizes;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Sizes per workshop and per product: the migration on data you already have (INV-18-c, AC-3, ADR-0028)
|--------------------------------------------------------------------------
|
| Two workshops with a Denim Jacket (XS to XXXL), a Footwear product (36 to 46) and a "One size" Leather Tote, each with stock, order
| lines (by size, before sizes, one size), a barcode with its name, a restock line, a batch In production and its ticket. The migration
| is rolled back (the three size lists, every record of both workshops on the oldest workshop's rows) and run forward again on that old
| shape: nothing moves, in either workshop.
*/

beforeEach(function () {
    $this->user = prepareDemo($this);
    $this->first = Workshop::query()->findOrFail($this->user->workshop_id);
    $this->second = Workshop::factory()->create();
});

/** The tables that point at `sizes.id`. */
const SIZE_TABLES = ['product_size_stocks', 'order_line_sizes', 'barcodes', 'barcode_names', 'product_restock_lines', 'production_batches'];

/**
 * A Denim Jacket, a Footwear product and a Leather Tote with every kind of record, as the workshop's own sizes: run as that workshop.
 *
 * @return array{jacket: Product, sneaker: Product, tote: Product}
 */
function plantSizedRecords(Workshop $workshop): array
{
    return CurrentWorkshop::run($workshop, function () use ($workshop): array {
        $demo = $workshop->id === (int) DB::table('workshops')->min('id');
        $jacket = $demo ? demoProduct('PRD-JKT-001') : Product::factory()->create(['sku' => 'PRD-JKT-001', 'name' => 'Denim Jacket', 'category' => 'apparel']);
        $sneaker = $demo ? demoProduct('PRD-SNK-003') : Product::factory()->create(['sku' => 'PRD-SNK-003', 'name' => 'Canvas Low Sneaker', 'category' => 'footwear']);
        $tote = $demo ? demoProduct('PRD-TOT-004') : Product::factory()->create(['sku' => 'PRD-TOT-004', 'name' => 'Leather Tote', 'category' => 'bag']);
        $indigo = demoColour('IND');

        // Stock: the jacket in Indigo S and L, the sneaker in 40, the tote in its one size.
        setFinishedStock($jacket, ['Indigo' => [0, 2, 0, 5, 0, 0, 0]]);
        ProductSizeStock::factory()->create(['product_id' => $sneaker->id, 'size_id' => sizeId('40', $sneaker), 'units' => 3]);
        ProductSizeStock::factory()->create(['product_id' => $tote->id, 'units' => 4]);

        // Orders: by size, before sizes (a One size row on a product with sizes) and "One size".
        OrderLine::factory()->sized(['S' => 10, 'M' => 5], ['M' => [2, 1]])->create(['product_id' => $jacket->id]);
        OrderLine::factory()->sized(['One size' => 4])->create(['product_id' => $jacket->id]);
        OrderLine::factory()->create(['product_id' => $tote->id, 'quantity' => 6]);

        // A barcode and its name for Indigo M, a restock line and a batch In production.
        $barcode = Barcode::factory()->create(['colour_id' => $indigo->id, 'size_id' => sizeId('M', $jacket)]);
        BarcodeName::factory()->create(['barcode_id' => $barcode->id, 'name_key' => 'denim jacket']);
        ProductRestockLine::factory()->create(['product_id' => $jacket->id, 'colour_id' => $indigo->id, 'size_id' => sizeId('L', $jacket), 'units' => 5]);
        ProductionBatch::factory()->create(['product_id' => $jacket->id, 'colour_id' => $indigo->id, 'size_id' => sizeId('S', $jacket), 'units' => 3]);

        return ['jacket' => $jacket, 'sneaker' => $sneaker, 'tote' => $tote];
    });
}

/**
 * Everything the workshop reads by size, in words (labels and codes, never ids): what the screens would show. Run as the workshop, so a
 * record that pointed at another workshop's size would read no label.
 *
 * @return array<string, mixed>
 */
function sizedSnapshot(Workshop $workshop): array
{
    return CurrentWorkshop::run($workshop, function (): array {
        $rows = fn ($query, Closure $map): array => $query->get()->map($map)->sort()->values()->all();
        $batchIds = ProductionBatch::query()->orderBy('id')->pluck('id')->all();

        return [
            'sizes' => Product::query()->with('sizes')->orderBy('sku')->get()->mapWithKeys(fn (Product $product): array => [$product->sku => $product->sizes->pluck('label')->all()])->all(),
            'stock' => $rows(ProductSizeStock::query()->with(['product', 'colour', 'size']), fn ($row): string => "{$row->product->sku} {$row->colour->code} {$row->size?->label} {$row->units}"),
            'order lines' => $rows(OrderLine::query()->with(['product', 'sizes.colour', 'sizes.size']), fn (OrderLine $line): string => "{$line->product->sku} ".($line->by_size ? 'by size' : 'one size')." {$line->quantity}/{$line->delivered}/{$line->cancelled} "
                .$line->sizes->map(fn ($row): string => "{$row->colour->code} {$row->size?->label} {$row->quantity}/{$row->delivered}/{$row->cancelled}")->implode(', ')),
            'barcodes' => $rows(Barcode::query()->with(['colour', 'size', 'names']), fn (Barcode $barcode): string => "{$barcode->number} {$barcode->colour->code} {$barcode->size?->label} ".$barcode->names->map(fn (BarcodeName $name): string => $name->name_key.' '.Size::query()->whereKey($name->size_id)->value('label'))->implode(', ')),
            'restock lines' => $rows(ProductRestockLine::query()->with(['product', 'colour', 'size']), fn ($line): string => "{$line->product->sku} {$line->colour->code} {$line->size?->label} {$line->units}"),
            'batches' => $rows(ProductionBatch::query()->with(['product', 'colour', 'size']), fn ($batch): string => "{$batch->label} {$batch->product->sku} {$batch->colour->code} {$batch->size?->label} {$batch->units}"),
            'tickets' => array_map(fn (array $ticket): string => "{$ticket['number']} {$ticket['variant']} {$ticket['sku']}", app(TicketSheet::class)->tickets($batchIds)),
        ];
    });
}

/** The labels of a workshop's list, in list order, and their positions. */
function sizeList(Workshop $workshop): array
{
    return CurrentWorkshop::run($workshop, fn (): array => Size::query()->ordered()->get()->mapWithKeys(fn (Size $size): array => [$size->label => $size->position])->all());
}

it('keeps every product its sizes and every record its size in both workshops, and numbers each workshop\'s list 10 to 190 (AC-3)', function () {
    plantSizedRecords($this->first);
    plantSizedRecords($this->second);
    $before = [$this->first->id => sizedSnapshot($this->first), $this->second->id => sizedSnapshot($this->second)];

    sizesPerWorkshopMigration()->down();

    // The old shape: three lists, one set of sizes, every record of both workshops on it; a line recorded by size sits on its list.
    expect(Schema::hasTable('product_sizes'))->toBeFalse()
        ->and(DB::table('size_lists')->orderBy('id')->pluck('name', 'code')->all())->toBe(['s_to_xl' => 'XS to XXXL', 'eu_36_to_46' => '36 to 46', 'one_size' => 'One size'])
        ->and(DB::table('sizes')->count())->toBe(19)
        ->and(DB::table('order_lines')->join('size_lists', 'size_lists.id', '=', 'order_lines.size_list_id')->orderBy('order_lines.id')->pluck('size_lists.code')->all())
        ->toBe(['s_to_xl', 'one_size', 'one_size', 's_to_xl', 'one_size', 'one_size']);

    sizesPerWorkshopMigration()->up();

    foreach ([$this->first, $this->second] as $workshop) {
        // The same words in both workshops, on each workshop's own rows.
        expect(sizedSnapshot($workshop))->toBe($before[$workshop->id]);

        $labels = array_map('strval', array_keys(sizeList($workshop)));
        expect($labels)->toBe([...CreateDefaultSizes::DEFAULTS])
            ->and(array_values(sizeList($workshop)))->toBe(range(10, 190, 10))
            ->and(CurrentWorkshop::run($workshop, fn () => Size::query()->where('is_one_size', true)->pluck('label')->all()))->toBe(['One size']);
    }

    // Every record points at a size of its own workshop, and the two workshops share no row.
    foreach (SIZE_TABLES as $table) {
        expect(DB::table($table)->join('sizes', 'sizes.id', '=', "{$table}.size_id")->whereColumn('sizes.workshop_id', '<>', "{$table}.workshop_id")->count())->toBe(0, $table);
    }

    expect(DB::table('sizes')->count())->toBe(38)
        ->and(DB::table('sizes')->where('workshop_id', $this->first->id)->count())->toBe(19);
});

it('gives each product the sizes of its category, in order, one size for the tote', function (string $sku, array $labels, bool $oneSize) {
    plantSizedRecords($this->first);
    plantSizedRecords($this->second);

    sizesPerWorkshopMigration()->down();
    sizesPerWorkshopMigration()->up();

    foreach ([$this->first, $this->second] as $workshop) {
        $set = CurrentWorkshop::run($workshop, fn () => app(ProductSizes::class)->fresh(Product::query()->where('sku', $sku)->firstOrFail()));

        expect($set->sizes->pluck('label')->all())->toBe($labels)
            ->and($set->oneSize)->toBe($oneSize);
    }
})->with([
    'Denim Jacket (Apparel)' => ['PRD-JKT-001', ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'], false],
    'Canvas Low Sneaker (Footwear)' => ['PRD-SNK-003', ['36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46'], false],
    'Leather Tote (Bag)' => ['PRD-TOT-004', ['One size'], true],
]);

it('gives a deleted product its sizes too, and makes Apparel and Footwear suggest their lists and Bag suggest One size', function () {
    $deleted = Product::factory()->create(['category' => 'apparel']);
    $deleted->delete();

    sizesPerWorkshopMigration()->down();
    sizesPerWorkshopMigration()->up();

    expect(Product::withTrashed()->findOrFail($deleted->id)->sizes()->count())->toBe(7);

    foreach ([$this->first, $this->second] as $workshop) {
        $suggested = CurrentWorkshop::run($workshop, fn (): array => ProductCategory::query()->whereIn('code', ['apparel', 'footwear', 'bag'])->get()
            ->mapWithKeys(fn (ProductCategory $category): array => [$category->code => $category->suggestedSizes()->pluck('label')->all()])->all());

        expect($suggested['apparel'])->toBe(['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'])
            ->and($suggested['footwear'])->toBe(['36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46'])
            ->and($suggested['bag'])->toBe([])
            ->and(CurrentWorkshop::run($workshop, fn () => Size::query()->whereIn('id', app(ProductSizes::class)->startingSizes(ProductCategory::query()->where('code', 'bag')->value('id'), $workshop->id))->pluck('label')->all()))->toBe(['One size']);
    }
});

it('lists two sizes tied on a position by creation order, and keeps the name and the One size unique in a workshop', function () {
    // As the workshop of the demo data: the list a screen of it would read.
    CurrentWorkshop::run($this->first, function () {
        $first = Size::factory()->create(['label' => 'Tied B', 'position' => 15]);
        $second = Size::factory()->create(['label' => 'Tied A', 'position' => 15]);

        expect(Size::query()->ordered()->pluck('label')->map(fn (mixed $label): string => (string) $label)->all())->toBe(['XS', 'Tied B', 'Tied A', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46', 'One size'])
            ->and($first->id)->toBeLessThan($second->id);

        // A savepoint each, for PostgreSQL: a refused row would abort the surrounding transaction.
        $refused = fn (array $row) => fn () => DB::transaction(fn () => Size::factory()->create($row));

        expect($refused(['label' => 'xxl']))->toThrow(QueryException::class)
            ->and($refused(['label' => 'Second one size', 'is_one_size' => true]))->toThrow(QueryException::class);
    });

    // The same name in another workshop is fine.
    CurrentWorkshop::run($this->second, fn () => expect(Size::factory()->create(['label' => 'Tied B'])->label)->toBe('Tied B'));
});

it('goes back to the three lists on down() and refuses, naming the size, once a size was added or renamed', function (Closure $change, string $name) {
    plantSizedRecords($this->first);
    $change($this);

    expect(fn () => sizesPerWorkshopMigration()->down())->toThrow(RuntimeException::class, $name)
        // Nothing was changed: the migration is still up.
        ->and(Schema::hasTable('product_sizes'))->toBeTrue()
        ->and(Schema::hasTable('size_lists'))->toBeFalse();
})->with([
    'a size added' => [fn () => Size::factory()->create(['label' => 'XXS', 'position' => 5]), 'XXS'],
    'a size renamed' => [fn () => Size::query()->where('label', 'XXL')->firstOrFail()->update(['label' => '2XL']), '2XL'],
]);

it('makes PostgreSQL refuse a record of a size of another workshop', function (string $table) {
    plantSizedRecords($this->first);
    $foreignSize = CurrentWorkshop::run($this->second, fn () => Size::query()->where('label', 'S')->value('id'));
    $row = DB::table($table)->first();

    expect(fn () => DB::transaction(fn () => DB::table($table)->where('id', $row->id)->update(['size_id' => $foreignSize])))->toThrow(QueryException::class, "{$table}_workshop_size_foreign");
})->with(['product_size_stocks', 'order_line_sizes', 'product_restock_lines', 'production_batches'])
    ->skip(fn () => DB::getDriverName() !== 'pgsql', 'PostgreSQL only: SQLite keeps no composite keys.');
