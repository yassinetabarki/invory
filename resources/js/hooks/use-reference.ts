import { usePage } from '@inertiajs/react';
import type { ReferenceData } from '@/types/domain';

const NO_REFERENCE: ReferenceData = {
    units: [],
    material_categories: [],
    product_categories: [],
    product_statuses: [],
    sales_periods: [],
    stock_statuses: [],
};

/**
 * The shared `reference` once-prop (units and categories, archived rows
 * included). It is only sent to signed-in users, so guests get empty lists.
 */
export function useReference(): ReferenceData {
    return usePage().props.reference ?? NO_REFERENCE;
}
