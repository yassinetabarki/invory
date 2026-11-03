import { formatCount } from '@/lib/format';

/** "1 unit" / "50 units": the count is the server's, only formatted here. */
export function formatUnits(count: number, locale: string): string {
    return `${formatCount(count, locale)} ${count === 1 ? 'unit' : 'units'}`;
}
