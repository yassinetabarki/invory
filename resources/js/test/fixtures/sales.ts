/**
 * Sales fixtures from the UI-first plan ("Preview fixture", time frozen at
 * 2026-10-08). Decimals are strings exactly as the API Resources send them;
 * totals are the server's (`quantity × unit_price`), never computed here.
 */
import { paginate } from '@/test/fixtures';
import type {
    Option,
    PaginationLink,
    ProductRef,
    Sale,
    SalesFilters,
    SalesIndexProps,
    SalesPeriod,
    SalesTotals,
    SaleProductOption,
} from '@/types/domain';

export const salesPeriods: Option<SalesPeriod>[] = [
    { value: 'last_7_days', label: 'Last 7 days' },
    { value: 'last_30_days', label: 'Last 30 days' },
    { value: 'this_month', label: 'This month' },
    { value: 'this_year', label: 'This year' },
];

const refs = {
    jacket: { id: 1, sku: 'PRD-JKT-001', name: 'Denim Jacket' },
    tshirt: { id: 2, sku: 'PRD-TSH-002', name: 'Classic White T-shirt' },
    sneaker: { id: 3, sku: 'PRD-SNK-003', name: 'Canvas Low Sneaker' },
    tote: { id: 4, sku: 'PRD-TOT-004', name: 'Leather Tote' },
    jeans: { id: 5, sku: 'PRD-JNS-005', name: 'Denim Jeans 5-pocket' },
    chore: { id: 6, sku: 'PRD-JKT-006', name: 'Canvas Chore Jacket' },
    belt: { id: 7, sku: 'PRD-BLT-007', name: 'Leather Belt' },
} as const;

function ref(product: (typeof refs)[keyof typeof refs]): ProductRef {
    return { ...product, photo_url: null };
}

export const productRefs = {
    jacket: ref(refs.jacket),
    tshirt: ref(refs.tshirt),
    sneaker: ref(refs.sneaker),
    tote: ref(refs.tote),
    jeans: ref(refs.jeans),
    chore: ref(refs.chore),
    belt: ref(refs.belt),
};

/** Every non-deleted product, by name; only the active ones can be sold. */
export const saleProducts: SaleProductOption[] = [
    {
        ...productRefs.chore,
        status: 'draft',
        selling_price: null,
    },
    {
        ...productRefs.sneaker,
        status: 'active',
        selling_price: '69.0000',
    },
    {
        ...productRefs.tshirt,
        status: 'active',
        selling_price: '24.0000',
    },
    {
        ...productRefs.jacket,
        status: 'active',
        selling_price: '89.0000',
    },
    {
        ...productRefs.jeans,
        status: 'active',
        selling_price: '79.0000',
    },
    {
        ...productRefs.belt,
        status: 'active',
        selling_price: '45.0000',
    },
    {
        ...productRefs.tote,
        status: 'active',
        selling_price: '249.0000',
    },
];

function sale(
    id: number,
    soldOn: string,
    product: ProductRef,
    quantity: number,
    unitPrice: string,
    total: string,
    note: string | null,
): Sale {
    return {
        id,
        product,
        quantity,
        unit_price: unitPrice,
        total,
        sold_on: soldOn,
        note,
    };
}

/** Sales 60, 59, 58, 57, 56: the newest five (also the dashboard's recent sales). */
export const recentSales: Sale[] = [
    sale(
        60,
        '2026-10-07',
        productRefs.jeans,
        6,
        '79.0000',
        '474.0000',
        'Boutique Lyon',
    ),
    sale(
        59,
        '2026-10-07',
        productRefs.tshirt,
        20,
        '24.0000',
        '480.0000',
        'Web shop',
    ),
    sale(
        58,
        '2026-10-06',
        productRefs.tote,
        2,
        '249.0000',
        '498.0000',
        'Showroom',
    ),
    sale(
        57,
        '2026-10-05',
        productRefs.jacket,
        4,
        '89.0000',
        '356.0000',
        'Web shop',
    ),
    sale(
        56,
        '2026-10-05',
        productRefs.belt,
        3,
        '45.0000',
        '135.0000',
        'Boutique Lyon',
    ),
];

/** The first page of the default list: sales 60 down to 51. */
export const salesPage: Sale[] = [
    ...recentSales,
    sale(
        55,
        '2026-10-04',
        productRefs.tshirt,
        12,
        '24.0000',
        '288.0000',
        'Wholesale order',
    ),
    sale(
        54,
        '2026-10-03',
        productRefs.sneaker,
        2,
        '69.0000',
        '138.0000',
        'Web shop',
    ),
    sale(
        53,
        '2026-10-02',
        productRefs.jeans,
        8,
        '79.0000',
        '632.0000',
        'Wholesale order',
    ),
    sale(
        52,
        '2026-10-01',
        productRefs.jacket,
        5,
        '89.0000',
        '445.0000',
        'Showroom',
    ),
    sale(
        51,
        '2026-09-30',
        productRefs.tshirt,
        15,
        '24.0000',
        '360.0000',
        'Web shop',
    ),
];

/** A sale without a note, for the empty-note rendering. */
export const saleWithoutNote: Sale = sale(
    33,
    '2026-09-21',
    productRefs.sneaker,
    1,
    '69.0000',
    '69.0000',
    null,
);

/** A sale of a product that is no longer active (draft or archived). */
export const saleOfInactiveProduct: Sale = sale(
    14,
    '2026-09-09',
    productRefs.chore,
    3,
    '95.0000',
    '285.0000',
    null,
);

export const defaultSalesFilters: SalesFilters = {
    search: null,
    product_id: null,
    period: 'last_30_days',
};

/** Last 30 days, all products. */
export const salesTotals: SalesTotals = {
    revenue: '16850.0000',
    units_sold: 296,
    sales_count: 48,
    average_sale: '351.0417',
};

export const noSalesTotals: SalesTotals = {
    revenue: '0.0000',
    units_sold: 0,
    sales_count: 0,
    average_sale: null,
};

/** Laravel's `meta.links` for a list of `lastPage` pages, on page 1. */
function firstPageLinks(lastPage: number): PaginationLink[] {
    return [
        { url: null, label: '&laquo; Previous', active: false },
        ...Array.from({ length: lastPage }, (_, index) => ({
            url: index === 0 ? null : `/sales?page=${index + 1}`,
            label: String(index + 1),
            active: index === 0,
        })),
        { url: '/sales?page=2', label: 'Next &raquo;', active: false },
    ];
}

/** `/sales` props with the plan's default values; override what a test needs. */
export function salesIndexProps(
    overrides: Partial<SalesIndexProps> = {},
): SalesIndexProps {
    return {
        sales: paginate(salesPage, {
            path: '/sales',
            from: 1,
            to: 10,
            per_page: 10,
            total: 48,
            last_page: 5,
            links: firstPageLinks(5),
        }),
        filters: defaultSalesFilters,
        totals: salesTotals,
        periods: salesPeriods,
        products: saleProducts,
        record: null,
        ...overrides,
    };
}
