<?php

use App\Models\Concerns\BelongsToWorkshop;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\OrderLineCut;
use App\Models\OrderLineCutMaterial;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductComponent;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use App\Models\Workshop;

/*
|--------------------------------------------------------------------------
| S-44-c: guards against forgetting the workshop (ADR-0013 decision 10)
|--------------------------------------------------------------------------
|
| One unscoped query is a data leak between two businesses. Four rules keep the automatic scope in force; each names the file it
| found and points at the ADR. The fourth (S-44-d) covers validation: Laravel's `exists` / `unique` rules query the table directly and
| ignore the scope, so they go through `App\Validation\WorkshopRule`, which adds the workshop.
*/

const WORKSHOP_ADR = 'docs/adr/0013-one-workshop-per-account.md';

/** The tables of workshop data (S-45 adds units and the two category tables). */
const WORKSHOP_TABLES = ['materials', 'products', 'product_components', 'stock_movements', 'sales', 'orders', 'order_lines', 'order_line_cuts', 'order_line_cut_materials'];

/**
 * Every PHP file under `app/`, as [path relative to the project root, source without comments]: a comment that talks about
 * `withoutGlobalScope()` is not a call to it.
 *
 * @return list<array{string, string}>
 */
function applicationSources(): array
{
    $root = dirname(__DIR__, 2);
    $files = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/app', FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $code = '';

        foreach (token_get_all((string) file_get_contents($file->getPathname())) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            $code .= is_array($token) ? $token[1] : $token;
        }

        $files[] = [str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1)), $code];
    }

    return $files;
}

/**
 * The files of `app/` whose source (comments removed) matches the pattern, as `path (the matched text)`.
 *
 * @return list<string>
 */
function applicationFilesMatching(string $pattern): array
{
    $found = [];

    foreach (applicationSources() as [$path, $code]) {
        if (preg_match($pattern, $code, $match) === 1) {
            $found[] = "{$path} ({$match[0]})";
        }
    }

    return $found;
}

it('puts BelongsToWorkshop on every model of workshop data', function () {
    // User and Workshop are the owners, not owned. Units and categories stay shared until S-45 gives each workshop its own lists.
    $allowed = [User::class, Workshop::class, Unit::class, MaterialCategory::class, ProductCategory::class];
    $missing = [];

    foreach (applicationSources() as [$path, $code]) {
        // Traits and scopes live in these two namespaces; they are not models.
        if (! str_starts_with($path, 'app/Models/') || str_starts_with($path, 'app/Models/Concerns/') || str_starts_with($path, 'app/Models/Scopes/')) {
            continue;
        }

        $class = 'App\\Models\\'.str_replace('/', '\\', substr($path, strlen('app/Models/'), -strlen('.php')));

        if (! in_array($class, $allowed, true) && ! in_array(BelongsToWorkshop::class, class_uses_recursive($class), true)) {
            $missing[] = "{$path} ({$class})";
        }
    }

    expect($missing)->toBe([], 'These models do not use '.BelongsToWorkshop::class.', so their rows would be read across workshops: '.implode(', ', $missing).'. Add the trait and a `workshop_id` column, or (for shared reference data only) the allow-list of this test with the reason. See '.WORKSHOP_ADR.' decisions 3 and 10.');
});

it('never switches the workshop scope off in app/', function () {
    $found = applicationFilesMatching('/withoutGlobalScopes?\s*\(/');

    expect($found)->toBe([], 'These files remove a global scope, which would read every workshop: '.implode(', ', $found).'. Only the workshop scope is registered, and the app never needs another workshop. See '.WORKSHOP_ADR.' decision 3 and consequences.');
});

it('never queries a table of workshop data around its model in app/', function () {
    $tables = implode('|', WORKSHOP_TABLES);
    $found = applicationFilesMatching("/(DB::table|->from)\\(\\s*['\"](?:{$tables})['\"]/");

    expect($found)->toBe([], 'These files query a table of workshop data without its model, which skips the workshop scope: '.implode(', ', $found).'. Use the Eloquent model (or its `toBase()`), so every query is limited to the current workshop. See '.WORKSHOP_ADR.' decisions 3 and 10.');
});

it('validates against a table of workshop data only through WorkshopRule in app/', function () {
    // Both spellings of each: the table (`materials`) and the model class (`Material::class`, `App\Models\Material`).
    $targets = array_merge(WORKSHOP_TABLES, array_map(
        static fn (string $model): string => '(?:\\\\?App\\\\{1,2}Models\\\\{1,2})?'.class_basename($model).'(?:::class)?',
        [Material::class, Product::class, ProductComponent::class, StockMovement::class, Sale::class, Order::class, OrderLine::class, OrderLineCut::class, OrderLineCutMaterial::class],
    ));
    $named = '(?:'.implode('|', $targets).')\b';
    $found = [];

    foreach (applicationSources() as [$path, $code]) {
        // The helper is where the workshop is added.
        if ($path === 'app/Validation/WorkshopRule.php') {
            continue;
        }

        // `Rule::exists('materials'…)`, `Rule::unique(Product::class…)`, and the string rules `'exists:materials,id'` / `'required|unique:products,sku'`.
        // `\b` keeps `WorkshopRule::unique(` (the helper's own name) from matching.
        if (preg_match("/\bRule::(?:exists|unique)\(\s*['\"]?{$named}|[|'\"](?:exists|unique):\s*{$named}/", $code, $match) === 1) {
            $found[] = "{$path} ({$match[0]})";
        }
    }

    expect($found)->toBe([], 'These files validate against a table of workshop data without the workshop: '.implode(', ', $found).'. Laravel\'s `exists`/`unique` rules ignore the workshop scope, so another workshop\'s record would count. Use App\Validation\WorkshopRule::exists() / ::unique(). See '.WORKSHOP_ADR.' decisions 6 and 10.');
});
