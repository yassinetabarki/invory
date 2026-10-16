/**
 * Typed fixtures shared by page tests. Decimals are strings, exactly as the
 * API Resources send them.
 */
import type {
    Material,
    MaterialFilters,
    MaterialRef,
    MaterialUsage,
    Paginated,
    StockMovement,
} from '@/types/domain';

export const denim: Material = {
    id: 1,
    sku: 'FAB-001',
    name: 'Denim 12oz Indigo',
    category: 'fabric',
    category_label: 'Fabric',
    unit: 'm',
    unit_label: 'Metre (m)',
    unit_cost: '8.5000',
    quantity_on_hand: '250.0000',
    reorder_level: '50.0000',
    stock_value: '2125.0000',
    stock_status: 'in_stock',
    supplier_name: 'Tessuti Rossi',
    supplier_reference: 'TR-4471',
    color: 'Indigo',
    composition: '100% cotton',
    notes: 'First line\nSecond line',
    photo_url: null,
    used_in_count: 0,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
};

export const thread: Material = {
    ...denim,
    id: 2,
    sku: 'THR-001',
    name: 'Polyester thread',
    category: 'thread',
    category_label: 'Thread',
    unit_cost: '0.0040',
    quantity_on_hand: '0.0000',
    reorder_level: null,
    stock_value: '0.0000',
    stock_status: 'out',
    supplier_name: null,
    supplier_reference: null,
    color: null,
    composition: null,
    notes: null,
};

/**
 * The preview fixture's metal shank button (docs/plans/phase-2-ui-first-preview.md):
 * 900 pc, reorder at 1,000 pc, so Low stock, used in 3 products. Its usage and
 * history below are the plan's values.
 */
export const button: Material = {
    id: 3,
    sku: 'MAT-BTN-017',
    name: 'Metal shank button 17mm',
    category: 'fastener',
    category_label: 'Fastener',
    unit: 'pc',
    unit_label: 'Piece (pc)',
    unit_cost: '0.3500',
    quantity_on_hand: '900.0000',
    reorder_level: '1000.0000',
    stock_value: '315.0000',
    stock_status: 'low',
    supplier_name: 'Northgate Trims',
    supplier_reference: 'NT-17-SHK-AB',
    color: 'Antique brass',
    composition: 'Zinc alloy',
    notes: 'Hammer-on shank. Needs the tack-button press.',
    photo_url: '/mock/photos/mat-btn-017.svg',
    used_in_count: 3,
    created_at: '2026-09-01T00:00:00Z',
    updated_at: '2026-10-02T09:00:00Z',
};

const buttonRef: MaterialRef = {
    id: 3,
    sku: 'MAT-BTN-017',
    name: 'Metal shank button 17mm',
    unit: 'pc',
};

const denimRef: MaterialRef = {
    id: 1,
    sku: 'MAT-DEN-012',
    name: 'Denim 12oz Indigo',
    unit: 'm',
};

const canvasRef: MaterialRef = {
    id: 4,
    sku: 'MAT-CAN-010',
    name: 'Cotton canvas 10oz Natural',
    unit: 'm',
};

const threadRef: MaterialRef = {
    id: 5,
    sku: 'MAT-THR-PES',
    name: 'Polyester thread',
    unit: 'm',
};

/** Button usage, bottleneck rows first, then by product name. */
export const buttonUsage: MaterialUsage[] = [
    {
        product: {
            id: 1,
            sku: 'PRD-JKT-001',
            name: 'Denim Jacket',
            photo_url: '/mock/photos/prd-jkt-001.svg',
            status: 'active',
            status_label: 'Active',
        },
        effective_quantity: '8.0000',
        units_covered: 112,
        max_producible: 112,
        bottleneck: buttonRef,
        is_bottleneck: true,
        next_limit: { material: denimRef, max_producible: 126 },
    },
    {
        product: {
            id: 6,
            sku: 'PRD-JKT-006',
            name: 'Canvas Chore Jacket',
            photo_url: null,
            status: 'draft',
            status_label: 'Draft',
        },
        effective_quantity: '5.0000',
        units_covered: 180,
        max_producible: 129,
        bottleneck: canvasRef,
        is_bottleneck: false,
        next_limit: null,
    },
    {
        product: {
            id: 5,
            sku: 'PRD-JNS-005',
            name: 'Denim Jeans 5-pocket',
            photo_url: '/mock/photos/prd-jns-005.svg',
            status: 'active',
            status_label: 'Active',
        },
        effective_quantity: '1.0000',
        units_covered: 900,
        max_producible: 105,
        bottleneck: threadRef,
        is_bottleneck: false,
        next_limit: null,
    },
];

/** One stock movement of the button; the defaults are a bare entry to override. */
function movement(
    entry: Pick<StockMovement, 'id' | 'type' | 'type_label' | 'occurred_on'> &
        Partial<StockMovement>,
): StockMovement {
    return {
        material_id: 3,
        quantity: null,
        quantity_before: null,
        quantity_after: null,
        unit_price: null,
        unit_cost_before: null,
        unit_cost_after: null,
        reference: null,
        note: null,
        units: null,
        order: null,
        product_name: null,
        user_name: 'Admin',
        ...entry,
    };
}

/** The button's four history entries, newest first (the server's order). */
export const buttonHistory: StockMovement[] = [
    movement({
        id: 4,
        type: 'price_change',
        type_label: 'Price change',
        occurred_on: '2026-10-02',
        unit_cost_before: '0.3200',
        unit_cost_after: '0.3500',
    }),
    movement({
        id: 3,
        type: 'restock',
        type_label: 'Restock',
        occurred_on: '2026-10-02',
        quantity: '500.0000',
        quantity_after: '900.0000',
        unit_price: '0.3500',
        reference: 'PO-2291',
    }),
    movement({
        id: 2,
        type: 'correction',
        type_label: 'Correction',
        occurred_on: '2026-09-14',
        quantity_before: '600.0000',
        quantity_after: '400.0000',
        note: 'Stock count',
    }),
    movement({
        id: 1,
        type: 'created',
        type_label: 'Created',
        occurred_on: '2026-09-01',
        quantity: '600.0000',
        quantity_after: '600.0000',
        unit_price: '0.3200',
    }),
];

export const defaultFilters: MaterialFilters = {
    search: null,
    category: null,
    low_stock: false,
    sort: 'name',
};

/** One page of results; pass `meta` overrides for multi-page cases. */
export function paginate<T>(
    data: T[],
    meta: Partial<Paginated<T>['meta']> = {},
): Paginated<T> {
    return {
        data,
        links: { first: null, last: null, prev: null, next: null },
        meta: {
            current_page: 1,
            from: data.length > 0 ? 1 : null,
            last_page: 1,
            links: [],
            path: '/materials',
            per_page: 25,
            to: data.length > 0 ? data.length : null,
            total: data.length,
            ...meta,
        },
    };
}
