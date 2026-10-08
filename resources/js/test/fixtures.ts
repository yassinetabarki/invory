/**
 * Typed fixtures shared by page tests. Decimals are strings, exactly as the
 * API Resources send them.
 */
import type {
    Material,
    MaterialCategory,
    MaterialFilters,
    Option,
    Paginated,
    UnitOfMeasure,
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

export const categories: Option<MaterialCategory>[] = [
    { value: 'fabric', label: 'Fabric' },
    { value: 'thread', label: 'Thread' },
    { value: 'trim', label: 'Trim' },
];

export const units: Option<UnitOfMeasure>[] = [
    { value: 'm', label: 'Metre (m)' },
    { value: 'pc', label: 'Piece (pc)' },
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
