/**
 * INV-3's examples: the card's three restocks, the Denim Jacket's detail and
 * the lines New restock receives.
 */
import { colourNamed } from '@/test/fixtures/colours';
import {
    denimStockByColour,
    sizeListOneSize,
    sizeListSToXl,
    stockByColour,
    toteStockByColour,
} from '@/test/fixtures/products';
import type {
    RestockDetail,
    RestockLine,
    RestockProductRef,
    RestockSummary,
} from '@/types/domain';

export const jacketRef: RestockProductRef = {
    id: 1,
    name: 'Denim Jacket',
    sku: 'PRD-JKT-001',
    status: 'active',
    deleted: false,
};
export const jeansRef: RestockProductRef = {
    id: 5,
    name: 'Denim Jeans 5-pocket',
    sku: 'PRD-JNS-005',
    status: 'draft',
    deleted: false,
};
export const toteRef: RestockProductRef = {
    id: 4,
    name: 'Leather Tote',
    sku: 'PRD-TOT-004',
    status: 'archived',
    deleted: false,
};

/** 25 Oct 2026, BL-0412, by Admin: Denim Jacket 24 · Denim Jeans 5-pocket 10. */
export const bl0412: RestockSummary = {
    id: 3,
    restocked_on: '2026-10-25',
    reference: 'BL-0412',
    units: 34,
    products: [
        { name: 'Denim Jacket', units: 24 },
        { name: 'Denim Jeans 5-pocket', units: 10 },
    ],
    more_products: 0,
    recorded_by: 'Admin',
    undone: false,
};
/** 22 Oct 2026, no reference, by Sami: Leather Tote 6. */
export const tote6: RestockSummary = {
    id: 2,
    restocked_on: '2026-10-22',
    reference: null,
    units: 6,
    products: [{ name: 'Leather Tote', units: 6 }],
    more_products: 0,
    recorded_by: 'Sami',
    undone: false,
};
/** 18 Oct 2026, "Return Boutique Lina", by Admin, undone: Classic White T-shirt 12. */
export const returned: RestockSummary = {
    id: 1,
    restocked_on: '2026-10-18',
    reference: 'Return Boutique Lina',
    units: 12,
    products: [{ name: 'Classic White T-shirt', units: 12 }],
    more_products: 0,
    recorded_by: 'Admin',
    undone: true,
};

/** The panel of BL-0412: Denim Jacket (Black M 4; Indigo 4 · 8 · 6 · 2) and Denim Jeans 5-pocket (Indigo M 6, L 4). */
export const bl0412Detail: RestockDetail = {
    ...bl0412,
    note: 'Batch sewn by Atelier Nour, delivered with the invoice.',
    recorded_at: '2026-10-25T14:32',
    products_count: 2,
    undone_at: null,
    undone_by: null,
    undo_refusal: null,
    blocks: [
        {
            product: jacketRef,
            sizes: sizeListSToXl.sizes,
            one_size: false,
            units: 24,
            rows: [
                { colour: colourNamed('Black'), units: [0, 4, 0, 0], total: 4 },
                {
                    colour: colourNamed('Indigo'),
                    units: [4, 8, 6, 2],
                    total: 20,
                },
            ],
            totals: [4, 12, 6, 2],
        },
        {
            product: jeansRef,
            sizes: sizeListSToXl.sizes,
            one_size: false,
            units: 10,
            rows: [
                {
                    colour: colourNamed('Indigo'),
                    units: [0, 6, 4, 0],
                    total: 10,
                },
            ],
            totals: null,
        },
    ],
};

/** New restock's line of the Denim Jacket: Black, Indigo and Light wash, 10 on the shelf. */
export const jacketLine: RestockLine = {
    product: jacketRef,
    sizes: sizeListSToXl.sizes,
    one_size: false,
    stock: denimStockByColour,
};
/** The Denim Jeans 5-pocket in Indigo, nothing on the shelf. */
export const jeansLine: RestockLine = {
    product: jeansRef,
    sizes: sizeListSToXl.sizes,
    one_size: false,
    stock: stockByColour(sizeListSToXl, [['Indigo', [0, 0, 0, 0]]]),
};
/** The Leather Tote ("One size"): Camel 4, Black 0. */
export const toteLine: RestockLine = {
    product: toteRef,
    sizes: sizeListOneSize.sizes,
    one_size: true,
    stock: toteStockByColour,
};
