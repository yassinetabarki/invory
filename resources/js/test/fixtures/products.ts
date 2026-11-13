/**
 * Product fixtures for tests: the Phase 2 preview data (plan, "Preview
 * fixture" and its "Expected" table), written out as the server sends it.
 * Decimals are scale-4 strings, percents scale-2 strings, and every derived
 * number (costs, margins, capacity) is copied from the plan, never computed.
 *
 * Material ids follow the seeded order: rivet 1 … label 12.
 */
import { paginate } from '@/test/fixtures';
import { colourNamed } from '@/test/fixtures/colours';
import type {
    Colour,
    DecimalRange,
    FinishedStock,
    MaterialOption,
    MaterialRef,
    Option,
    Paginated,
    Product,
    ProductBarcodes,
    ProductCategory,
    ProductComponentLine,
    ProductFilters,
    ProductListItem,
    ProductStatus,
    Sale,
    Size,
    SizeOption,
    UnitOfMeasure,
    VariantBarcode,
} from '@/types/domain';

/** A figure that does not vary over the colours and sizes: lowest = highest (INV-16). */
export function same(value: string): DecimalRange {
    return { min: value, max: value };
}

// ---------------------------------------------------------------------------
// Materials
// ---------------------------------------------------------------------------

type CatalogueEntry = {
    option: MaterialOption;
    reorder_level: string;
    photo_url: string | null;
};

function entry(
    id: number,
    sku: string,
    name: string,
    unit: UnitOfMeasure,
    unit_label: string,
    unit_cost: string,
    quantity_on_hand: string,
    stock_status: MaterialOption['stock_status'],
    reorder_level: string,
    photo_url: string | null,
): CatalogueEntry {
    return {
        option: {
            id,
            sku,
            name,
            unit,
            unit_label,
            unit_cost,
            quantity_on_hand,
            stock_status,
        },
        reorder_level,
        photo_url,
    };
}

const pcLabel = 'Piece (pc)';
const mLabel = 'Metre (m)';

const catalogue = {
    rivet: entry(
        1,
        'MAT-RVT-009',
        'Brass rivet 9mm',
        'pc',
        pcLabel,
        '0.0800',
        '5000.0000',
        'in_stock',
        '1000.0000',
        '/mock/photos/mat-rvt-009.svg',
    ),
    canvas: entry(
        2,
        'MAT-CAN-010',
        'Cotton canvas 10oz Natural',
        'm',
        mLabel,
        '5.4000',
        '300.0000',
        'in_stock',
        '60.0000',
        '/mock/photos/mat-can-010.svg',
    ),
    jersey: entry(
        3,
        'MAT-JRS-180',
        'Cotton jersey 180gsm White',
        'm',
        mLabel,
        '4.2000',
        '64.0000',
        'low',
        '80.0000',
        '/mock/photos/mat-jrs-180.svg',
    ),
    denim: entry(
        4,
        'MAT-DEN-012',
        'Denim 12oz Indigo',
        'm',
        mLabel,
        '8.5000',
        '250.0000',
        'in_stock',
        '100.0000',
        '/mock/photos/mat-den-012.svg',
    ),
    button: entry(
        5,
        'MAT-BTN-017',
        'Metal shank button 17mm',
        'pc',
        pcLabel,
        '0.3500',
        '900.0000',
        'low',
        '1000.0000',
        '/mock/photos/mat-btn-017.svg',
    ),
    zipper: entry(
        6,
        'MAT-ZIP-018',
        'Metal zipper 18cm',
        'pc',
        pcLabel,
        '0.9500',
        '1250.0000',
        'in_stock',
        '300.0000',
        '/mock/photos/mat-zip-018.svg',
    ),
    nappa: entry(
        7,
        'MAT-LTH-NAP',
        'Nappa leather Cognac',
        'sqft',
        'Square foot (sqft)',
        '6.8000',
        '540.0000',
        'in_stock',
        '150.0000',
        '/mock/photos/mat-lth-nap.svg',
    ),
    thread: entry(
        8,
        'MAT-THR-PES',
        'Polyester thread',
        'm',
        mLabel,
        '0.0040',
        '20000.0000',
        'in_stock',
        '5000.0000',
        '/mock/photos/mat-thr-pes.svg',
    ),
    cupsole: entry(
        9,
        'MAT-SOL-042',
        'Rubber cupsole 42',
        'pair',
        'Pair (pair)',
        '5.9000',
        '0.0000',
        'out',
        '40.0000',
        '/mock/photos/mat-sol-042.svg',
    ),
    lining: entry(
        10,
        'MAT-LIN-VIS',
        'Viscose lining Black',
        'm',
        mLabel,
        '3.1000',
        '180.0000',
        'in_stock',
        '50.0000',
        null,
    ),
    laces: entry(
        11,
        'MAT-LAC-120',
        'Waxed cotton laces 120cm',
        'pair',
        'Pair (pair)',
        '0.4500',
        '600.0000',
        'in_stock',
        '200.0000',
        null,
    ),
    label: entry(
        12,
        'MAT-LBL-WOV',
        'Woven brand label',
        'pc',
        pcLabel,
        '0.1200',
        '400.0000',
        'low',
        '500.0000',
        '/mock/photos/mat-lbl-wov.svg',
    ),
};

type CatalogueKey = keyof typeof catalogue;

/** Every material the composition editor can pick, by name. */
export const materialOptions: MaterialOption[] = Object.values(catalogue).map(
    (item) => item.option,
);

/**
 * What `GET /materials/options` answers for `params` (the request's query),
 * like the server does: the first `limit` (default 20) materials whose name or
 * SKU contains the search text (case-insensitive), by name then id, followed
 * by the `include`d ones that were not among them.
 */
export function answerMaterialOptions(
    params: Record<string, string | string[]>,
    catalogueRows: readonly MaterialOption[] = materialOptions,
): MaterialOption[] {
    const search = typeof params.search === 'string' ? params.search : '';
    const limit = typeof params.limit === 'string' ? Number(params.limit) : 20;
    const include = (Array.isArray(params.include) ? params.include : []).map(
        Number,
    );

    const byName = [...catalogueRows].sort(
        (a, b) => a.name.localeCompare(b.name) || a.id - b.id,
    );
    const matches = byName
        .filter(
            (row) =>
                row.name.toLowerCase().includes(search.toLowerCase()) ||
                row.sku.toLowerCase().includes(search.toLowerCase()),
        )
        .slice(0, limit);
    const extra = byName.filter(
        (row) => include.includes(row.id) && !matches.includes(row),
    );

    return [...matches, ...extra];
}

/** The reference the capacity block carries for a material. */
export function materialRef(key: CatalogueKey): MaterialRef {
    const { id, sku, name, unit } = catalogue[key].option;

    return { id, sku, name, unit };
}

type LineValues = {
    quantity: string;
    waste_percent: string;
    effective_quantity: string;
    line_cost: string;
    units_possible: number;
    notes: string;
    is_bottleneck?: boolean;
};

/** One composition line: the material's data comes from the catalogue above. */
function line(
    productId: number,
    position: number,
    key: CatalogueKey,
    values: LineValues,
): ProductComponentLine {
    const { option, reorder_level, photo_url } = catalogue[key];

    return {
        id: productId * 100 + position,
        position,
        material: {
            id: option.id,
            sku: option.sku,
            name: option.name,
            unit: option.unit,
            photo_url,
            stock_status: option.stock_status,
            reorder_level,
            stock_mode: 'single',
            cost_mode: 'same',
        },
        quantity_mode: 'same',
        quantities: [],
        quantity: values.quantity,
        waste_percent: values.waste_percent,
        effective_quantity: values.effective_quantity,
        unit_cost: option.unit_cost,
        line_cost: values.line_cost,
        stock_on_hand: option.quantity_on_hand,
        stock_by_colour: null,
        units_possible: values.units_possible,
        is_bottleneck: values.is_bottleneck ?? false,
        notes: values.notes,
    };
}

/**
 * A "By colour and size" line (INV-16): Denim 12oz Black, used in Black only
 * (S 1.6000 · M 1.8000 · L 2.0000 · XL 2.2000). Quantity, effective quantity,
 * line cost and units possible are null ("Varies"); waste and stock stay single values.
 */
export const denimBlackByVariantLine: ProductComponentLine = {
    id: 190,
    position: 5,
    material: {
        id: 13,
        sku: 'MAT-DEN-013',
        name: 'Denim 12oz Black',
        unit: 'm',
        photo_url: null,
        stock_status: 'in_stock',
        reorder_level: '10.0000',
        stock_mode: 'single',
        cost_mode: 'same',
    },
    quantity_mode: 'by_variant',
    quantities: [
        { colour_id: 8, size_id: 1, quantity: '1.6000' },
        { colour_id: 8, size_id: 2, quantity: '1.8000' },
        { colour_id: 8, size_id: 3, quantity: '2.0000' },
        { colour_id: 8, size_id: 4, quantity: '2.2000' },
    ],
    quantity: null,
    waste_percent: '10.00',
    effective_quantity: null,
    unit_cost: '8.5000',
    line_cost: null,
    stock_on_hand: '40.0000',
    stock_by_colour: null,
    units_possible: null,
    is_bottleneck: false,
    notes: null,
};

// ---------------------------------------------------------------------------
// Options
// ---------------------------------------------------------------------------

export const productCategories: Option<ProductCategory>[] = [
    { value: 'apparel', label: 'Apparel' },
    { value: 'footwear', label: 'Footwear' },
    { value: 'bag', label: 'Bag' },
    { value: 'accessory', label: 'Accessory' },
    { value: 'jewelry', label: 'Jewelry' },
    { value: 'other', label: 'Other' },
];

export const productStatuses: Option<ProductStatus>[] = [
    { value: 'draft', label: 'Draft' },
    { value: 'active', label: 'Active' },
    { value: 'archived', label: 'Archived' },
];

export const noProductFilters: ProductFilters = {
    search: null,
    category: null,
    status: null,
    cant_produce: false,
};

// ---------------------------------------------------------------------------
// Sizes: ids as the migration numbers them. A "list" is only a test helper that
// groups the sizes of a fixture product (INV-18 dropped the size list from the contract).
// ---------------------------------------------------------------------------

export type SizeList = { code: string; name: string; sizes: Size[] };

export const sizeListSToXl: SizeList = {
    code: 's_to_xl',
    name: 'S, M, L, XL',
    sizes: [
        { id: 1, label: 'S' },
        { id: 2, label: 'M' },
        { id: 3, label: 'L' },
        { id: 4, label: 'XL' },
    ],
};

export const sizeListEu36To46: SizeList = {
    code: 'eu_36_to_46',
    name: '36 to 46',
    sizes: [36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46].map((size, index) => ({
        id: 5 + index,
        label: String(size),
    })),
};

export const sizeListOneSize: SizeList = {
    code: 'one_size',
    name: 'One size',
    sizes: [{ id: 16, label: 'One size' }],
};

/** Every size of the workshop, as the create and edit pages receive them (INV-18). */
export const sizeOptionsFixture: SizeOption[] = [
    ...sizeListSToXl.sizes,
    ...sizeListEu36To46.sizes,
    ...sizeListOneSize.sizes,
].map((size) => ({ ...size, one_size: size.label === 'One size' }));

/** The size ids each default category suggests, as the create and edit pages receive them. */
export const categorySizesFixture: Record<ProductCategory, number[]> = {
    apparel: sizeListSToXl.sizes.map((size) => size.id),
    footwear: sizeListEu36To46.sizes.map((size) => size.id),
    bag: [16],
    accessory: [16],
    jewelry: [16],
    other: [16],
};

/** A new workshop's list as far as the form tests need it: XS … XXXL (ids 1-4 are S … XL), 36 … 46, One size. */
export const fullSizeOptionsFixture: SizeOption[] = [
    { id: 21, label: 'XS', one_size: false },
    ...sizeOptionsFixture.slice(0, 4),
    { id: 22, label: 'XXL', one_size: false },
    { id: 23, label: 'XXXL', one_size: false },
    ...sizeOptionsFixture.slice(4),
];

/** Apparel suggests XS to XXXL (7 sizes), as a new workshop does. */
export const fullCategorySizesFixture: Record<ProductCategory, number[]> = {
    ...categorySizesFixture,
    apparel: [21, 1, 2, 3, 4, 22, 23],
};

/** The first colour of every workshop: the one products recorded before colours got (INV-1). */
const white: Colour = {
    id: 1,
    code: 'WHT',
    name: 'White',
    swatch: '#FFFFFF',
};

// ---------------------------------------------------------------------------
// Products (full)
// ---------------------------------------------------------------------------

/** The golden example: unit cost 35.7540, 112 units, limited by the button. */
export const denimJacket: Product = {
    id: 1,
    sku: 'PRD-JKT-001',
    name: 'Denim Jacket',
    category: 'apparel',
    category_label: 'Apparel',
    sizes: sizeListSToXl.sizes,
    one_size: false,
    sizes_line: 'Sizes S, M, L, XL',
    colours: [white],
    status: 'active',
    status_label: 'Active',
    description:
        'Classic trucker jacket in 12oz indigo denim with metal shank buttons.',
    photo_url: '/mock/photos/prd-jkt-001.svg',
    labor_cost: '12.0000',
    overhead_cost: '3.5000',
    selling_price: '89.0000',
    cost: {
        material_cost: same('20.2540'),
        labor_cost: '12.0000',
        overhead_cost: '3.5000',
        unit_cost: same('35.7540'),
    },
    margin: { amount: same('53.2460'), percent: same('59.83') },
    capacity: {
        max_producible: 112,
        bottleneck: materialRef('button'),
        bottleneck_colour: null,
        bottleneck_missing: false,
        bottleneck_colour_status: null,
        bottleneck_stock: catalogue.button.option.quantity_on_hand,
        best: 112,
        by_variant: null,
    },
    capacity_by_colour: null,
    components: [
        line(1, 1, 'denim', {
            quantity: '1.8000',
            waste_percent: '10.00',
            effective_quantity: '1.9800',
            line_cost: '16.8300',
            units_possible: 126,
            notes: 'Body, sleeves, collar',
        }),
        line(1, 2, 'button', {
            quantity: '8.0000',
            waste_percent: '0.00',
            effective_quantity: '8.0000',
            line_cost: '2.8000',
            units_possible: 112,
            notes: 'Front placket (6), cuffs (2)',
            is_bottleneck: true,
        }),
        line(1, 3, 'thread', {
            quantity: '120.0000',
            waste_percent: '5.00',
            effective_quantity: '126.0000',
            line_cost: '0.5040',
            units_possible: 158,
            notes: 'All seams',
        }),
        line(1, 4, 'label', {
            quantity: '1.0000',
            waste_percent: '0.00',
            effective_quantity: '1.0000',
            line_cost: '0.1200',
            units_possible: 400,
            notes: 'Inside back yoke',
        }),
    ],
    created_at: '2026-09-01T09:00:00Z',
    updated_at: '2026-10-01T09:00:00Z',
};

// ---------------------------------------------------------------------------
// Finished stock per colour and size (INV-2)
// ---------------------------------------------------------------------------

/**
 * The stock the server sends: one row per colour (by name, in list order) with
 * its units per size; the totals per size and in all are what the server adds.
 */
export function stockByColour(
    list: SizeList,
    rows: [colour: string, units: number[]][],
): FinishedStock {
    const colours = rows.map(([name, units]) => ({
        colour: colourNamed(name),
        units: units.reduce((sum, count) => sum + count, 0),
        sizes: list.sizes.map((size, index) => ({
            size,
            units: units[index] ?? 0,
        })),
    }));

    return {
        units: colours.reduce((sum, { units }) => sum + units, 0),
        sizes: list.sizes.map((size, index) => ({
            size,
            units: colours.reduce(
                (sum, { sizes }) => sum + (sizes[index]?.units ?? 0),
                0,
            ),
        })),
        colours,
    };
}

/** The worked example: Black 1 · 0 · 2 · 1, Indigo 2 · 0 · 3 · 1, Light wash 0 (S · M · L · XL) → 10 units. */
export const denimStockByColour = stockByColour(sizeListSToXl, [
    ['Black', [1, 0, 2, 1]],
    ['Indigo', [2, 0, 3, 1]],
    ['Light wash', [0, 0, 0, 0]],
]);

/** The Leather Tote ("One size"): Camel 4, Black 0. */
export const toteStockByColour = stockByColour(sizeListOneSize, [
    ['Camel', [4]],
    ['Black', [0]],
]);

// ---------------------------------------------------------------------------
// Barcodes per colour and size (INV-8)
// ---------------------------------------------------------------------------

/**
 * The barcodes the server sends: one row per colour (by name, in list order)
 * with the number of each size (`null` = none yet) and how many are missing.
 * The totals are written out, never added up here. Variant SKUs follow the
 * server's rule ("PRD-JKT-001-IND-S", "PRD-TOT-001-CAM" for "One size").
 */
export function barcodesByColour(
    sku: string,
    list: SizeList,
    rows: [colour: string, numbers: (string | null)[], missing: number][],
    totals: Pick<ProductBarcodes, 'missing' | 'missing_names' | 'with_barcode'>,
): ProductBarcodes {
    return {
        ...totals,
        colours: rows.map(([name, numbers, missing]) => {
            const colour = colourNamed(name);

            return {
                colour,
                missing,
                variants: list.sizes.map((size, index): VariantBarcode => ({
                    size,
                    sku:
                        list.sizes.length === 1
                            ? `${sku}-${colour.code}`
                            : `${sku}-${colour.code}-${size.label}`,
                    barcode: numbers[index] ?? null,
                    shared_with: [],
                })),
            };
        }),
    };
}

/** The worked example: Black and Indigo made, Light wash not → missing 4, with a barcode 8. */
export const denimBarcodes = barcodesByColour(
    'PRD-JKT-001',
    sizeListSToXl,
    [
        [
            'Black',
            [
                '2000000000053',
                '2000000000060',
                '2000000000077',
                '2000000000084',
            ],
            0,
        ],
        [
            'Indigo',
            [
                '2000000000015',
                '2000000000022',
                '2000000000039',
                '2000000000046',
            ],
            0,
        ],
        ['Light wash', [], 4],
    ],
    {
        missing: 4,
        missing_names: 'Light wash S, M, L and XL',
        with_barcode: 8,
    },
);

/** The Leather Tote ("One size") in Camel and Rust: Camel made, Rust not. */
export const toteBarcodes = barcodesByColour(
    'PRD-TOT-001',
    sizeListOneSize,
    [
        ['Camel', ['2000000000091'], 0],
        ['Rust', [], 1],
    ],
    { missing: 1, missing_names: 'Rust', with_barcode: 1 },
);

/** The Leather Tote's stock for those two colours: Camel 4, Rust 0. */
export const toteCamelRustStock = stockByColour(sizeListOneSize, [
    ['Camel', [4]],
    ['Rust', [0]],
]);

/** Out of stock: the cupsole is at 0, so nothing can be produced. */
export const canvasLowSneaker: Product = {
    id: 3,
    sku: 'PRD-SNK-003',
    name: 'Canvas Low Sneaker',
    category: 'footwear',
    category_label: 'Footwear',
    sizes: sizeListEu36To46.sizes,
    one_size: false,
    sizes_line: 'Sizes 36 to 46',
    colours: [white],
    status: 'active',
    status_label: 'Active',
    description: 'Vulcanised low-top sneaker in natural canvas.',
    photo_url: '/mock/photos/prd-snk-003.svg',
    labor_cost: '9.3000',
    overhead_cost: '2.0000',
    selling_price: '69.0000',
    cost: {
        material_cost: same('10.4990'),
        labor_cost: '9.3000',
        overhead_cost: '2.0000',
        unit_cost: same('21.7990'),
    },
    margin: { amount: same('47.2010'), percent: same('68.41') },
    capacity: {
        max_producible: 0,
        bottleneck: materialRef('cupsole'),
        bottleneck_colour: null,
        bottleneck_missing: false,
        bottleneck_colour_status: null,
        bottleneck_stock: catalogue.cupsole.option.quantity_on_hand,
        best: 0,
        by_variant: null,
    },
    capacity_by_colour: null,
    components: [
        line(3, 1, 'canvas', {
            quantity: '0.6500',
            waste_percent: '10.00',
            effective_quantity: '0.7150',
            line_cost: '3.8610',
            units_possible: 419,
            notes: 'Upper',
        }),
        line(3, 2, 'cupsole', {
            quantity: '1.0000',
            waste_percent: '0.00',
            effective_quantity: '1.0000',
            line_cost: '5.9000',
            units_possible: 0,
            notes: 'Outsole',
            is_bottleneck: true,
        }),
        line(3, 3, 'laces', {
            quantity: '1.0000',
            waste_percent: '0.00',
            effective_quantity: '1.0000',
            line_cost: '0.4500',
            units_possible: 600,
            notes: 'Laces',
        }),
        line(3, 4, 'thread', {
            quantity: '40.0000',
            waste_percent: '5.00',
            effective_quantity: '42.0000',
            line_cost: '0.1680',
            units_possible: 476,
            notes: 'Upper seams',
        }),
        line(3, 5, 'label', {
            quantity: '1.0000',
            waste_percent: '0.00',
            effective_quantity: '1.0000',
            line_cost: '0.1200',
            units_possible: 400,
            notes: 'Tongue',
        }),
    ],
    created_at: '2026-09-02T09:00:00Z',
    updated_at: '2026-10-02T09:00:00Z',
};

/** A draft with no photo and no selling price (so no margin). */
export const choreJacket: Product = {
    id: 6,
    sku: 'PRD-JKT-006',
    name: 'Canvas Chore Jacket',
    category: 'apparel',
    category_label: 'Apparel',
    sizes: sizeListSToXl.sizes,
    one_size: false,
    sizes_line: 'Sizes S, M, L, XL',
    colours: [white],
    status: 'draft',
    status_label: 'Draft',
    description: null,
    photo_url: null,
    labor_cost: '14.5000',
    overhead_cost: '3.0400',
    selling_price: null,
    cost: {
        material_cost: same('14.8900'),
        labor_cost: '14.5000',
        overhead_cost: '3.0400',
        unit_cost: same('32.4300'),
    },
    margin: null,
    capacity: {
        max_producible: 129,
        bottleneck: materialRef('canvas'),
        bottleneck_colour: null,
        bottleneck_missing: false,
        bottleneck_colour_status: null,
        bottleneck_stock: catalogue.canvas.option.quantity_on_hand,
        best: 129,
        by_variant: null,
    },
    capacity_by_colour: null,
    components: [
        line(6, 1, 'canvas', {
            quantity: '2.1000',
            waste_percent: '10.00',
            effective_quantity: '2.3100',
            line_cost: '12.4740',
            units_possible: 129,
            notes: 'Body, sleeves, pockets',
            is_bottleneck: true,
        }),
        line(6, 2, 'button', {
            quantity: '5.0000',
            waste_percent: '0.00',
            effective_quantity: '5.0000',
            line_cost: '1.7500',
            units_possible: 180,
            notes: 'Front',
        }),
        line(6, 3, 'thread', {
            quantity: '130.0000',
            waste_percent: '5.00',
            effective_quantity: '136.5000',
            line_cost: '0.5460',
            units_possible: 146,
            notes: 'All seams',
        }),
        line(6, 4, 'label', {
            quantity: '1.0000',
            waste_percent: '0.00',
            effective_quantity: '1.0000',
            line_cost: '0.1200',
            units_possible: 400,
            notes: 'Neck',
        }),
    ],
    created_at: '2026-09-03T09:00:00Z',
    updated_at: '2026-10-03T09:00:00Z',
};

/** The chore jacket before anyone added a material: no capacity, no bottleneck. */
export const jacketWithoutComposition: Product = {
    ...choreJacket,
    cost: {
        material_cost: same('0.0000'),
        labor_cost: '14.5000',
        overhead_cost: '3.0400',
        unit_cost: same('17.5400'),
    },
    capacity: {
        max_producible: null,
        bottleneck: null,
        bottleneck_colour: null,
        bottleneck_missing: false,
        bottleneck_colour_status: null,
        bottleneck_stock: null,
        best: null,
        by_variant: null,
    },
    capacity_by_colour: null,
    components: [],
};

const black: Colour = { id: 8, code: 'BLK', name: 'Black', swatch: '#1F1F23' };
const indigo: Colour = {
    id: 10,
    code: 'IND',
    name: 'Indigo',
    swatch: '#3E4C7E',
};
const sizeXl: Size = { id: 4, label: 'XL' };

/** Denim 12oz Black, the By colour and size line's material. */
export const denimBlackRef: MaterialRef = {
    id: 13,
    sku: 'MAT-DEN-013',
    name: 'Denim 12oz Black',
    unit: 'm',
};

/**
 * The card's example (INV-16): the Denim Jacket in Black and Indigo with the
 * Black denim By colour and size and 40 m on hand. Guaranteed 16 (Black, XL,
 * limited by the Black denim), best 112 (Indigo, limited by the button).
 */
export const denimJacketVarying: Product = {
    ...denimJacket,
    colours: [black, indigo],
    cost: {
        material_cost: { min: '18.3840', max: '23.9940' },
        labor_cost: '12.0000',
        overhead_cost: '3.5000',
        unit_cost: { min: '33.8840', max: '39.4940' },
    },
    margin: {
        amount: { min: '49.5060', max: '55.1160' },
        percent: { min: '55.62', max: '61.93' },
    },
    capacity: {
        max_producible: 16,
        bottleneck: denimBlackRef,
        bottleneck_colour: null,
        bottleneck_missing: false,
        bottleneck_colour_status: null,
        bottleneck_stock: '40.0000',
        best: 112,
        by_variant: {
            binding: { colour: black, size: sizeXl },
            best_at: { colour: indigo, bottleneck: materialRef('button') },
        },
    },
    capacity_by_colour: [
        {
            colour: black,
            guaranteed: 16,
            best: 22,
            bottleneck: denimBlackRef,
            bottleneck_colour: null,
            bottleneck_missing: false,
            binding_size: sizeXl,
        },
        {
            colour: indigo,
            guaranteed: 103,
            best: 112,
            bottleneck: materialRef('denim'),
            bottleneck_colour: null,
            bottleneck_missing: false,
            binding_size: sizeXl,
        },
    ],
    components: [
        ...denimJacket.components.map((component) => ({
            ...component,
            is_bottleneck: false,
        })),
        { ...denimBlackByVariantLine, is_bottleneck: true },
    ],
};

const lightWash: Colour = {
    id: 11,
    code: 'LWH',
    name: 'Light wash',
    swatch: '#9DB3D1',
};

/** Denim 12oz, one material kept per colour (INV-21): Indigo 250 m, Black 40 m. */
export const denimPerColourRef: MaterialRef = {
    id: 14,
    sku: 'MAT-DEN-014',
    name: 'Denim 12oz',
    unit: 'm',
};

/** The Denim Jacket's denim line by colour and size on that material: Indigo covers 103–142 units, Black 16–22. */
export const denimPerColourLine: ProductComponentLine = {
    ...denimBlackByVariantLine,
    id: 191,
    material: {
        ...denimBlackByVariantLine.material,
        ...denimPerColourRef,
        stock_status: 'low',
        reorder_level: '100.0000',
        stock_mode: 'per_colour',
        cost_mode: 'per_colour',
    },
    stock_on_hand: '290.0000',
    stock_by_colour: [
        {
            colour: indigo,
            missing: false,
            stock_on_hand: '250.0000',
            units_possible: { min: 103, max: 142 },
        },
        {
            colour: black,
            missing: false,
            stock_on_hand: '40.0000',
            units_possible: { min: 16, max: 22 },
        },
    ],
    is_bottleneck: true,
};

/**
 * The card's example (INV-21): the Denim Jacket in Indigo and Black on one
 * per-colour Denim 12oz. Guaranteed 16 (Black, XL, limited by the Black stock),
 * best 112 (Indigo, limited by the button).
 */
export const denimJacketPerColour: Product = {
    ...denimJacketVarying,
    colours: [indigo, black],
    capacity: {
        max_producible: 16,
        bottleneck: denimPerColourRef,
        bottleneck_colour: black,
        bottleneck_missing: false,
        bottleneck_colour_status: 'low',
        bottleneck_stock: '40.0000',
        best: 112,
        by_variant: {
            binding: { colour: black, size: sizeXl },
            best_at: { colour: indigo, bottleneck: materialRef('button') },
        },
    },
    capacity_by_colour: [
        {
            colour: indigo,
            guaranteed: 103,
            best: 112,
            bottleneck: denimPerColourRef,
            bottleneck_colour: indigo,
            bottleneck_missing: false,
            binding_size: sizeXl,
        },
        {
            colour: black,
            guaranteed: 16,
            best: 22,
            bottleneck: denimPerColourRef,
            bottleneck_colour: black,
            bottleneck_missing: false,
            binding_size: sizeXl,
        },
    ],
    components: [
        ...denimJacket.components
            .slice(1)
            .map((component) => ({ ...component, is_bottleneck: false })),
        denimPerColourLine,
    ],
};

/** The same jacket with Light wash added to it and Denim 12oz without Light wash: nothing in Light wash can be made. */
export const denimJacketLightWash: Product = {
    ...denimJacketPerColour,
    colours: [indigo, black, lightWash],
    capacity: {
        max_producible: 0,
        bottleneck: denimPerColourRef,
        bottleneck_colour: lightWash,
        bottleneck_missing: true,
        bottleneck_colour_status: 'out',
        bottleneck_stock: '0.0000',
        best: 112,
        by_variant: {
            binding: { colour: lightWash, size: sizeXl },
            best_at: { colour: indigo, bottleneck: materialRef('button') },
        },
    },
    capacity_by_colour: [
        ...(denimJacketPerColour.capacity_by_colour ?? []),
        {
            colour: lightWash,
            guaranteed: 0,
            best: 0,
            bottleneck: denimPerColourRef,
            bottleneck_colour: lightWash,
            bottleneck_missing: true,
            binding_size: sizeXl,
        },
    ],
    components: [
        ...denimJacketPerColour.components.slice(0, -1),
        {
            ...denimPerColourLine,
            stock_by_colour: [
                ...(denimPerColourLine.stock_by_colour ?? []),
                {
                    colour: lightWash,
                    missing: true,
                    stock_on_hand: '0.0000',
                    units_possible: { min: 0, max: 0 },
                },
            ],
        },
    ],
};

/** Sold below cost: 30.0000 against a unit cost of 35.7540. */
export const denimJacketBelowCost: Product = {
    ...denimJacket,
    selling_price: '30.0000',
    margin: { amount: same('-5.7540'), percent: same('-19.18') },
};

/** A zero selling price has an amount but no percentage. */
export const denimJacketGiveaway: Product = {
    ...denimJacket,
    selling_price: '0.0000',
    margin: { amount: same('-35.7540'), percent: null },
};

// ---------------------------------------------------------------------------
// Products (list rows)
// ---------------------------------------------------------------------------

export const denimJacketItem: ProductListItem = {
    id: 1,
    sku: 'PRD-JKT-001',
    name: 'Denim Jacket',
    category: 'apparel',
    category_label: 'Apparel',
    status: 'active',
    status_label: 'Active',
    photo_url: '/mock/photos/prd-jkt-001.svg',
    components_count: 4,
    unit_cost: same('35.7540'),
    selling_price: '89.0000',
    margin: { amount: same('53.2460'), percent: same('59.83') },
    capacity: {
        max_producible: 112,
        bottleneck: materialRef('button'),
        bottleneck_colour: null,
        bottleneck_missing: false,
        bottleneck_colour_status: null,
        bottleneck_stock: catalogue.button.option.quantity_on_hand,
        best: 112,
        by_variant: null,
    },
    units_sold_last_30_days: 38,
};

export const teeItem: ProductListItem = {
    id: 2,
    sku: 'PRD-TSH-002',
    name: 'Classic White T-shirt',
    category: 'apparel',
    category_label: 'Apparel',
    status: 'active',
    status_label: 'Active',
    photo_url: '/mock/photos/prd-tsh-002.svg',
    components_count: 3,
    unit_cost: same('9.7484'),
    selling_price: '24.0000',
    margin: { amount: same('14.2516'), percent: same('59.38') },
    capacity: {
        max_producible: 50,
        bottleneck: materialRef('jersey'),
        bottleneck_colour: null,
        bottleneck_missing: false,
        bottleneck_colour_status: null,
        bottleneck_stock: catalogue.jersey.option.quantity_on_hand,
        best: 50,
        by_variant: null,
    },
    units_sold_last_30_days: 142,
};

export const sneakerItem: ProductListItem = {
    id: 3,
    sku: 'PRD-SNK-003',
    name: 'Canvas Low Sneaker',
    category: 'footwear',
    category_label: 'Footwear',
    status: 'active',
    status_label: 'Active',
    photo_url: '/mock/photos/prd-snk-003.svg',
    components_count: 5,
    unit_cost: same('21.7990'),
    selling_price: '69.0000',
    margin: { amount: same('47.2010'), percent: same('68.41') },
    capacity: {
        max_producible: 0,
        bottleneck: materialRef('cupsole'),
        bottleneck_colour: null,
        bottleneck_missing: false,
        bottleneck_colour_status: null,
        bottleneck_stock: catalogue.cupsole.option.quantity_on_hand,
        best: 0,
        by_variant: null,
    },
    units_sold_last_30_days: 9,
};

export const toteItem: ProductListItem = {
    id: 4,
    sku: 'PRD-TOT-004',
    name: 'Leather Tote',
    category: 'bag',
    category_label: 'Bag',
    status: 'active',
    status_label: 'Active',
    photo_url: '/mock/photos/prd-tot-004.svg',
    components_count: 6,
    unit_cost: same('101.0300'),
    selling_price: '249.0000',
    margin: { amount: same('147.9700'), percent: same('59.43') },
    capacity: {
        max_producible: 53,
        bottleneck: materialRef('nappa'),
        bottleneck_colour: null,
        bottleneck_missing: false,
        bottleneck_colour_status: null,
        bottleneck_stock: catalogue.nappa.option.quantity_on_hand,
        best: 53,
        by_variant: null,
    },
    units_sold_last_30_days: 12,
};

export const jeansItem: ProductListItem = {
    id: 5,
    sku: 'PRD-JNS-005',
    name: 'Denim Jeans 5-pocket',
    category: 'apparel',
    category_label: 'Apparel',
    status: 'active',
    status_label: 'Active',
    photo_url: '/mock/photos/prd-jns-005.svg',
    components_count: 6,
    unit_cost: same('29.4780'),
    selling_price: '79.0000',
    margin: { amount: same('49.5220'), percent: same('62.69') },
    capacity: {
        max_producible: 105,
        bottleneck: materialRef('thread'),
        bottleneck_colour: null,
        bottleneck_missing: false,
        bottleneck_colour_status: null,
        bottleneck_stock: catalogue.thread.option.quantity_on_hand,
        best: 105,
        by_variant: null,
    },
    units_sold_last_30_days: 64,
};

export const choreJacketItem: ProductListItem = {
    id: 6,
    sku: 'PRD-JKT-006',
    name: 'Canvas Chore Jacket',
    category: 'apparel',
    category_label: 'Apparel',
    status: 'draft',
    status_label: 'Draft',
    photo_url: null,
    components_count: 4,
    unit_cost: same('32.4300'),
    selling_price: null,
    margin: null,
    capacity: {
        max_producible: 129,
        bottleneck: materialRef('canvas'),
        bottleneck_colour: null,
        bottleneck_missing: false,
        bottleneck_colour_status: null,
        bottleneck_stock: catalogue.canvas.option.quantity_on_hand,
        best: 129,
        by_variant: null,
    },
    units_sold_last_30_days: 0,
};

export const beltItem: ProductListItem = {
    id: 7,
    sku: 'PRD-BLT-007',
    name: 'Leather Belt',
    category: 'accessory',
    category_label: 'Accessory',
    status: 'active',
    status_label: 'Active',
    photo_url: '/mock/photos/prd-blt-007.svg',
    components_count: 2,
    unit_cost: same('17.0440'),
    selling_price: '45.0000',
    margin: { amount: same('27.9560'), percent: same('62.12') },
    capacity: {
        max_producible: 391,
        bottleneck: materialRef('nappa'),
        bottleneck_colour: null,
        bottleneck_missing: false,
        bottleneck_colour_status: null,
        bottleneck_stock: catalogue.nappa.option.quantity_on_hand,
        best: 391,
        by_variant: null,
    },
    units_sold_last_30_days: 31,
};

/** A product with no composition at all (capacity null, no bottleneck). */
export const unfinishedItem: ProductListItem = {
    ...choreJacketItem,
    id: 8,
    sku: 'PRD-NEW-008',
    name: 'Unfinished Parka',
    components_count: 0,
    unit_cost: same('17.5400'),
    capacity: {
        max_producible: null,
        bottleneck: null,
        bottleneck_colour: null,
        bottleneck_missing: false,
        bottleneck_colour_status: null,
        bottleneck_stock: null,
        best: null,
        by_variant: null,
    },
};

/** Sold below cost: a negative margin. */
export const belowCostItem: ProductListItem = {
    ...denimJacketItem,
    id: 9,
    sku: 'PRD-JKT-009',
    name: 'Clearance Jacket',
    selling_price: '30.0000',
    margin: { amount: same('-5.7540'), percent: same('-19.18') },
};

/** All seven preview products, by name (the list's order). */
export const allProductItems: ProductListItem[] = [
    choreJacketItem,
    sneakerItem,
    teeItem,
    denimJacketItem,
    jeansItem,
    beltItem,
    toteItem,
];

/** One page of products, with the products list's path. */
export function paginateProducts(
    data: ProductListItem[],
    meta: Partial<Paginated<ProductListItem>['meta']> = {},
): Paginated<ProductListItem> {
    return paginate(data, { path: '/products', ...meta });
}

// ---------------------------------------------------------------------------
// Sales
// ---------------------------------------------------------------------------

const denimRef = {
    id: 1,
    sku: 'PRD-JKT-001',
    name: 'Denim Jacket',
    photo_url: '/mock/photos/prd-jkt-001.svg',
};

function denimSale(
    id: number,
    sold_on: string,
    quantity: number,
    total: string,
    note: string | null,
): Sale {
    return {
        id,
        product: denimRef,
        quantity,
        unit_price: '89.0000',
        total,
        sold_on,
        note,
    };
}

/** The Denim Jacket's newest five sales (sale ids 57, 52, 43, 40, 38). */
export const denimRecentSales: Sale[] = [
    denimSale(57, '2026-10-05', 4, '356.0000', 'Web shop'),
    denimSale(52, '2026-10-01', 5, '445.0000', 'Showroom'),
    denimSale(43, '2026-09-26', 6, '534.0000', 'Wholesale order'),
    denimSale(40, '2026-09-25', 5, '445.0000', 'Web shop'),
    denimSale(38, '2026-09-24', 5, '445.0000', 'Wholesale order'),
];

export const denimSalesLast30Days = {
    units_sold: 38,
    revenue: '3382.0000',
};

/** A sale recorded without a note (the note column shows an em dash). */
export const denimSaleWithoutNote: Sale = denimSale(
    99,
    '2026-09-20',
    1,
    '89.0000',
    null,
);
