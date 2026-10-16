import { Link } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { formatUnits } from '@/components/orders/stage-figure';
import { ProgressStatus } from '@/components/orders/progress-status';
import { PageHeader } from '@/components/shared/page-header';
import { Button } from '@/components/ui/button';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount, formatDate } from '@/lib/format';
import { edit } from '@/routes/orders';
import type { Order } from '@/types/domain';

type OrderHeaderProps = {
    order: Order;
    /** The page opens the confirm dialog; this component never deletes. */
    onDelete: () => void;
};

/** The order's title, summary, status, progress, note and its Edit / Delete buttons. */
export function OrderHeader({ order, onDelete }: OrderHeaderProps) {
    const { locale } = useAppSettings();
    const products =
        order.lines.length === 1
            ? '1 product'
            : `${order.lines.length} products`;

    return (
        <div className="flex flex-col gap-4">
            <PageHeader
                title={order.client}
                description={`Ordered ${formatDate(order.ordered_on, locale)} · ${products} · ${formatUnits(order.units, locale)}`}
                actions={
                    <>
                        <Button asChild variant="outline">
                            <Link href={edit(order.id)}>
                                <Pencil aria-hidden="true" />
                                Edit
                            </Link>
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            className="text-destructive-foreground hover:text-destructive-foreground"
                            onClick={onDelete}
                        >
                            <Trash2 aria-hidden="true" />
                            Delete
                        </Button>
                    </>
                }
            />

            <div className="flex flex-wrap items-center gap-3">
                <ProgressStatus
                    status={order.status}
                    label={order.status_label}
                />
                <p className="text-sm font-medium tabular-nums">
                    {formatCount(order.shipped, locale)} of{' '}
                    {formatCount(order.units, locale)} shipped
                </p>
            </div>

            {order.note && (
                <section
                    aria-labelledby="order-note"
                    className="rounded-xl border bg-card p-4 text-card-foreground"
                >
                    <h2 id="order-note" className="text-sm font-bold">
                        Note
                    </h2>
                    <p className="mt-1 text-sm break-words whitespace-pre-line text-muted-foreground">
                        {order.note}
                    </p>
                </section>
            )}
        </div>
    );
}
