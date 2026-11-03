import { router } from '@inertiajs/react';
import { Ban, CircleCheck, Package, Truck } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useId } from 'react';
import type { ReactNode } from 'react';
import { ProgressStatus } from '@/components/orders/progress-status';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { OrderTracker as OrderTrackerData } from '@/types/domain';

/** The hatched look of Cancelled: never colour alone, the word is beside it. */
export const HATCHED =
    'bg-[repeating-linear-gradient(135deg,var(--color-muted-foreground)_0_2px,transparent_2px_6px)] opacity-60';

/** The card's frame: heading at once, the body when the tracker lands (or its placeholder, or its error). */
function TrackerFrame({
    children,
    heading,
}: {
    children: ReactNode;
    heading?: ReactNode;
}) {
    const id = useId();

    return (
        <section
            aria-labelledby={id}
            className="flex flex-col gap-4 rounded-xl border bg-card p-5 text-card-foreground"
        >
            <div className="flex flex-wrap items-center gap-3">
                <h2 id={id} className="text-base font-bold">
                    Order tracker
                </h2>
                {heading}
            </div>
            {children}
        </section>
    );
}

/** "5 of 80 units delivered · 5 cancelled". */
export function deliveredLine(
    tracker: { delivered: number; cancelled: number; total: number },
    locale: string,
): string {
    const base = `${formatCount(tracker.delivered, locale)} of ${formatCount(tracker.total, locale)} units delivered`;

    return tracker.cancelled > 0
        ? `${base} · ${formatCount(tracker.cancelled, locale)} cancelled`
        : base;
}

/** The thin bar of an order or a line: Delivered (green), To deliver (blue), Cancelled (hatched). Decoration: the tiles hold the numbers. */
export function DeliveryBar({
    counts,
    className,
}: {
    counts: {
        to_deliver: number;
        delivered: number;
        cancelled: number;
        total: number;
    };
    className?: string;
}) {
    const total = Math.max(counts.total, 1);
    const part = (units: number) => ({ width: `${(units / total) * 100}%` });

    return (
        <div
            aria-hidden="true"
            data-slot="delivery-bar"
            className={cn(
                'flex h-2 overflow-hidden rounded-full bg-muted',
                className,
            )}
        >
            <span
                className="bg-success-foreground/70"
                style={part(counts.delivered)}
            />
            <span
                className="bg-info-foreground/60"
                style={part(counts.to_deliver)}
            />
            <span className={HATCHED} style={part(counts.cancelled)} />
        </div>
    );
}

type Tile = {
    key: string;
    label: string;
    icon: LucideIcon;
    units: number;
    tone: string;
    note?: string;
};

/**
 * The Order tracker (INV-14, AC-1): the status, "5 of 80 units delivered · 5 cancelled", the bar, the four tiles "Units of this order"
 * and the grey line. On a phone, Total first and full width, then the three counts two to a row.
 */
export function OrderTracker({ tracker }: { tracker: OrderTrackerData }) {
    const { locale } = useAppSettings();
    const tiles: Tile[] = [
        {
            key: 'to_deliver',
            label: 'To deliver',
            icon: Truck,
            units: tracker.to_deliver,
            tone: 'border-info-foreground/30 bg-info/40',
        },
        {
            key: 'delivered',
            label: 'Delivered',
            icon: CircleCheck,
            units: tracker.delivered,
            tone: 'border-success-foreground/30 bg-success/40',
        },
        {
            key: 'cancelled',
            label: 'Cancelled',
            icon: Ban,
            units: tracker.cancelled,
            tone: 'border-dashed',
        },
        {
            key: 'total',
            label: 'Total',
            icon: Package,
            units: tracker.total,
            tone: 'bg-muted/50 max-lg:order-first max-lg:col-span-2',
            note: 'units ordered',
        },
    ];

    return (
        <TrackerFrame
            heading={
                <>
                    <ProgressStatus
                        status={tracker.status}
                        label={tracker.status_label}
                    />
                    <p className="text-sm font-medium tabular-nums">
                        {deliveredLine(tracker, locale)}
                    </p>
                </>
            }
        >
            <DeliveryBar counts={tracker} />
            <ul
                aria-label="Units of this order"
                className="grid grid-cols-2 gap-3 lg:grid-cols-4"
            >
                {tiles.map(({ key, label, icon: Icon, units, tone, note }) => (
                    <li
                        key={key}
                        aria-label={`${label}, ${formatCount(units, locale)} ${units === 1 ? 'unit' : 'units'}`}
                        className={cn(
                            'flex min-w-0 flex-col gap-1 rounded-lg border px-4 py-3',
                            tone,
                        )}
                    >
                        <span
                            aria-hidden="true"
                            className="flex items-center gap-2 text-sm font-medium text-muted-foreground"
                        >
                            <Icon className="size-4" />
                            {label}
                        </span>
                        <span
                            aria-hidden="true"
                            className="font-mono text-2xl font-bold [overflow-wrap:anywhere] tabular-nums"
                        >
                            {formatCount(units, locale)}{' '}
                            <span className="font-sans text-sm font-normal text-muted-foreground">
                                {note ?? (units === 1 ? 'unit' : 'units')}
                            </span>
                        </span>
                    </li>
                ))}
            </ul>
            <p className="text-sm text-muted-foreground">
                To deliver: ordered, not delivered yet. On the shelf now is your
                finished stock; it isn't set aside for this order. Delivered and
                Cancelled units stay in the total.
            </p>
        </TrackerFrame>
    );
}

/** The card while the tracker loads: the frame at once, a bar and four tile placeholders. */
export function OrderTrackerLoading() {
    return (
        <TrackerFrame>
            <span role="status" className="sr-only">
                Loading the tracker…
            </span>
            <Skeleton className="h-2 w-full" />
            <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                {[0, 1, 2, 3].map((tile) => (
                    <Skeleton key={tile} className="h-20" />
                ))}
            </div>
        </TrackerFrame>
    );
}

/** The card when the tracker couldn't be read: the sentence and Try again (the focus stays on the button while it reloads). */
export function OrderTrackerError({ reloading }: { reloading: boolean }) {
    return (
        <TrackerFrame>
            <div
                role="alert"
                className="flex flex-col items-start gap-2 text-sm"
            >
                <p>Couldn't load the tracker of this order.</p>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className="aria-disabled:opacity-50"
                    aria-disabled={reloading ? 'true' : undefined}
                    onClick={() => {
                        if (!reloading) {
                            router.reload({ only: ['tracker'] });
                        }
                    }}
                >
                    Try again
                </Button>
            </div>
        </TrackerFrame>
    );
}
