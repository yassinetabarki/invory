import { useAppSettings } from '@/hooks/use-app-settings';
import type { ScanToday } from '@/types/domain';

/** "09:05": the time of a scan, in the reader's clock. */
function time(iso: string, locale: string): string {
    return new Intl.DateTimeFormat(locale, {
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(iso));
}

/** "Your scans today" (INV-15, AC-3): this member's scans since midnight, newest first. */
export function ScansToday({ scans }: { scans: ScanToday[] }) {
    const { locale } = useAppSettings();

    return (
        <section aria-labelledby="scans-today" className="flex flex-col gap-2">
            <h2 id="scans-today" className="font-semibold">
                Your scans today
            </h2>
            {scans.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    No scan yet today.
                </p>
            ) : (
                <ul className="flex flex-col divide-y rounded-xl border">
                    {scans.map((scan, index) => (
                        <li
                            key={`${scan.number}-${scan.at}-${index}`}
                            className="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1 p-3 text-sm"
                        >
                            <span className="min-w-0">
                                <span className="font-mono">{scan.number}</span>{' '}
                                · {scan.product}{' '}
                                <span className="text-muted-foreground">
                                    → {scan.to}
                                </span>
                            </span>
                            <time
                                dateTime={scan.at}
                                className="text-muted-foreground tabular-nums"
                            >
                                {time(scan.at, locale)}
                            </time>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
