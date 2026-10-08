/**
 * Dashboard fixtures from the UI-first plan ("Preview fixture", last 30 days,
 * time frozen at 2026-10-08). Every figure is what the server would send;
 * nothing here is derived, so tests prove the UI only formats.
 */
import { denim } from '@/test/fixtures';
import { productRefs, recentSales, salesPeriods } from '@/test/fixtures/sales';
import type {
    DashboardProps,
    DashboardStats,
    Material,
    TopProduct,
    UnproducibleAlert,
} from '@/types/domain';

export const dashboardStats: DashboardStats = {
    revenue: '16850.0000',
    units_sold: 296,
    sales_count: 48,
    products_sold_count: 6,
    inventory_value: '10544.3000',
    materials_count: 12,
    needs_restock_count: 4,
    low_stock_count: 3,
    out_of_stock_count: 1,
    unproducible_count: 1,
    products_count: 7,
};

/** Top 5 by units sold, last 30 days. */
export const topByUnits: TopProduct[] = [
    {
        product: productRefs.tshirt,
        units_sold: 142,
        revenue: '3408.0000',
        percent_of_top: '100.00',
    },
    {
        product: productRefs.jeans,
        units_sold: 64,
        revenue: '5056.0000',
        percent_of_top: '45.07',
    },
    {
        product: productRefs.jacket,
        units_sold: 38,
        revenue: '3382.0000',
        percent_of_top: '26.76',
    },
    {
        product: productRefs.belt,
        units_sold: 31,
        revenue: '1395.0000',
        percent_of_top: '21.83',
    },
    {
        product: productRefs.tote,
        units_sold: 12,
        revenue: '2988.0000',
        percent_of_top: '8.45',
    },
];

/** Top 5 by revenue, last 30 days (same products, other order and bars). */
export const topByRevenue: TopProduct[] = [
    {
        product: productRefs.jeans,
        units_sold: 64,
        revenue: '5056.0000',
        percent_of_top: '100.00',
    },
    {
        product: productRefs.tshirt,
        units_sold: 142,
        revenue: '3408.0000',
        percent_of_top: '67.41',
    },
    {
        product: productRefs.jacket,
        units_sold: 38,
        revenue: '3382.0000',
        percent_of_top: '66.89',
    },
    {
        product: productRefs.tote,
        units_sold: 12,
        revenue: '2988.0000',
        percent_of_top: '59.10',
    },
    {
        product: productRefs.belt,
        units_sold: 31,
        revenue: '1395.0000',
        percent_of_top: '27.59',
    },
];

function material(overrides: Partial<Material>): Material {
    return {
        ...denim,
        photo_url: null,
        supplier_name: null,
        supplier_reference: null,
        color: null,
        composition: null,
        notes: null,
        ...overrides,
    };
}

/** Out first, then low; by name. */
export const needsRestock: Material[] = [
    material({
        id: 9,
        sku: 'MAT-SOL-042',
        name: 'Rubber cupsole 42',
        category: 'sole',
        category_label: 'Sole',
        unit: 'pair',
        unit_label: 'Pair (pair)',
        unit_cost: '5.9000',
        quantity_on_hand: '0.0000',
        reorder_level: '40.0000',
        stock_value: '0.0000',
        stock_status: 'out',
        used_in_count: 1,
    }),
    material({
        id: 3,
        sku: 'MAT-JRS-180',
        name: 'Cotton jersey 180gsm White',
        unit_cost: '4.2000',
        quantity_on_hand: '64.0000',
        reorder_level: '80.0000',
        stock_value: '268.8000',
        stock_status: 'low',
        used_in_count: 1,
    }),
    material({
        id: 5,
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
        used_in_count: 3,
    }),
    material({
        id: 12,
        sku: 'MAT-LBL-WOV',
        name: 'Woven brand label',
        category: 'label',
        category_label: 'Label',
        unit: 'pc',
        unit_label: 'Piece (pc)',
        unit_cost: '0.1200',
        quantity_on_hand: '400.0000',
        reorder_level: '500.0000',
        stock_value: '48.0000',
        stock_status: 'low',
        used_in_count: 6,
    }),
];

/** Canvas Low Sneaker: the cupsole is out of stock. */
export const sneakerAlert: UnproducibleAlert = {
    product: productRefs.sneaker,
    bottleneck: {
        id: 9,
        sku: 'MAT-SOL-042',
        name: 'Rubber cupsole 42',
        unit: 'pair',
        stock_status: 'out',
    },
};

/** A product whose bottleneck is in stock but below one unit's need. */
export const choreAlert: UnproducibleAlert = {
    product: productRefs.chore,
    bottleneck: {
        id: 5,
        sku: 'MAT-BTN-017',
        name: 'Metal shank button 17mm',
        unit: 'pc',
        stock_status: 'low',
    },
};

/** `DashboardProps` with the plan's default values; override what a test needs. */
export function dashboardProps(
    overrides: Partial<DashboardProps> = {},
): DashboardProps {
    return {
        period: 'last_30_days',
        periods: salesPeriods,
        stats: dashboardStats,
        top_products: { by_units: topByUnits, by_revenue: topByRevenue },
        needs_restock: needsRestock,
        recent_sales: recentSales,
        alerts: [sneakerAlert],
        ...overrides,
    };
}
