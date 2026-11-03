import type { ScanConfirm } from '@/types/domain';

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
