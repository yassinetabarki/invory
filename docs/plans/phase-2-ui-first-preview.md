# Phase 2 — UI-first preview (approved mockup on mock data)

> Owner: **tech-lead** · Status: **done** (verified 2026-10-14: committed `becfcae`; the preview data was retired by S-17; the human click-through moves to backlog S-30) · Stories: S-12, S-13, S-05, S-06-FE, S-07-FE, S-14-FE, S-15-FE, S-16-FE ([BACKLOG](../BACKLOG.md))
> Design reference: [docs/design/mockup/](../design/mockup/README.md) · Data seam: [ADR-0006](../adr/0006-ui-first-preview-data.md) · Maths: `invory-costing-domain` skill
> Executed by the tech-lead, who dispatches `backend-developer` (BE) and `frontend-developer` (FE) per wave and reviews every handoff.

## Outcome
A signed-in user can click through **every approved screen** in the real app: Dashboard, Materials (list with Restock + History, detail with
"What you can make with it", form with photo), Products (list, detail, form with composition editor, selling price and margin) and Sales
(log, filters, record / edit / delete). Pages are final: typed by `domain.ts`, linked with Wayfinder, with validation errors and toasts.
Data for things without tables yet (products, sales, history, photos) comes from one server-side fixture seam (`App\Mock`); every derived number
is computed on the server by the production domain code. Writes to preview data validate for real, persist nothing, and say so in a toast.

**Out of scope (later backend stories, see BACKLOG Phase 3):** products / sales / stock movement tables, photo storage, real restock,
server-side "material in use" delete guard, custom date ranges (F-11).

## Facts this plan builds on
- Materials CRUD (S-01/S-02) and the costing engine (S-03) are real and committed. `MaterialController`, `MaterialResource`, materials pages exist.
- Toasts: `Inertia::flash('toast', ['type' => 'success'|'info'|'warning'|'error', 'message' => __('…')])`, shown by the starter kit's `<Toaster>`.
- `config('inertia.testing.ensure_pages_exist')` is **true**: PHP tests need the page files, so BE-1 creates stubs for new pages in Wave 1.
- Inertia 3 has `Inertia::defer()` + `<Deferred>` (React) and `AssertableInertia::loadDeferredProps()` for tests.
- Wayfinder runs with `formVariants: true`: `Controller.update.form(id)` gives `{ action: '…?_method=PUT', method: 'post' }`, which is what
  multipart (photo) updates need. Pass **ids** to Wayfinder helpers (`ProductController.show(product.id)`), so signatures survive the later switch
  from `int $product` to model binding.
- Fonts come from `laravel-vite-plugin/fonts` (`bunny()` in `vite.config.ts`) and the `@fonts` Blade directive.
- The shadcn CLI is unreliable here (backlog F-04): ui files may need hand-fixed imports (`@/lib/utils`, per-package `@radix-ui/react-*`).
- Brick/math 1.x `RoundingMode` cases are PascalCase (`HalfUp`, `Down`).

## Mockup deltas and UX decisions (tech-lead, 2026-10-08)
| Topic | Decision |
|---|---|
| Currency | Money is formatted with the installation currency (`app.currency`, TND by default, 3 decimals). The mockup's `€` is illustrative. Currency prefixes in inputs use the currency's own symbol/code from Intl. |
| Selling price (decision 5) | Product form: the "Production costs per unit" card becomes **"Costs and price"** with Labor, Overhead and **Selling price (optional)**, hint "Pre-fills Record a sale. The margin is calculated when you save." Product detail: the Cost breakdown card adds **Selling price** and **Margin** rows ("TND 53.246 · 59.83%") under Unit cost, or "No selling price set" with an Edit link. Products list: a **"Price · margin"** column (price, margin % below; "—" without a price). |
| Capacity badge copy | `0` → **"Can't produce"** (mockup) instead of the skill's older "Out of stock"; `null` → "No composition"; `n` → "n units" ("1 unit"). |
| Materials list rows | Row actions follow the mockup: **Restock**, **History**, View, Edit. Delete moves to the detail page only (disabled with the reason when `used_in_count > 0`). |
| Material detail | Adds **Restock** (same form, opened from the header) and a full **History** section under "What you can make with it". |
| Composition editor | Rows reorder with **Move up / Move down** buttons (keyboard-accessible). The mockup's drag handle is not required. |
| "Record a sale" panel | Beside the table on wide screens (mockup), a `Sheet` below `lg`. Same form component for record and edit. Dashboard and product "Record sale" buttons open `sales.index?record=1[&record_product_id=ID]`. |
| Sales period select | Presets only (Last 7 days, Last 30 days, This month, This year). "Custom range…" → backlog F-11. |
| 375 px | Every table collapses to stacked cards below `md`; inline panels (restock, history) render inside the card. |

## Waves

```
Wave 1  S-13a contract + routes + skeletons (BE-1) ─┐
        S-13b domain maths (BE-2)                   ├─► TL review ─► Wave 2
        S-12  theme, shell, shared UI, test helpers (FE-1) ─┘
Wave 2  S-13c preview data layer (BE-3) ─┐
        S-05  products UI (FE-2)         ├─► TL review: all gates + PostgreSQL parity + browser click-through ─► fix rounds (max 3)
        S-06-FE + S-14-FE dashboard, sales, nav (FE-3) │
        S-07-FE + S-15-FE + S-16-FE materials UI (FE-4) ┘
```
Parallel-work rules (one shared working tree): disjoint files per wave (listed per subtask); focused checks while others work; the tech-lead runs
the full gates once the wave is in. Wave-2 FE developers test with Vitest and the Inertia mock only: **no browser smoke in Wave 2** (BE-3 is
still filling the props). A transient `npm run build` failure while BE-3 is mid-edit (Wayfinder boots Laravel) is not yours: retry later.

---

## The contract (frozen — changes go through the tech-lead)

### Routes (`routes/web.php`, inside the existing `auth` + `verified` group)
| Method | URI | Name | Controller@action | Page / response |
|---|---|---|---|---|
| GET | /dashboard | dashboard | `DashboardController` (invokable) | `dashboard` · query `period` |
| GET… | /materials… | materials.* | `MaterialController` (resource, real) | unchanged names; props extended below |
| POST | /materials/{material}/restocks | materials.restocks.store | `MaterialRestockController@store` | preview: validate → `back()` + info toast |
| GET | /products | products.index | `ProductController@index` | `products/index` · query `search`, `category`, `status`, `cant_produce`, `page` |
| GET | /products/create | products.create | `@create` | `products/create` |
| POST | /products | products.store | `@store` | preview: validate → `products.index` + info toast |
| GET | /products/{product} | products.show | `@show` | `products/show` |
| GET | /products/{product}/edit | products.edit | `@edit` | `products/edit` |
| PUT | /products/{product} | products.update | `@update` | preview: validate → `products.show` + info toast |
| DELETE | /products/{product} | products.destroy | `@destroy` | preview: → `products.index` + info toast |
| GET | /sales | sales.index | `SaleController@index` | `sales/index` · query `search`, `product_id`, `period`, `page`, `record`, `record_product_id` |
| POST | /sales | sales.store | `@store` | preview: validate → `back()` + info toast |
| PUT | /sales/{sale} | sales.update | `@update` | preview: validate → `back()` + info toast |
| DELETE | /sales/{sale} | sales.destroy | `@destroy` | preview: → `back()` + info toast |

`Route::resource('products', ProductController::class)` and `Route::resource('sales', SaleController::class)->only(['index', 'store', 'update', 'destroy'])`.
Until the tables exist, `{product}` and `{sale}` are plain integer ids (`int $product`); unknown or non-numeric ids → **404**.
Preview-only controllers authorize `Gate::authorize('access-preview')` on every action (ability defined in `AppServiceProvider`: any authenticated
user, `false` when `app()->isProduction()`). `MaterialController` keeps `MaterialPolicy`; `MaterialRestockController` authorizes
`update` on the material **and** `access-preview`.

Toast copy for preview writes (type `info`): "Preview: the product passed validation but wasn't saved." · "Preview: nothing was deleted." ·
"Preview: the sale passed validation but wasn't saved." · "Preview: the restock passed validation but wasn't saved." Real material create/update with a
`photo` or `remove_photo` keeps `success` and appends " Photos are a preview and weren't saved."

### Shared props
```ts
app: { currency: string; locale: string; today: string } // today: 'YYYY-MM-DD' in the app timezone (default for date inputs, max date)
```

### Types (`resources/js/types/domain.ts`) — BE-1 writes these verbatim in Wave 1
Existing types change as follows:
```ts
// Material: append after `notes`
    photo_url: string | null; // public URL of the photo, or null (no photo)
    used_in_count: number; // non-deleted products whose composition uses this material

// MaterialFormData: append
    photo: File | null;
    remove_photo: boolean;

// MaterialsIndexProps: append
    summary: { materials_count: number; inventory_value: Decimal }; // over all non-deleted materials, not just this page
    recent_history?: StockMovement[]; // deferred prop: newest 5 movements of each material on this page

// MaterialShowProps: replace with
export type MaterialShowProps = {
    material: Material;
    usage: MaterialUsage[]; // bottleneck rows first, then by product name
    history: StockMovement[]; // newest first
};
```
New types (append, in this order):
```ts
// ---------------------------------------------------------------------------
// UI-first track (2026-10-08): products, sales, photos, stock history, usage.
// Decimals: scale-4 strings · percents: scale-2 strings · dates: 'YYYY-MM-DD'.
// ---------------------------------------------------------------------------

export type ProductCategory = 'apparel' | 'footwear' | 'bag' | 'accessory' | 'jewelry' | 'other';
export type ProductStatus = 'draft' | 'active' | 'archived';
export type SalesPeriod = 'last_7_days' | 'last_30_days' | 'this_month' | 'this_year';
export type StockMovementType = 'created' | 'restock' | 'correction' | 'price_change';

export type MaterialRef = { id: number; sku: string; name: string; unit: UnitOfMeasure };
export type ProductRef = { id: number; sku: string; name: string; photo_url: string | null };

export type Capacity = {
    max_producible: number | null; // null = no composition
    bottleneck: MaterialRef | null; // null when max_producible is null
};

export type Margin = {
    amount: Decimal; // selling_price − unit_cost, may be negative
    percent: Decimal | null; // amount ÷ selling_price × 100 (scale 2); null when selling_price is 0
};

export type CostBreakdown = {
    material_cost: Decimal;
    labor_cost: Decimal;
    overhead_cost: Decimal;
    unit_cost: Decimal;
};

export type ProductComponentLine = {
    id: number;
    position: number; // 1-based display order
    material: MaterialRef & { photo_url: string | null; stock_status: StockStatus; reorder_level: Decimal | null };
    quantity: Decimal; // per unit of product, in the material's unit
    waste_percent: Decimal; // scale 2
    effective_quantity: Decimal;
    unit_cost: Decimal; // the material's unit cost
    line_cost: Decimal;
    stock_on_hand: Decimal;
    units_possible: number;
    is_bottleneck: boolean;
    notes: string | null; // placement, e.g. "Front placket"
};

export type ProductListItem = {
    id: number;
    sku: string;
    name: string;
    category: ProductCategory;
    category_label: string;
    status: ProductStatus;
    status_label: string;
    photo_url: string | null;
    components_count: number;
    unit_cost: Decimal;
    selling_price: Decimal | null;
    margin: Margin | null; // null when selling_price is null
    capacity: Capacity;
    units_sold_last_30_days: number;
};

export type Product = {
    id: number;
    sku: string;
    name: string;
    category: ProductCategory;
    category_label: string;
    status: ProductStatus;
    status_label: string;
    description: string | null;
    photo_url: string | null;
    labor_cost: Decimal;
    overhead_cost: Decimal;
    selling_price: Decimal | null;
    cost: CostBreakdown;
    margin: Margin | null; // null when selling_price is null
    capacity: Capacity;
    components: ProductComponentLine[]; // ordered by position
    created_at: string; // ISO-8601
    updated_at: string;
};

export type ProductFilters = {
    search: string | null; // name or SKU
    category: ProductCategory | null;
    status: ProductStatus | null;
    cant_produce: boolean; // true → max_producible = 0
};

/** A material the composition editor can pick. */
export type MaterialOption = MaterialRef & {
    unit_label: string;
    unit_cost: Decimal;
    quantity_on_hand: Decimal;
    stock_status: StockStatus;
};

export type ProductComponentFormRow = {
    material_id: number | '';
    quantity: string;
    waste_percent: string;
    notes: string;
};

/** Fields the product form submits (strings for decimals). */
export type ProductFormData = {
    sku: string;
    name: string;
    category: ProductCategory | '';
    status: ProductStatus | '';
    description: string;
    labor_cost: string;
    overhead_cost: string;
    selling_price: string; // '' = no selling price
    photo: File | null;
    remove_photo: boolean;
    components: ProductComponentFormRow[];
};

export type Sale = {
    id: number;
    product: ProductRef;
    quantity: number; // whole units
    unit_price: Decimal;
    total: Decimal; // quantity × unit_price, computed server-side
    sold_on: string; // 'YYYY-MM-DD'
    note: string | null;
};

export type SalesFilters = {
    search: string | null; // product name, SKU or note
    product_id: number | null;
    period: SalesPeriod; // default 'last_30_days'
};

export type SalesTotals = {
    revenue: Decimal;
    units_sold: number;
    sales_count: number;
    average_sale: Decimal | null; // null when sales_count = 0
};

/** Every non-deleted product (filter). The "Record a sale" form lists only status 'active'. */
export type SaleProductOption = ProductRef & {
    status: ProductStatus;
    selling_price: Decimal | null; // pre-fills the unit price
};

/** Fields the sale form submits. */
export type SaleFormData = {
    product_id: number | '';
    quantity: string;
    unit_price: string;
    sold_on: string; // 'YYYY-MM-DD'
    note: string;
};

export type StockMovement = {
    id: number;
    material_id: number;
    type: StockMovementType;
    type_label: string; // "Created", "Restock", "Correction", "Price change"
    occurred_on: string; // 'YYYY-MM-DD'
    quantity: Decimal | null; // created: opening stock · restock: quantity received
    quantity_before: Decimal | null; // correction only
    quantity_after: Decimal | null; // stock after the entry; null for price_change
    unit_price: Decimal | null; // created: opening unit cost · restock: unit price paid
    unit_cost_before: Decimal | null; // price_change only
    unit_cost_after: Decimal | null; // price_change only
    reference: string | null; // restock: PO or delivery note
    note: string | null; // correction: reason
    user_name: string | null; // who did it
};

/** Fields the restock quick form submits. */
export type RestockFormData = {
    quantity: string;
    unit_price: string;
    received_on: string; // 'YYYY-MM-DD'
    reference: string;
    update_unit_cost: boolean; // default true; always sent
};

export type NextLimit = { material: MaterialRef; max_producible: number };

/** One product that uses the material shown ("What you can make with it"). */
export type MaterialUsage = {
    product: ProductRef & { status: ProductStatus; status_label: string };
    effective_quantity: Decimal; // needed per unit, waste included, in this material's unit
    units_covered: number; // units this material's stock covers on its own
    max_producible: number; // units of the product that can be made now (whole composition)
    bottleneck: MaterialRef; // the material that limits the product
    is_bottleneck: boolean; // true when that material is this one
    next_limit: NextLimit | null; // only when is_bottleneck; null if it is the product's only material
};

export type TopProduct = {
    product: ProductRef;
    units_sold: number;
    revenue: Decimal;
    percent_of_top: Decimal; // bar length for this ranking, scale 2; '100.00' for the first
};

export type UnproducibleAlert = {
    product: ProductRef;
    bottleneck: MaterialRef & { stock_status: StockStatus };
};

export type DashboardStats = {
    revenue: Decimal;
    units_sold: number;
    sales_count: number;
    products_sold_count: number;
    inventory_value: Decimal;
    materials_count: number;
    needs_restock_count: number;
    low_stock_count: number;
    out_of_stock_count: number;
    unproducible_count: number; // products (any status) with max_producible = 0
    products_count: number; // non-deleted products
};

// Page props
export type ProductsIndexProps = {
    products: Paginated<ProductListItem>; // by name
    filters: ProductFilters;
    categories: Option<ProductCategory>[];
    statuses: Option<ProductStatus>[];
};
export type ProductCreateProps = {
    categories: Option<ProductCategory>[];
    statuses: Option<ProductStatus>[];
    materials: MaterialOption[]; // every non-deleted material, by name
};
export type ProductEditProps = ProductCreateProps & { product: Product };
export type ProductShowProps = {
    product: Product;
    sales_last_30_days: { units_sold: number; revenue: Decimal };
    recent_sales: Sale[]; // newest 5
};
export type SalesIndexProps = {
    sales: Paginated<Sale>; // sold_on desc, then id desc
    filters: SalesFilters;
    totals: SalesTotals; // for the filtered set
    periods: Option<SalesPeriod>[];
    products: SaleProductOption[]; // by name
    record: { product_id: number | null } | null; // non-null → open "Record a sale" on load
};
export type DashboardProps = {
    period: SalesPeriod;
    periods: Option<SalesPeriod>[];
    stats: DashboardStats;
    top_products: { by_units: TopProduct[]; by_revenue: TopProduct[] }; // max 5 each, for the period
    needs_restock: Material[]; // max 5: out first, then low; by name
    recent_sales: Sale[]; // newest 5, any period
    alerts: UnproducibleAlert[]; // every product that can't be produced, by name
};
```
Request bodies: forms submit the `*FormData` shapes. `photo` makes the request multipart; updates with a file go as `POST` + `_method=PUT`.
Nested composition errors arrive as `components.N.field`.

### PHP APIs
**Enums (BE-1)**, string-backed, each with `label(): string` and `static options(): list<array{value: string, label: string}>`:
`ProductCategory` (Apparel, Footwear, Bag, Accessory, Jewelry, Other), `ProductStatus` (Draft, Active, Archived),
`SalesPeriod` (Last 7 days, Last 30 days, This month, This year) with
`public function range(CarbonImmutable $today): array{0: CarbonImmutable, 1: CarbonImmutable}` (inclusive start-of-day dates, see the skill) and
`public static function default(): self` (LastThirtyDays), and `StockMovementType` (Created, Restock, Correction, Price change).

**Domain maths (BE-2)**, pure PHP (no `Illuminate`), BigDecimal only, rules in the skill:
```php
namespace App\Domain\Costing;
final readonly class Margin { public function __construct(public string $amount, public ?string $percent) {} } // scale 4 / scale 2
final class MarginCalculator {
    /** null when $sellingPrice is null; percent null when the selling price is zero. */
    public function calculate(?string $sellingPrice, string $unitCost): ?Margin;
}
// CostingCalculator (existing class) gains:
/** @param list<BomLine> $lines  Capacity of the BOM with every line of $materialId removed (the "next limit"). maxProducible null when nothing is left. */
public function capacityWithout(array $lines, int $materialId): ProductionCapacity;

namespace App\Domain\Sales;
final readonly class SaleLine { public function __construct(public int $productId, public int $quantity, public string $unitPrice) {} }
final readonly class SalesSummary { public function __construct(public string $revenue, public int $unitsSold, public int $salesCount, public ?string $averageSale, public int $productsSoldCount) {} }
final readonly class ProductSales { public function __construct(public int $productId, public int $unitsSold, public string $revenue, public string $percentOfTop) {} }
final class SalesCalculator {
    public function lineTotal(int $quantity, string $unitPrice): string;            // scale 4
    /** @param list<SaleLine> $sales */
    public function summarize(array $sales): SalesSummary;
    /** @param list<SaleLine> $sales  @param 'units'|'revenue' $by  @return list<ProductSales> ranked, at most $limit */
    public function topProducts(array $sales, string $by, int $limit = 5): array;
}
```
Invalid input (quantity < 1, negative price, unknown `$by`, non-numeric strings) throws `InvalidArgumentException`.

---

## Preview fixture (single source: `App\Mock`; seeded materials: `DemoCatalogSeeder`)
All values below are **inputs**. Derived values are computed by the domain classes; the "Expected" tables are what tests assert
(time frozen at **2026-10-08**, `DemoCatalogSeeder` run). Dates are stored as `days_ago` relative to today.

### Materials (seeded for real, `updateOrCreate` by SKU; photo = `public/mock/photos/<lower-sku>.svg` swatch in the tint/ink colours)
| SKU | Name | Category | Unit | Unit cost | Stock | Reorder at | Photo tint / ink |
|---|---|---|---|---|---|---|---|
| MAT-RVT-009 | Brass rivet 9mm | hardware | pc | 0.0800 | 5000 | 1000 | #B8954A / #FFFFFF |
| MAT-CAN-010 | Cotton canvas 10oz Natural | fabric | m | 5.4000 | 300 | 60 | #D9CBB0 / #5B4A2A |
| MAT-JRS-180 | Cotton jersey 180gsm White | fabric | m | 4.2000 | 64 | 80 | #ECECE8 / #5F5F66 |
| MAT-DEN-012 | Denim 12oz Indigo | fabric | m | 8.5000 | 250 | 100 | #3B4A7A / #FFFFFF |
| MAT-BTN-017 | Metal shank button 17mm | fastener | pc | 0.3500 | 900 | 1000 | #A88B4A / #FFFFFF |
| MAT-ZIP-018 | Metal zipper 18cm | fastener | pc | 0.9500 | 1250 | 300 | #B9BCC4 / #2A2A2E |
| MAT-LTH-NAP | Nappa leather Cognac | leather | sqft | 6.8000 | 540 | 150 | #9A5B34 / #FFFFFF |
| MAT-THR-PES | Polyester thread | thread | m | 0.0040 | 20000 | 5000 | #F4F1EA / #5F5F66 |
| MAT-SOL-042 | Rubber cupsole 42 | sole | pair | 5.9000 | 0 | 40 | #EFEDE6 / #5F5F66 |
| MAT-LIN-VIS | Viscose lining Black | lining | m | 3.1000 | 180 | 50 | no photo |
| MAT-LAC-120 | Waxed cotton laces 120cm | trim | pair | 0.4500 | 600 | 200 | no photo |
| MAT-LBL-WOV | Woven brand label | label | pc | 0.1200 | 400 | 500 | #1F1F23 / #FFFFFF |

Supplier details: button = Northgate Trims, ref `NT-17-SHK-AB`, colour Antique brass, composition Zinc alloy, notes "Hammer-on shank. Needs the tack-button press."
Others: plausible supplier/colour/composition values or null (BE's choice). Expected: inventory value **`"10544.3000"`**, 12 materials, needs restock 4 (low 3: jersey, button, label · out 1: cupsole).

### Products (fixture; materials referenced by SKU; photo swatch except PRD-JKT-006)
| id | SKU | Name | Category | Status | Labor | Overhead | Selling price | Tint / ink | Description |
|---|---|---|---|---|---|---|---|---|---|
| 1 | PRD-JKT-001 | Denim Jacket | apparel | active | 12.0000 | 3.5000 | 89.0000 | #3B4A7A / #FFFFFF | Classic trucker jacket in 12oz indigo denim with metal shank buttons. |
| 2 | PRD-TSH-002 | Classic White T-shirt | apparel | active | 3.2000 | 0.7500 | 24.0000 | #ECECE8 / #5F5F66 | (BE's choice) |
| 3 | PRD-SNK-003 | Canvas Low Sneaker | footwear | active | 9.3000 | 2.0000 | 69.0000 | #D9CBB0 / #5B4A2A | (BE's choice) |
| 4 | PRD-TOT-004 | Leather Tote | bag | active | 22.5000 | 4.7700 | 249.0000 | #9A5B34 / #FFFFFF | (BE's choice) |
| 5 | PRD-JNS-005 | Denim Jeans 5-pocket | apparel | active | 11.0000 | 2.9700 | 79.0000 | #2F3B66 / #FFFFFF | (BE's choice) |
| 6 | PRD-JKT-006 | Canvas Chore Jacket | apparel | draft | 14.5000 | 3.0400 | null | no photo | (BE's choice) |
| 7 | PRD-BLT-007 | Leather Belt | accessory | active | 6.0000 | 1.3400 | 45.0000 | #7A4425 / #FFFFFF | (BE's choice) |

Compositions (position order; quantity · waste % · placement notes). Line ids: `product_id × 100 + position`.
| Product | Lines |
|---|---|
| 1 Denim Jacket | MAT-DEN-012 1.8000 · 10.00 · "Body, sleeves, collar" — MAT-BTN-017 8 · 0 · "Front placket (6), cuffs (2)" — MAT-THR-PES 120 · 5.00 · "All seams" — MAT-LBL-WOV 1 · 0 · "Inside back yoke" |
| 2 T-shirt | MAT-JRS-180 1.2000 · 6.00 · "Body and sleeves" — MAT-THR-PES 80 · 5.00 · "All seams" — MAT-LBL-WOV 1 · 0 · "Neck" |
| 3 Sneaker | MAT-CAN-010 0.6500 · 10.00 · "Upper" — MAT-SOL-042 1 · 0 · "Outsole" — MAT-LAC-120 1 · 0 · "Laces" — MAT-THR-PES 40 · 5.00 · "Upper seams" — MAT-LBL-WOV 1 · 0 · "Tongue" |
| 4 Tote | MAT-LTH-NAP 9.2000 · 10.00 · "Body and handles" — MAT-LIN-VIS 0.8000 · 5.00 · "Lining" — MAT-ZIP-018 1 · 0 · "Inner pocket" — MAT-RVT-009 8 · 0 · "Handle anchors" — MAT-THR-PES 150 · 5.00 · "All seams" — MAT-LBL-WOV 1 · 0 · "Inner pocket" |
| 5 Jeans | MAT-DEN-012 1.4000 · 8.00 · "Body" — MAT-BTN-017 1 · 0 · "Waistband" — MAT-RVT-009 6 · 0 · "Pocket corners" — MAT-ZIP-018 1 · 0 · "Fly" — MAT-THR-PES 180 · 5.00 · "All seams" — MAT-LBL-WOV 1 · 0 · "Back waistband" |
| 6 Chore Jacket | MAT-CAN-010 2.1000 · 10.00 · "Body, sleeves, pockets" — MAT-BTN-017 5 · 0 · "Front" — MAT-THR-PES 130 · 5.00 · "All seams" — MAT-LBL-WOV 1 · 0 · "Neck" |
| 7 Belt | MAT-LTH-NAP 1.2000 · 15.00 · "Strap" — MAT-RVT-009 4 · 0 · "Buckle and keeper" |

**Expected** (verified with exact decimals; matches every mockup figure):
| Product | material_cost | unit_cost | max_producible | bottleneck | next limit (without bottleneck) | margin amount · % | components | sold 30 d |
|---|---|---|---|---|---|---|---|---|
| 1 Denim Jacket | 20.2540 | **35.7540** | **112** | Metal shank button 17mm | Denim 12oz Indigo · 126 | 53.2460 · 59.83 | 4 | 38 |
| 2 T-shirt | 5.7984 | 9.7484 | 50 | Cotton jersey 180gsm White | Polyester thread · 238 | 14.2516 · 59.38 | 3 | 142 |
| 3 Sneaker | 10.4990 | 21.7990 | **0** | Rubber cupsole 42 | Woven brand label · 400 | 47.2010 · 68.41 | 5 | 9 |
| 4 Tote | 73.7600 | 101.0300 | 53 | Nappa leather Cognac | Polyester thread · 126 | 147.9700 · 59.43 | 6 | 12 |
| 5 Jeans | 15.5080 | 29.4780 | 105 | Polyester thread | Denim 12oz Indigo · 165 | 49.5220 · 62.69 | 6 | 64 |
| 6 Chore Jacket | 14.8900 | 32.4300 | 129 | Cotton canvas 10oz Natural | Polyester thread · 146 | null | 4 | 0 |
| 7 Belt | 9.7040 | 17.0440 | 391 | Nappa leather Cognac | Brass rivet 9mm · 1250 | 27.9560 · 62.12 | 2 | 31 |

Denim Jacket lines (golden): effective `1.9800 / 8.0000 / 126.0000 / 1.0000`, line cost `16.8300 / 2.8000 / 0.5040 / 0.1200`, units possible `126 / 112 / 158 / 400`.
`used_in_count`: rivet 3, canvas 2, jersey 1, denim 2, button 3, zipper 2, nappa 2, thread 6, cupsole 1, lining 1, laces 1, label 6.
Button usage (material detail, bottleneck rows first, then name): Denim Jacket (eff 8.0000, covers 112, can make 112, bottleneck = button,
next limit Denim 12oz Indigo 126) · Canvas Chore Jacket (5.0000, 180, 129, Cotton canvas) · Denim Jeans 5-pocket (1.0000, 900, 105, Polyester thread).
Dashboard: unproducible 1 of 7 (alert: Canvas Low Sneaker, bottleneck Rubber cupsole 42, `out`).

### Sales (fixture; 60 rows; `unit_price` = the product's selling price; ids in this order)
`id: days_ago · product id · quantity · note` (`—` = null note)
```
 1:150·2·30·Wholesale order   2:138·5·12·Wholesale order   3:121·1·6·Showroom        4:110·7·10·Boutique Lyon   5:96·4·3·Showroom
 6:84·2·25·Web shop           7:73·3·6·Web shop            8:61·5·9·Boutique Lyon    9:52·1·7·Wholesale order  10:44·2·18·Web shop
11:38·7·5·—                  12:33·4·2·Showroom           13:29·2·10·Web shop       14:29·7·4·Boutique Lyon   15:28·5·5·Wholesale order
16:27·1·3·Web shop           17:27·4·1·Showroom           18:26·5·5·Web shop        19:26·2·8·—               20:25·7·5·Web shop
21:24·5·7·Boutique Lyon      22:24·3·3·Web shop           23:23·2·12·Wholesale order 24:22·1·6·Showroom       25:21·7·3·Boutique Lyon
26:21·4·3·Web shop           27:20·2·14·Web shop          28:20·5·6·Web shop        29:19·7·6·Wholesale order 30:18·1·4·Web shop
31:18·2·6·Showroom           32:17·5·8·Wholesale order    33:17·3·1·—               34:16·4·2·Boutique Lyon   35:16·7·2·Web shop
36:15·2·10·Web shop          37:15·5·4·Showroom           38:14·1·5·Wholesale order 39:14·7·4·Web shop        40:13·1·5·Web shop
41:13·2·15·Wholesale order   42:13·4·2·Web shop           43:12·1·6·Wholesale order 44:11·5·10·Boutique Lyon  45:11·2·10·Web shop
46:10·7·4·Showroom           47:10·3·3·Web shop           48:9·2·10·Showroom        49:9·4·2·Web shop         50:9·5·5·Web shop
51:8·2·15·Web shop           52:7·1·5·Showroom            53:6·5·8·Wholesale order  54:5·3·2·Web shop         55:4·2·12·Wholesale order
56:3·7·3·Boutique Lyon       57:3·1·4·Web shop            58:2·4·2·Showroom         59:1·2·20·Web shop        60:1·5·6·Boutique Lyon
```
**Expected:**
| Period | sales_count | units_sold | revenue | average_sale | products_sold |
|---|---|---|---|---|---|
| last_7_days | 8 | 57 | 3001.0000 | 375.1250 | 6 |
| last_30_days (default) | 48 | 296 | 16850.0000 | 351.0417 | 6 |
| this_month | 9 | 62 | 3446.0000 | 382.8889 | 6 |
| this_year | 60 | 429 | 23752.0000 | 395.8667 | 6 |

Top 5, last 30 days — by units: T-shirt 142 (`100.00`), Jeans 64 (`45.07`), Denim Jacket 38 (`26.76`), Belt 31 (`21.83`), Tote 12 (`8.45`);
by revenue: Jeans 5056.0000 (`100.00`), T-shirt 3408.0000 (`67.41`), Denim Jacket 3382.0000 (`66.89`), Tote 2988.0000 (`59.10`), Belt 1395.0000 (`27.59`).
Sales list (default) starts with ids 60, 59, 58, 57, 56 (= dashboard recent sales). Denim Jacket recent sales: 57, 52, 43, 40, 38.

### Stock history (fixture, keyed by material SKU; `user_name` "Admin"; ids ascending in this order; same-day entries: higher id = newer)
| SKU | Entries (days_ago · type · values) |
|---|---|
| MAT-RVT-009 | 37 created 3000 @ 0.0800 → 3000 · 20 restock +2000 @ 0.0800 ref PO-2270 → 5000 |
| MAT-CAN-010 | 37 created 180 @ 5.4000 · 16 restock +120 @ 5.4000 → 300 |
| MAT-JRS-180 | 37 created 120 @ 4.2000 · 13 correction 120 → 64 note "Stock count" |
| MAT-DEN-012 | 37 created 150 @ 8.5000 · 18 restock +100 @ 8.5000 ref PO-2281 → 250 |
| MAT-BTN-017 | 37 created 600 @ 0.3200 · 24 correction 600 → 400 note "Stock count" · 6 restock +500 @ 0.3500 ref PO-2291 → 900 · 6 price_change 0.3200 → 0.3500 |
| MAT-ZIP-018 | 37 created 750 @ 0.9500 · 22 restock +500 @ 0.9500 → 1250 |
| MAT-LTH-NAP | 37 created 340 @ 6.8000 · 10 restock +200 @ 6.8000 ref PO-2286 → 540 |
| MAT-THR-PES | 37 created 10000 @ 0.0040 · 28 restock +10000 @ 0.0040 → 20000 |
| MAT-SOL-042 | 37 created 60 @ 5.9000 · 8 correction 60 → 0 note "Used in production" |
| MAT-LIN-VIS | 37 created 180 @ 3.1000 |
| MAT-LAC-120 | 37 created 400 @ 0.4500 · 26 restock +200 @ 0.4500 → 600 |
| MAT-LBL-WOV | 37 created 250 @ 0.1000 · 14 restock +150 @ 0.1200 → 400 · 14 price_change 0.1000 → 0.1200 |

For `created`: `quantity` = opening stock, `quantity_after` = same, `unit_price` = opening cost. Materials with no fixture history get `history: []`.

---

## S-13 · Preview data layer — backend

### S-13a — Wave 1 (BE-1) · contract, routes, skeletons · size M
**Files:** `resources/js/types/domain.ts`, `resources/js/types/global.d.ts` (`sharedPageProps.app.today` only), `app/Http/Middleware/HandleInertiaRequests.php`,
`tests/Feature/Foundation/SharedPropsTest.php`, `app/Enums/{ProductCategory,ProductStatus,SalesPeriod,StockMovementType}.php`, `tests/Unit/Enums/*`,
`routes/web.php`, `app/Http/Controllers/{DashboardController,ProductController,SaleController,MaterialRestockController}.php` (skeletons),
`app/Http/Controllers/MaterialController.php` (index `summary`; show `usage: []`, `history: []` placeholders), `app/Services/Inventory/InventorySummary.php` (real),
`app/Http/Resources/MaterialResource.php` (two new keys), `app/Providers/AppServiceProvider.php` (`access-preview`), `tests/Pest.php` (contract key helpers),
`tests/Feature/DashboardTest.php`, `tests/Feature/Preview/PreviewRoutesTest.php`, `tests/Feature/Inventory/InventorySummaryTest.php`, `tests/Feature/Materials/*`
(only where the new keys and props need assertions), page stubs `resources/js/pages/products/{index,show,create,edit}.tsx`, `resources/js/pages/sales/index.tsx`
(`// STUB — owned by frontend-developer`). The FE type-compat edits that the new contract forces in existing materials files are FE-1's (S-12 item 9).
1. **First**, write `domain.ts` exactly as frozen above (FE-1 imports `ProductStatus` in this wave).
2. Shared prop `app.today` (`now()->toDateString()`), typed, tested.
3. The four enums + unit tests (labels, options, `SalesPeriod::range()` for each case incl. month/year boundaries, `default()`).
4. Routes as frozen; skeleton controllers with the **final method names and parameters**: GET actions render their page component with no props;
   writes redirect back. Every action authorizes `access-preview` (restock: also `update` on the material).
5. `MaterialResource` gains `photo_url` (null for now) and `used_in_count` (0 for now). Update `materialContractKeys()`.
6. `InventorySummary` (real, `App\Services\Inventory`): `materials_count`, `inventory_value` (BigDecimal Σ `stockValue()`, scale 4), `needs_restock_count`,
   `low_stock_count`, `out_of_stock_count` over non-deleted materials. `materials.index` sends `summary`; `materials.show` sends `usage: []` and
   `history: []` until S-13c fills them, so the server honours `domain.ts` at every commit.
7. Tests: guests redirected on every new route (dataset); a verified user gets 200 / redirect; `access-preview` denied in production (403); shared prop
   present; `InventorySummary` (empty table, the 12 seeded-style materials → `"10544.3000"`, soft-deleted excluded); materials index/show props.

### S-13b — Wave 1 (BE-2) · domain maths · size S
**Files:** `app/Domain/Costing/CostingCalculator.php` (add `capacityWithout()` only), `app/Domain/Costing/{Margin,MarginCalculator}.php`,
`app/Domain/Sales/*`, `tests/Unit/Domain/Costing/{NextLimitTest,MarginTest}.php`, `tests/Unit/Domain/Sales/*`.
1. Tests first from the skill: Denim Jacket margin (`53.2460`, `59.83`), null price, zero price, negative margin; next limit (worked example → Denim 126;
   only-material → null; tie at the bottleneck; aggregated duplicate lines removed together); sales: line total, empty summary, the fixture's
   last-30-days summary and both top-5 rankings with exact `percent_of_top`, tie-breaks, zero top metric, invalid input.
2. Implement with BigDecimal; keep `App\Domain` framework-free (arch rule); 100 % branch coverage of the new code.

### S-13c — Wave 2 (BE-3) · preview data layer · size L
**Files:** `app/Mock/**`, `public/mock/photos/*.svg`, `app/Http/Controllers/{DashboardController,ProductController,SaleController,MaterialRestockController,MaterialController}.php`,
`app/Http/Requests/DashboardRequest.php`, `app/Http/Requests/Products/{IndexProductRequest,StoreProductRequest,UpdateProductRequest}.php`,
`app/Http/Requests/Sales/{IndexSaleRequest,StoreSaleRequest,UpdateSaleRequest}.php`, `app/Http/Requests/Materials/{StoreRestockRequest,StoreMaterialRequest,UpdateMaterialRequest}.php`,
`app/Http/Resources/MaterialResource.php`, `app/Models/Material.php` (PHPDoc `@property` only), `database/seeders/{DemoCatalogSeeder,DatabaseSeeder}.php`,
`tests/Feature/Preview/**`, `tests/Feature/Materials/**`, `tests/Arch/ArchTest.php` (App\Mock rule), `tests/Pest.php` (helpers).
1. `App\Mock`: the fixture above (inputs only) plus builders that shape props exactly like the contract, using `CostingCalculator`,
   `capacityWithout()`, `MarginCalculator` and `SalesCalculator`. Materials are loaded from the DB by SKU in **one query per request**; missing SKUs
   skip their lines. Every file opens with `/** MOCK — UI-first preview data (ADR-0006). Removed by S-17. */`. Pagination of fixture lists returns the
   same `{ data, links, meta }` shape as a paginated Resource collection (25 per page, `withQueryString()`).
2. Reuse `InventorySummary` (S-13a) for the dashboard stats.
3. Controllers fill the frozen props. Materials: `index` adds `summary`, `used_in_count`/`photo_url` per row and `Inertia::defer()` `recent_history`;
   `show` adds `usage` and `history`; `store`/`update` validate `photo` / `remove_photo` and pass `safe()->except([...])` to the actions (strict mode
   would throw on unknown attributes), with the toast suffix. Dashboard `needs_restock` is a real query.
4. FormRequests implement the skill's validation rules, including `components.*` (`distinct`, `exists` without trashed, `waste_percent` < 100),
   the photo rule (`File::image()->types(['jpg','jpeg','png','webp'])->max(4096)`), `sold_on` / `received_on` `before_or_equal:today`. Rules that need
   tables that don't exist yet (product SKU unique, sale product active) read `App\Mock` and carry a `// MOCK:` comment. On sale update, the sale's
   current product stays valid even if no longer active.
5. `DemoCatalogSeeder` seeds the 12 materials (`updateOrCreate` by SKU); `DatabaseSeeder` calls it.
6. Arch: `App\Mock` only used by `App\Http\Controllers`, `App\Http\Requests`, `Database\Seeders`.
7. Feature tests (time frozen at 2026-10-08, seeder run): every "Expected" table above through HTTP props (`assertInertia`), filters and pagination,
   404s, validation datasets for every write (incl. nested composition errors and photo type/size), preview writes persist nothing and flash `info`,
   deferred `recent_history`, guests redirected, N+1-safe (strict mode on).

**Done when:** `composer test` green, PostgreSQL parity green, every "Expected" value asserted.

---

## S-12 · Theme, shell, shared UI — Wave 1 (FE-1) · size M
**Files:** `resources/css/app.css`, `vite.config.ts` (fonts block only), `resources/views/app.blade.php` (the inline `html` background style only),
`resources/js/components/{app-logo,app-logo-icon,nav-main}.tsx` (+ `nav-main.test.tsx`), delete `resources/js/components/nav-footer.tsx` (F-02),
`resources/js/components/materials/stock-badge.tsx` (+ test), `resources/js/components/shared/{money,quantity}.tsx` (+ tests),
`resources/js/components/shared/{photo-thumb,photo-field,stat-card,callout,capacity-badge,product-status-badge}.tsx` (+ tests),
`resources/js/components/ui/{popover,command}.tsx`, `package.json`, `package-lock.json` (`cmdk`, `@radix-ui/react-popover` only),
`resources/js/lib/format.ts` (+ test), `resources/js/test/{inertia.tsx,inertia.test.tsx,setup.ts}`; **contract compatibility only** (item 9):
`resources/js/test/fixtures.ts`, `resources/js/components/materials/{materials-table,material-form}.test.tsx`, `resources/js/pages/materials/{create,edit}.tsx`,
`resources/js/pages/materials/{index,show}.test.tsx`.
1. **Theme tokens** in `app.css` from the mockup: background `#FAFAF9`, card `#FFFFFF`, foreground `#18181B`, muted-foreground `#5F5F66`,
   border `#E4E4E0`, input `#D6D6D0`, **primary `#2B3A86`** (hover `#1E2A66`) on white, sidebar `#F3F3F1`, ring indigo. Add semantic status tokens
   (success `#E6F4EF`/`#0B5C4B`, warning `#FDF0DC`/`#8A4405`, danger `#FDE8E6`/`#A3221A`, info `#EEF0FA`/`#2B3A86`, neutral `#F0F0EC`/`#3F3F46`)
   exposed as Tailwind colours. **Derive dark tokens** (dark neutral surfaces, a lighter indigo primary, dark-tinted status backgrounds) with
   WCAG AA text contrast (≥ 4.5:1) in both modes; list the ratios in the handoff. Update the Blade `html` background colours to match.
2. Fonts: Instrument Sans 400/500/600/700 and **IBM Plex Mono** 400/500 via `bunny()`; `--font-mono` in `@theme`. SKUs and codes use `font-mono`.
   `<Money>` and `<Quantity>` render with `tabular-nums`.
3. Logo tile in primary; `nav-main` active state matches parent URLs for non-dashboard items (F-01: `/materials/5/edit` highlights Materials); delete `nav-footer.tsx`.
4. `StockBadge` uses the status tokens (text + icon: check / exclamation / x).
5. Shared components (contract-agnostic unless noted), each with tests:
   - `PhotoThumb` `{ src: string | null; alt: string; size: 'sm' | 'md' | 'lg'; shape?: 'square' | 'portrait' }`: image with `object-cover`, or a neutral tile with an image icon when `src` is null.
   - `PhotoField` `{ name?: 'photo'; removeName?: 'remove_photo'; label: string; currentUrl: string | null; error?: string; aspect: 'square' | 'portrait'; onChange?: (file: File | null) => void; onRemoveChange?: (remove: boolean) => void }`: works inside `<Form>` (native `name`d inputs, hidden `remove_photo`) and with `useForm` (callbacks). Drop zone + "browse", preview of the picked file (object URL, revoked), Replace / Remove for an existing photo, "JPG, PNG or WebP · max 4 MB", optional client pre-check, error with `aria-describedby`.
   - `StatCard` `{ label; value: ReactNode; hint?: ReactNode; href?: string; tone?: 'default' | 'warning' | 'danger' }` (a link card when `href`).
   - `Callout` `{ tone: 'info' | 'warning' | 'danger' | 'success'; title?: ReactNode; children; action?: ReactNode; role?: 'alert' | 'note' | 'status' }` with an icon per tone.
   - `CapacityBadge` `{ value: number | null }`: `null` → "No composition", `0` → "Can't produce" (danger + x), `n` → "n units" / "1 unit" (success + check).
   - `ProductStatusBadge` `{ status: ProductStatus; label: string }` (draft = info, active/archived = neutral). **Build it last**: `ProductStatus` comes from BE-1's `domain.ts` in this wave.
6. `ui/popover.tsx` and `ui/command.tsx` (shadcn; fix imports by hand if the CLI misbehaves) for the composition editor's material picker.
7. `lib/format.ts`: `formatDate(value: string | null | undefined, locale)` → `7 Oct 2026` from `'YYYY-MM-DD'` or ISO-8601 with **no timezone shift**
   (format the calendar date as UTC), and `formatCount(value: number | null | undefined, locale)` → `1,250`. Em dash for null/invalid.
8. Test helper: page tests can render `<Deferred>` both before (fallback) and after (children) the deferred prop is present, and can assert what
   a `useForm` submit sends (method, URL, data, files) without a network call. Document the usage in the helper's header like the existing parts.
9. **Contract compatibility** (after BE-1 has written `domain.ts`): add the new required keys with neutral values to existing typed literals so
   `types:check` stays green: `Material` literals (`photo_url: null`, `used_in_count: 0`), `MaterialFormData` literals (`photo: null`,
   `remove_photo: false`), `MaterialsIndexProps` in tests (`summary`), `MaterialShowProps` in tests (`usage: []`, `history: []`). No behaviour change.
   These files ship in the S-13a commit, so keep them free of S-12 changes.

**Done when:** FE gates green; every shared component tested in each state; the AA contrast ratios of the text tokens (light and dark) listed in the handoff.

---

## S-05 · Products UI — Wave 2 (FE-2) · size L
**Files:** `resources/js/pages/products/**` (+ tests), `resources/js/components/products/**` (+ tests), `resources/js/test/fixtures/products.ts`.
Mockups: `Products.dc.html`, `ProductDetail.dc.html`, `ProductForm.dc.html`.
1. `products/index`: header + "Add product"; filters (search debounced 300 ms, category, status, "Can't produce" toggle) synced to the URL; table ≥ md:
   Product (thumb, name, `SKU · N materials` in mono), Category, Status badge, Unit cost, **Price · margin**, Can produce (`CapacityBadge`),
   Bottleneck (link to the material), Sold (30 d), actions (view, edit); stacked cards < md; empty states (no products / no match); pagination.
2. `products/show`: breadcrumb; header (name, SKU, category, status) + Record sale (→ `sales.index?record=1&record_product_id=ID`), Edit, Delete
   (`ConfirmDeleteDialog` → `router.delete`); photo (portrait) + description; hero cards: **Can produce now** (primary surface), Unit cost,
   Sold · last 30 days (units + revenue); bottleneck callout (warning with stock, need per unit and units; danger when capacity is 0; info
   "No composition defined" with an Edit link when null; mention when the bottleneck is also below its reorder level); Cost breakdown
   (Materials "N lines, waste included", Labor, Overhead, **Unit cost**, Selling price, Margin amount · %); Composition table (material with thumb +
   SKU, qty per unit, waste, effective qty, unit cost (`precise`), line cost, in stock, units possible; bottleneck row highlighted with a
   "Bottleneck" badge (text + icon); footer: materials total + capacity); Recent sales (date, qty, unit price, total, note) + "View all"
   (→ `sales.index?product_id=ID`).
3. `products/create` + `products/edit`: `ProductForm` with `useForm<ProductFormData>`: Details (SKU mono, Name, Category, Status + hint "Only active
   products can be sold.", Description), **Costs and price** (Labor, Overhead, Selling price optional), Photo (`PhotoField`, portrait), Composition
   (`BomEditor`): searchable material combobox (`Command` + `Popover`) showing stock and unit cost and hiding materials already used, quantity
   with the material's unit as suffix, waste % suffix, placement notes, remove, Move up / Move down, "Add material", count label, hint "Unit cost and
   capacity are recalculated from current stock when you save."; nested errors `components.N.field` on the right row; stable client row keys.
   Create posts to `products.store`; edit sends `PUT` (as `POST` + `_method=PUT` when a file is attached). Cancel links back.
4. Tests: `BomEditor` (add, remove, reorder, duplicate prevention, unit suffix, nested error), `ProductForm` (field errors, submit payload incl.
   selling price and photo, edit pre-fill from `Product`), composition table (worked example → `TND 35.754` and `€35.75` with EUR, bottleneck row),
   cost breakdown with and without selling price / with negative margin, products table states, page tests for all four pages.

## S-06-FE + S-14-FE · Dashboard, Sales, navigation — Wave 2 (FE-3) · size L
**Files:** `resources/js/pages/dashboard.tsx` (+ test), `resources/js/pages/sales/**` (+ tests), `resources/js/components/{dashboard,sales}/**` (+ tests),
`resources/js/components/app-sidebar.tsx`, `resources/js/test/fixtures/{sales,dashboard}.ts`. Mockups: `Main.dc.html`, `Sales.dc.html`.
1. Sidebar: Dashboard, Materials, **Products** (lucide `Shirt`), **Sales** (lucide `ReceiptText`).
2. Dashboard: header + period select (→ `dashboard?period=`) + "Record sale"; can't-produce alerts (`Callout` danger, `role="alert"`, "X can't be
   produced. Y is out of stock." / "…doesn't cover one unit." + See product); 5 `StatCard`s (Total sales "N sales · <period label>", Units sold
   "across N products", Inventory value "N materials", Needs restock "x low · y out of stock →" to `materials?low_stock=1`, Can't produce
   "of N products →" to `products?cant_produce=1`); Top selling products with a Units / Revenue toggle (`aria-pressed`; bar width = `percent_of_top`%
   as a CSS string, never computed); Needs restock list (thumb, name, "stock · reorder at X", `StockBadge`, View all); Recent sales table + View all sales.
   Empty states: no sales in the period; nothing to restock.
3. Sales: header + "Record sale"; totals (Total sales, Units sold, Sales recorded, Average sale or "—"); filters (search debounced, period, product);
   table (Date, Product + SKU, Qty, Unit price, Total, Note, Edit / Delete) + cards < md; pagination; empty states.
   **Record / edit sale** form: product select (active only; "Only active products are listed."), quantity (`inputMode="numeric"`), unit price
   (pre-filled from the product's selling price when a product is picked, still editable), date (default and max `app.today`), note; info "The total
   is calculated when you save. Recording a sale doesn't change material stock."; Save / Cancel. Beside the table on `lg+`, a `Sheet` below.
   Opens on load when `record` is non-null (pre-selects `record.product_id`). Delete via `ConfirmDeleteDialog`.
4. Tests: top products toggle and bar widths from props, KPI links, alerts, empty states, sale form (pre-fill price, active-only list, errors,
   PUT for edit), `record` prop opens the form, filters → `router.get`, delete flow, page tests.

## S-07-FE + S-15-FE + S-16-FE · Materials UI additions — Wave 2 (FE-4) · size L
**Files:** `resources/js/pages/materials/**` (+ tests), `resources/js/components/materials/**` except `stock-badge.tsx` (+ tests), `resources/js/test/fixtures.ts`.
Mockups: `Materials.dc.html`, `MaterialDetail.dc.html`, `MaterialForm.dc.html`.
1. Index: header description "N materials · inventory value X" from `summary`; table: thumb + name + SKU (mono), Category, In stock, Reorder at,
   Unit cost, Stock value, Status, **Used in** ("3 products" / "—"), actions **Restock** (primary style when low/out), **History**, View, Edit;
   cards < md. One inline panel per row at a time (`aria-expanded`/`aria-controls`, focus moves into the panel, Escape and Close return focus).
   **Restock panel**: `<Form {...MaterialRestockController.store.form(material.id)}>` with Quantity received (unit suffix), Unit price paid
   (currency; placeholder = current unit cost), Date (default/max `app.today`), Reference (optional), "Use this price as the new unit cost" (checked;
   always sends `update_unit_cost` 1/0), hint, Cancel / Add to stock; closes on success. **History panel**: `<Deferred data="recent_history">` with a
   skeleton fallback; entries of that material (filter by `material_id`), type badge (Restock success, Price change info, Correction warning, Created
   neutral) + text (restock "+500 pc at TND 0.350 · PO-2291", created "600 pc at TND 0.320", correction "600 pc → 400 pc (Stock count)",
   price change "TND 0.320 → TND 0.350 per pc") + "by Admin"; "Open material page".
2. Show: breadcrumb; header (name, SKU, category, `StockBadge`) + Restock (same form in a dialog), Edit, Delete (disabled with "Used in N products,
   so it can't be deleted." when `used_in_count > 0`); photo (square) + "Replace photo" (→ edit); KPIs (In stock, Reorder at, Unit cost, Stock value);
   below-reorder (warning) / out-of-stock (danger) callout; Details (Unit label, Supplier, Supplier ref. mono, Colour, Composition, Notes, Last updated);
   **What you can make with it**: intro "N products use this material…", table Product (thumb, name, status badge unless active, SKU), Needs per unit,
   This stock covers, **Can make now**, Limited by (warning badge "This material (bottleneck)" or a link to the limiting material), bottleneck rows
   highlighted; **Suggestion** callouts for rows with `is_bottleneck` ("this material limits Denim Jacket to 112 units. If you restock it, the next
   limit is Denim 12oz Indigo at 126 units." / only-material variant); empty state "Not used in any product yet."; full **History** list (newest first).
3. Create / edit: `MaterialForm` follows `MaterialForm.dc.html` (Identity, Stock and cost, Supplier and notes, Photo side card with `PhotoField`,
   square); multipart via the existing `.form()` helpers; edit shows the current photo with Replace / Remove.
4. Tests: restock form (fields, defaults, error display, `update_unit_cost` always sent), history list per type and the deferred fallback, usage
   table (bottleneck row, link vs badge, draft badge), suggestions (both variants), delete disabled reason, used-in column, photo field wiring, page tests.

---

## Commit plan (main session, after human approval; each commit green on its own)
Revised at review (2026-10-08): the preview controllers render the new pages (Inertia `ensure_pages_exist`), the pages import the S-12 shared
components, and the `domain.ts` changes force edits in the existing materials FE files. S-12, S-13a, S-13c, S-05, S-06-FE, S-14-FE, S-07-FE,
S-15-FE and S-16-FE are therefore only green **together**, so they ship as one commit. S-13b (pure domain maths) is independent.
1. `docs(plan): UI-first preview track, approved mockup, ADR-0006, costing rules for sales, margin, usage and restock` — docs, skill, tech-lead memory.
2. `feat(costing): margin, next-limit capacity and sales totals` — S-13b.
3. `feat(preview): UI-first build of the approved mockup on preview data` — S-12, S-13, S-05, S-06-FE, S-14-FE, S-07-FE, S-15-FE, S-16-FE
   (per-story file lists in the delivery report).

## Risks and mitigations
| Risk | Mitigation |
|---|---|
| Four agents in Wave 2 in one tree | Strictly disjoint file lists; Wave-2 FE tests run on Vitest only; tech-lead runs full gates after the wave |
| FE-1 needs `ProductStatus` while BE-1 writes `domain.ts` | BE-1 writes `domain.ts` first; FE-1 builds `ProductStatusBadge` last |
| Preview data drifting from the mockup | Fixture verified with exact decimals before dispatch; BE-3 asserts every "Expected" value |
| Mock code leaking into real layers | One namespace, `MOCK` docblocks, arch rule, S-17 release blocker |
| Multipart PUT with photos | `.form()` helpers / `_method=PUT` spoofing; covered by FE submit-payload tests and the tech-lead's browser click-through |

---

## Progress log (tech-lead)
- **2026-10-08 Wave 1: done.** S-13a (BE-1), S-13b (BE-2), S-12 (FE-1) handed off; gates green except `domain.ts` formatting (assigned to BE-3).
  Follow-ups from Wave 1: materials resource route needs a numeric constraint (PostgreSQL 500 on `/materials/abc`, assigned to BE-3);
  `--input` border contrast 1.46:1 < 3:1 (assigned to FE-3); test files bundled by the Inertia pages glob (follow-up F-13).
- **2026-10-08 Wave 2: dispatched** (BE-3 S-13c, FE-2 S-05, FE-3 S-06-FE/S-14-FE, FE-4 S-07/15/16-FE); all four agents were cut off by an API
  rate limit before handing off. Partial work is on disk; the tech-lead inventories it and re-dispatches the remainder ("Wave 2b").
- **2026-10-08 Wave 2b: done.** Resume briefs completed S-13c, S-05, S-06-FE/S-14-FE, S-07/15/16-FE; all four handed off green on their paths.
  Next: tech-lead review (all gates, PostgreSQL parity, diff checklist, browser click-through), then fix rounds and the delivery report.
- **2026-10-08 Review: APPROVED, 0 fix rounds.** composer test 1286 passed (pint, larastan 0), Vitest 968 passed, check / types / build green,
  PostgreSQL parity 1286 passed. The logged-in browser click-through was not possible (no test user in the dev DB; agents don't create accounts):
  handed to the human. MINOR/NIT follow-ups F-13 to F-21 in the backlog. Awaiting human commit approval.
