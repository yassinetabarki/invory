/**
 * INV-5's Progress example, on 30 Oct 2026: the phases Sewing and Finishing
 * and the card's seven batches (26 units: In cutting 7 · Sewing 15 ·
 * Finishing 4). The figures are the server's.
 */
import { paginate } from '@/test/fixtures';
import { colourNamed } from '@/test/fixtures/colours';
import { sizeListSToXl } from '@/test/fixtures/products';
import { jacketRef, jeansRef } from '@/test/fixtures/restocks';
import type {
    ProgressBatch,
    ProgressIndexProps,
    ProgressPhaseView,
    ProgressProductCard,
    ProgressRow,
    ProgressStep,
    RestockProductRef,
    Size,
} from '@/types/domain';

const [S, M, L, XL] = sizeListSToXl.sizes as [Size, Size, Size, Size];

export const cutting: ProgressStep = { phase: 'cutting', name: 'In cutting' };
export const sewing: ProgressStep = { phase: 11, name: 'Sewing' };
export const finishing: ProgressStep = { phase: 12, name: 'Finishing' };
export const progressPath: ProgressStep[] = [cutting, sewing, finishing];

function row(
    colour: string,
    size: Size | null,
    byPhase: number[],
    shelf: number | null,
): ProgressRow {
    return {
        colour: colourNamed(colour),
        size,
        by_phase: byPhase,
        in_production: byPhase.reduce((sum, units) => sum + units, 0),
        on_shelf: shelf,
    };
}

export const jacketCard: ProgressProductCard = {
    product: jacketRef,
    units: 21,
    batches: 6,
    one_size: false,
    rows: [
        row('Indigo', null, [4, 10, 4], null),
        row('Indigo', S, [0, 4, 0], 2),
        row('Indigo', M, [4, 6, 4], 0),
        row('Light wash', null, [3, 0, 0], null),
        row('Light wash', L, [2, 0, 0], 0),
        row('Light wash', XL, [1, 0, 0], 0),
    ],
    total: { by_phase: [7, 10, 4], in_production: 21 },
};

export const jeansCard: ProgressProductCard = {
    product: jeansRef,
    units: 5,
    batches: 1,
    one_size: false,
    rows: [
        row('Indigo', null, [0, 5, 0], null),
        row('Indigo', S, [0, 5, 0], 0),
    ],
    total: { by_phase: [0, 5, 0], in_production: 5 },
};

export function progressBatch(
    number: string,
    product: RestockProductRef,
    colour: string,
    size: Size,
    units: number,
    since: string,
    days: number,
    shelf = 0,
): ProgressBatch {
    return {
        id: Number(number.slice(2)),
        number,
        product,
        colour: colourNamed(colour),
        size,
        one_size: false,
        units,
        since,
        since_days: days,
        on_shelf: shelf,
    };
}

export const b0146 = progressBatch(
    'B-0146',
    jeansRef,
    'Indigo',
    S,
    5,
    '2026-10-26',
    4,
);
export const b0147 = progressBatch(
    'B-0147',
    jacketRef,
    'Indigo',
    S,
    4,
    '2026-10-27',
    3,
    2,
);
export const b0148 = progressBatch(
    'B-0148',
    jacketRef,
    'Indigo',
    M,
    4,
    '2026-10-29',
    1,
);
export const b0149 = progressBatch(
    'B-0149',
    jacketRef,
    'Indigo',
    M,
    6,
    '2026-10-28',
    2,
);
export const b0150 = progressBatch(
    'B-0150',
    jacketRef,
    'Indigo',
    M,
    4,
    '2026-10-25',
    5,
);

export const sewingView: ProgressPhaseView = {
    phase: sewing,
    previous: cutting,
    next: finishing,
    units: 15,
    batch_count: 3,
    product_count: 2,
    oldest_since: '2026-10-26',
    batches: paginate([b0146, b0147, b0149]),
};

export const finishingView: ProgressPhaseView = {
    phase: finishing,
    previous: sewing,
    next: null,
    units: 4,
    batch_count: 1,
    product_count: 1,
    oldest_since: '2026-10-29',
    batches: paginate([b0148]),
};

export const cuttingView: ProgressPhaseView = {
    phase: cutting,
    previous: null,
    next: sewing,
    units: 7,
    batch_count: 3,
    product_count: 1,
    oldest_since: '2026-10-25',
    batches: paginate([b0150]),
};

export const progressProps: ProgressIndexProps = {
    path: progressPath,
    tiles: [
        { ...cutting, units: 7 },
        { ...sewing, units: 15 },
        { ...finishing, units: 4 },
    ],
    all_units: 26,
    total_units: 26,
    filters: { phase: null, product: null, colour: null },
    product_filter: null,
    colours: [
        colourNamed('Black'),
        colourNamed('Indigo'),
        colourNamed('Light wash'),
    ],
    has_batches: true,
    products: paginate([jacketCard, jeansCard]),
    phase_view: null,
};
