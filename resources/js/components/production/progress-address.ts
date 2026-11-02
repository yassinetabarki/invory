import { index } from '@/routes/progress';
import type { PhaseRef, ProgressFilters } from '@/types/domain';

/** The Progress page's address: the phase shown (none = All phases) and the filters. */
export function progressQuery(
    filters: Pick<ProgressFilters, 'product' | 'colour'>,
    phase: PhaseRef | null,
): Record<string, string | number> {
    return {
        ...(phase === null ? {} : { phase }),
        ...(filters.product === null ? {} : { product: filters.product }),
        ...(filters.colour === null ? {} : { colour: filters.colour }),
    };
}

export function progressUrl(
    filters: Pick<ProgressFilters, 'product' | 'colour'>,
    phase: PhaseRef | null,
): string {
    return index.url({ query: progressQuery(filters, phase) });
}

/** "1 unit" / "15 units", the count already formatted. */
export function unitsLabel(count: number, formatted: string): string {
    return `${formatted} ${count === 1 ? 'unit' : 'units'}`;
}
