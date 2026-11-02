import type { ProgressBatch } from '@/types/domain';

/** "Indigo M", "Black, size 38", "Camel" (a "One size" product): how the messages name a batch's variant. */
export function variantName(batch: ProgressBatch): string {
    if (batch.one_size) {
        return batch.colour.name;
    }

    return /^\d+$/.test(batch.size.label)
        ? `${batch.colour.name}, size ${batch.size.label}`
        : `${batch.colour.name} ${batch.size.label}`;
}

/** "today", "1 day", "4 days": the server counts the days, the page only words them. */
export function daysLabel(days: number): string {
    if (days === 0) {
        return 'today';
    }

    return days === 1 ? '1 day' : `${days} days`;
}
