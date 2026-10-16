export type Decimal = string; // decimals travel as strings (scale 4). Never do maths on them in TS.

export type Option<T extends string = string> = { value: T; label: string };

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};
export type Paginated<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        links: PaginationLink[];
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
};

// Codes from the reference tables (ADR-0008): units and categories are company-managed data, not a fixed list.
export type MaterialCategory = string;
export type UnitOfMeasure = string; // a `units.code`, for example 'm'
export type StockStatus = 'in_stock' | 'low' | 'out';

export type Material = {
    id: number;
    sku: string;
    name: string;
    category: MaterialCategory;
    category_label: string;
    unit: UnitOfMeasure;
    unit_label: string;
    unit_cost: Decimal;
    quantity_on_hand: Decimal;
    reorder_level: Decimal | null;
    stock_value: Decimal; // quantity_on_hand × unit_cost, scale 4
    stock_status: StockStatus; // computed server-side, see rule below
    supplier_name: string | null;
    supplier_reference: string | null;
    color: string | null;
    composition: string | null;
    notes: string | null;
    photo_url: string | null; // public URL of the photo, or null (no photo)
    used_in_count: number; // non-deleted products whose composition uses this material
    created_at: string; // ISO-8601
    updated_at: string;
};

export type MaterialSort =
    | 'name'
    | '-name'
    | 'sku'
    | '-sku'
    | 'quantity_on_hand'
    | '-quantity_on_hand'
    | 'unit_cost'
    | '-unit_cost'
    | 'updated_at'
    | '-updated_at';

export type MaterialFilters = {
    search: string | null;
    category: MaterialCategory | null;
    low_stock: boolean; // true → stock_status in (low, out)
    sort: MaterialSort; // default 'name'
};

/** Fields the material form submits (strings for decimals). */
export type MaterialFormData = {
    sku: string;
    name: string;
    category: MaterialCategory; // '' until chosen
    unit: UnitOfMeasure; // '' until chosen
    unit_cost: string;
    quantity_on_hand: string;
    reorder_level: string;
    supplier_name: string;
    supplier_reference: string;
    color: string;
    composition: string;
    notes: string;
    photo: File | null;
    remove_photo: boolean;
};

// Page props
export type MaterialsIndexProps = {
    materials: Paginated<Material>;
    filters: MaterialFilters;
    summary: { materials_count: number; inventory_value: Decimal }; // over all non-deleted materials, not just this page
    recent_history?: StockMovement[]; // deferred prop: newest 5 movements of each material on this page
};
export type MaterialCreateProps = Record<string, never>; // categories and units come from the shared `reference` prop
export type MaterialEditProps = { material: Material };
export type MaterialShowProps = {
    material: Material;
    usage: MaterialUsage[]; // bottleneck rows first, then by product name
    history: StockMovement[]; // newest first
};

// ---------------------------------------------------------------------------
// UI-first track (2026-10-08): products, sales, photos, stock history, usage.
// Decimals: scale-4 strings · percents: scale-2 strings · dates: 'YYYY-MM-DD'.
// ---------------------------------------------------------------------------

// A `product_categories.code` (ADR-0008), for example 'apparel'.
export type ProductCategory = string;
export type ProductStatus = 'draft' | 'active' | 'archived';
export type SalesPeriod =
    | 'last_7_days'
    | 'last_30_days'
    | 'this_month'
    | 'this_year';
// `cutting_return` is labelled "Back from cutting" (it fits the 16-character `stock_movements.type` column, so no column change)
export type StockMovementType =
    | 'created'
    | 'restock'
    | 'correction'
    | 'price_change'
    | 'cutting'
    | 'cutting_return';

export type MaterialRef = {
    id: number;
    sku: string;
    name: string;
    unit: UnitOfMeasure;
};
export type ProductRef = {
    id: number;
    sku: string;
    name: string;
    photo_url: string | null;
};

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
    material: MaterialRef & {
        photo_url: string | null;
        stock_status: StockStatus;
        reorder_level: Decimal | null;
    };
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

/**
 * Query of `GET materials/options` (route `materials.options`), the picker's typeahead. The response is a bare `MaterialOption[]`:
 * the first `limit` (default 20, max 50) non-deleted materials whose name or SKU contains `search` (case-insensitive), by name then
 * id, followed by the `include`d ids (max 50) that were not among them. An `include`d id that is soft-deleted or unknown is skipped.
 */
export type MaterialOptionsQuery = {
    search?: string;
    limit?: number;
    include?: number[];
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
    category: ProductCategory; // '' until chosen
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

/** A product as an order shows and offers it. `deleted` = soft-deleted: shown by name, without a link. */
export type OrderProduct = {
    id: number;
    sku: string;
    name: string;
    status: ProductStatus;
    deleted: boolean;
};

export type OrderStage =
    | 'to_start'
    | 'in_cutting'
    | 'ready_to_ship'
    | 'shipped';
export type OrderProgress = 'not_started' | 'in_progress' | 'shipped';

export type OrderLine = {
    id: number;
    product: OrderProduct;
    quantity: number; // whole units, 1 … 1 000 000
    to_start: number; // quantity − in_cutting − ready_to_ship − shipped, computed server-side
    in_cutting: number;
    ready_to_ship: number;
    shipped: number;
    started: number; // in_cutting + ready_to_ship + shipped, computed server-side
    status: OrderProgress;
    status_label: string; // "Not started", "In progress", "Shipped"
};

export type Order = {
    id: number;
    client: string;
    ordered_on: string; // 'YYYY-MM-DD'
    note: string | null;
    lines: OrderLine[]; // in the order entered (position)
    units: number; // sum of the lines' quantities, computed server-side
    in_cutting: number; // the lines summed, server-side
    ready_to_ship: number;
    shipped: number; // "10 of 80 shipped"
    status: OrderProgress;
    status_label: string;
    // Set while units are in cutting or ready to ship: "Boutique Lina can't be deleted: 30 units are in cutting or ready to
    // ship. Move them back to To start first." (30 = in_cutting + ready_to_ship; for one: "1 unit is" and "Move it back"); null when it may be deleted
    delete_refusal: string | null;
};

export type OrderFilters = {
    search: string | null; // client, note, or a line's product name or SKU
};

export type OrderLineFormRow = {
    product_id: number | '';
    quantity: string;
};

/** Fields the order form submits. Errors come back as client, ordered_on, note, lines, lines.N.product_id, lines.N.quantity. */
export type OrderFormData = {
    client: string;
    ordered_on: string; // 'YYYY-MM-DD'
    note: string;
    lines: OrderLineFormRow[];
};

export type OrdersIndexProps = {
    orders: Paginated<Order>;
    filters: OrderFilters;
};
export type OrderCreateProps = { products: OrderProduct[] };
export type OrderEditProps = { order: Order; products: OrderProduct[] };
export type OrderShowProps = { order: Order };

/**
 * start_cutting: to_start → in_cutting · mark_ready: in_cutting → ready_to_ship · ship: ready_to_ship → shipped ·
 * back_to_start: in_cutting → to_start · back_to_cutting: ready_to_ship → in_cutting · back_to_ready: shipped → ready_to_ship
 */
export type OrderLineMove =
    | 'start_cutting'
    | 'mark_ready'
    | 'ship'
    | 'back_to_start'
    | 'back_to_cutting'
    | 'back_to_ready';

/** Fields the move window posts to `orders.lines.moves.store`. Errors come back as `units` (and `move`). */
export type MoveUnitsFormData = { move: OrderLineMove; units: string };

/** One row of the move window's materials table (S-53), worked out by the server: the screen never computes. */
export type MaterialMoveRow = {
    material: MaterialRef;
    quantity: Decimal; // start_cutting: needed · back_to_start: what those units took
    in_stock: Decimal; // the material's stock now ("In stock")
    after: Decimal | null; // "Left after" / "After return"; null when short or not returned
    short_by: Decimal | null; // start_cutting, only when the stock is short: "Short by …" (every short row)
    not_returned: 'deleted' | 'unit_changed' | null; // back_to_start: "Deleted, not returned" / "Unit changed, not returned"
};

/** `GET orders.lines.moves.preview` (`?move=start_cutting|back_to_start&units=N`): the move window's materials table. */
export type MovePreview = {
    move: 'start_cutting' | 'back_to_start';
    units: number;
    rows: MaterialMoveRow[]; // by material name
    has_composition: boolean; // start_cutting: false → "… has no composition, so no material is taken out of stock."
    nothing_taken: boolean; // back_to_start: true → "No material was taken out of stock for these units, so none is returned."
    max_cuttable: number | null; // start_cutting: "Stock on hand allows N units."; null without composition
    // start_cutting, only when the units exceed max_cuttable; written by the server, the same text as the refusal under `units`:
    //   max_cuttable ≥ 1: "Not enough stock to cut 20 units. You can cut at most 12 now: <bottleneck> runs short. Restock it first,
    //                     or cut 12 or fewer."
    //   max_cuttable = 0: "Not enough stock to cut 20 units. You can't cut any now: restock <bottleneck> first."
    short_message: string | null;
};

export type StockMovement = {
    id: number;
    material_id: number;
    type: StockMovementType;
    type_label: string; // "Created", "Restock", "Correction", "Price change", "Cutting", "Back from cutting"
    occurred_on: string; // 'YYYY-MM-DD'
    quantity: Decimal | null; // created: opening stock · restock: quantity received · cutting (taken) and cutting_return (given back): positive
    quantity_before: Decimal | null; // correction, cutting, cutting_return only
    quantity_after: Decimal | null; // stock after the entry; null for price_change
    unit_price: Decimal | null; // created: opening unit cost · restock: unit price paid
    unit_cost_before: Decimal | null; // price_change only
    unit_cost_after: Decimal | null; // price_change only
    reference: string | null; // restock: PO or delivery note
    note: string | null; // correction: reason
    units: number | null; // cutting / cutting_return: the units of the order line
    order: { id: number; client: string } | null; // cutting / cutting_return (soft-deleted orders included)
    product_name: string | null; // cutting / cutting_return (soft-deleted products included)
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
    statuses: Option<ProductStatus>[];
};
export type ProductCreateProps = {
    statuses: Option<ProductStatus>[];
};
export type ProductEditProps = ProductCreateProps & { product: Product };
export type ProductShowProps = {
    product?: Product; // deferred prop
    sales_last_30_days?: { units_sold: number; revenue: Decimal }; // deferred prop
    recent_sales?: Sale[]; // deferred prop: newest 5
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
    stats?: DashboardStats; // deferred prop (group 'primary'): KPI figures
    top_products?: { by_units: TopProduct[]; by_revenue: TopProduct[] }; // deferred prop (group 'primary'): max 5 each, for the period
    needs_restock?: Material[]; // deferred prop: max 5, out first, then low; by name
    recent_sales?: Sale[]; // deferred prop: newest 5, any period
    alerts?: UnproducibleAlert[]; // deferred prop: every product that can't be produced, by name
};

// ---------------------------------------------------------------------------
// Reference data in the database (ADR-0008, S-21): units and categories are company-managed.
// Forms and filters keep sending CODES (`category: 'fabric'`, `unit: 'm'`); `id` is for the settings screens.
// ---------------------------------------------------------------------------

export type UnitDimension =
    | 'length'
    | 'area'
    | 'mass'
    | 'volume'
    | 'count'
    | 'package';

export type ReferenceOption = {
    id: number;
    code: string; // immutable, [a-z0-9_]+
    name: string;
    position: number;
    archived: boolean;
};
export type UnitOption = ReferenceOption & {
    dimension: UnitDimension;
    dimension_label: string;
    factor_to_base: Decimal | null; // scale 12; null for `package` units. Display only, never do maths on it.
    is_system: boolean; // seeded units: dimension and factor are fixed, and they can't be deleted (only archived)
};
// The shared once-prop `reference` (`usePage().props.reference`): present only for signed-in users. Lists include archived
// rows (flagged `archived`), ordered by position then name.
export type ReferenceData = {
    units: UnitOption[];
    material_categories: ReferenceOption[];
    product_categories: ReferenceOption[];
    product_statuses: Option<ProductStatus>[];
    sales_periods: Option<SalesPeriod>[];
    stock_statuses: Option<StockStatus>[];
};

// Settings tables: usage_count = non-deleted materials that use the unit/category (product categories: preview products).
export type ReferenceRow = ReferenceOption & { usage_count: number };
export type UnitRow = UnitOption & { usage_count: number };
export type UnitsSettingsProps = {
    units: UnitRow[];
    dimensions: Option<UnitDimension>[];
};
export type MaterialCategoriesSettingsProps = { categories: ReferenceRow[] };
export type ProductCategoriesSettingsProps = { categories: ReferenceRow[] };

// Settings forms. `code` is sent on create only. `position` empty = last. System units: only name and position change.
export type UnitFormData = {
    code: string;
    name: string;
    dimension: UnitDimension | '';
    factor_to_base: string; // decimal string; empty for `package`
    position: string;
};
export type ReferenceFormData = {
    code: string;
    name: string;
    position: string;
};
