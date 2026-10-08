/**
 * Product fixtures for tests: the Phase 2 preview data (plan, "Preview
 * fixture" and its "Expected" table), written out as the server sends it.
 * Decimals are scale-4 strings, percents scale-2 strings, and every derived
 * number (costs, margins, capacity) is copied from the plan, never computed.
 *
 * Material ids follow the seeded order: rivet 1 … label 12.
 */
import { paginate } from '@/test/fixtures';
import type {
    MaterialOption,
    MaterialRef,
    Option,
    Paginated,
    Product,
    ProductCategory,
    ProductComponentLine,
    ProductFilters,
    ProductListItem,
    ProductStatus,
    Sale,
    UnitOfMeasure,
} from '@/types/domain';

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
        },
        quantity: values.quantity,
        waste_percent: values.waste_percent,
        effective_quantity: values.effective_quantity,
        unit_cost: option.unit_cost,
        line_cost: values.line_cost,
        stock_on_hand: option.quantity_on_hand,
        units_possible: values.units_possible,
        is_bottleneck: values.is_bottleneck ?? false,
        notes: values.notes,
    };
}

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
// Products (full)
// ---------------------------------------------------------------------------

/** The golden example: unit cost 35.7540, 112 units, limited by the button. */
export const denimJacket: Product = {
    id: 1,
    sku: 'PRD-JKT-001',
    name: 'Denim Jacket',
    category: 'apparel',
    category_label: 'Apparel',
    status: 'active',
    status_label: 'Active',
    description:
        'Classic trucker jacket in 12oz indigo denim with metal shank buttons.',
    photo_url: '/mock/photos/prd-jkt-001.svg',
    labor_cost: '12.0000',
    overhead_cost: '3.5000',
    selling_price: '89.0000',
    cost: {
        material_cost: '20.2540',
        labor_cost: '12.0000',
        overhead_cost: '3.5000',
        unit_cost: '35.7540',
    },
    margin: { amount: '53.2460', percent: '59.83' },
    capacity: { max_producible: 112, bottleneck: materialRef('button') },
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

/** Out of stock: the cupsole is at 0, so nothing can be produced. */
export const canvasLowSneaker: Product = {
    id: 3,
    sku: 'PRD-SNK-003',
    name: 'Canvas Low Sneaker',
    category: 'footwear',
    category_label: 'Footwear',
    status: 'active',
    status_label: 'Active',
    description: 'Vulcanised low-top sneaker in natural canvas.',
    photo_url: '/mock/photos/prd-snk-003.svg',
    labor_cost: '9.3000',
    overhead_cost: '2.0000',
    selling_price: '69.0000',
    cost: {
        material_cost: '10.4990',
        labor_cost: '9.3000',
        overhead_cost: '2.0000',
        unit_cost: '21.7990',
    },
    margin: { amount: '47.2010', percent: '68.41' },
    capacity: { max_producible: 0, bottleneck: materialRef('cupsole') },
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
    status: 'draft',
    status_label: 'Draft',
    description: null,
    photo_url: null,
    labor_cost: '14.5000',
    overhead_cost: '3.0400',
    selling_price: null,
    cost: {
        material_cost: '14.8900',
        labor_cost: '14.5000',
        overhead_cost: '3.0400',
        unit_cost: '32.4300',
    },
    margin: null,
    capacity: { max_producible: 129, bottleneck: materialRef('canvas') },
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
        material_cost: '0.0000',
        labor_cost: '14.5000',
        overhead_cost: '3.0400',
        unit_cost: '17.5400',
    },
    capacity: { max_producible: null, bottleneck: null },
    components: [],
};

/** Sold below cost: 30.0000 against a unit cost of 35.7540. */
export const denimJacketBelowCost: Product = {
    ...denimJacket,
    selling_price: '30.0000',
    margin: { amount: '-5.7540', percent: '-19.18' },
};

/** A zero selling price has an amount but no percentage. */
export const denimJacketGiveaway: Product = {
    ...denimJacket,
    selling_price: '0.0000',
    margin: { amount: '-35.7540', percent: null },
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
    unit_cost: '35.7540',
    selling_price: '89.0000',
    margin: { amount: '53.2460', percent: '59.83' },
    capacity: { max_producible: 112, bottleneck: materialRef('button') },
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
    unit_cost: '9.7484',
    selling_price: '24.0000',
    margin: { amount: '14.2516', percent: '59.38' },
    capacity: { max_producible: 50, bottleneck: materialRef('jersey') },
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
    unit_cost: '21.7990',
    selling_price: '69.0000',
    margin: { amount: '47.2010', percent: '68.41' },
    capacity: { max_producible: 0, bottleneck: materialRef('cupsole') },
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
    unit_cost: '101.0300',
    selling_price: '249.0000',
    margin: { amount: '147.9700', percent: '59.43' },
    capacity: { max_producible: 53, bottleneck: materialRef('nappa') },
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
    unit_cost: '29.4780',
    selling_price: '79.0000',
    margin: { amount: '49.5220', percent: '62.69' },
    capacity: { max_producible: 105, bottleneck: materialRef('thread') },
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
    unit_cost: '32.4300',
    selling_price: null,
    margin: null,
    capacity: { max_producible: 129, bottleneck: materialRef('canvas') },
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
    unit_cost: '17.0440',
    selling_price: '45.0000',
    margin: { amount: '27.9560', percent: '62.12' },
    capacity: { max_producible: 391, bottleneck: materialRef('nappa') },
    units_sold_last_30_days: 31,
};

/** A product with no composition at all (capacity null, no bottleneck). */
export const unfinishedItem: ProductListItem = {
    ...choreJacketItem,
    id: 8,
    sku: 'PRD-NEW-008',
    name: 'Unfinished Parka',
    components_count: 0,
    unit_cost: '17.5400',
    capacity: { max_producible: null, bottleneck: null },
};

/** Sold below cost: a negative margin. */
export const belowCostItem: ProductListItem = {
    ...denimJacketItem,
    id: 9,
    sku: 'PRD-JKT-009',
    name: 'Clearance Jacket',
    selling_price: '30.0000',
    margin: { amount: '-5.7540', percent: '-19.18' },
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
