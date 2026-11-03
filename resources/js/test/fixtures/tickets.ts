import type { ScanConfirm, WorkTicket } from '@/types/domain';

/** The card's B-0147: 4 Denim Jacket Indigo S in Sewing, started 23 Oct 2026, printed 30 Oct 2026 (INV-15, AC-1). */
export const ticket0147: WorkTicket = {
    id: 147,
    number: 'B-0147',
    product: 'Denim Jacket',
    variant: 'Indigo · S',
    units: 4,
    started_on: '2026-10-23',
    sku: 'PRD-JKT-001-IND-S',
    split_from: null,
    path: ['In cutting', 'Sewing', 'Finishing', 'Put in stock'],
    current: 1,
    printed_on: '2026-10-30',
    qr: { size: 29, path: 'M4 4h7v1h-7zM4 5h1v1h-1z' },
};

/** B-0155: 4 units split from B-0149 into Finishing. */
export const ticket0155: WorkTicket = {
    ...ticket0147,
    id: 155,
    number: 'B-0155',
    variant: 'Indigo · M',
    sku: 'PRD-JKT-001-IND-M',
    split_from: 'B-0149',
    current: 2,
};

/** The scan confirm of B-0147 in Sewing: Move 4 units to Finishing (INV-15, AC-4). */
export const confirm0147: ScanConfirm = {
    batch_id: 147,
    number: 'B-0147',
    units: 4,
    product: 'Denim Jacket',
    variant: 'Indigo · S',
    started_on: '2026-10-23',
    path: [
        { name: 'In cutting', state: 'done' },
        { name: 'Sewing', state: 'now' },
        { name: 'Finishing', state: 'next' },
        { name: 'Put in stock', state: 'later' },
    ],
    from: 11,
    to: 12,
    to_name: 'Finishing',
    shelf: null,
    warning: null,
};

/** The scan confirm of B-0148 in Finishing, the last phase: Put 4 units in stock, Indigo M from 0 to 4. */
export const confirm0148: ScanConfirm = {
    ...confirm0147,
    batch_id: 148,
    number: 'B-0148',
    variant: 'Indigo · M',
    path: [
        { name: 'In cutting', state: 'done' },
        { name: 'Sewing', state: 'done' },
        { name: 'Finishing', state: 'now' },
        { name: 'Put in stock', state: 'next' },
    ],
    from: 12,
    to: 'stock',
    to_name: 'Put in stock',
    shelf: { now: 0, after: 4 },
};
