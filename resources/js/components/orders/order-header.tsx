import { Link } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { telHref } from '@/components/clients/client-header';
import { SourceLabel } from '@/components/clients/source-label';
import { formatUnits } from '@/components/orders/stage-figure';
import { PageHeader } from '@/components/shared/page-header';
import { Button } from '@/components/ui/button';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatDate } from '@/lib/format';
import { show as showClient } from '@/routes/clients';
import { edit } from '@/routes/orders';
import type { Order } from '@/types/domain';

type OrderHeaderProps = {
    order: Order;
    /** The page opens the confirm dialog; this component never deletes. */
    onDelete: () => void;
};

/** The order's title, summary, client line, note and its Edit / Delete buttons. The status lives in the Order tracker (INV-14). */
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

            {order.client_details && (
                // INV-4, AC-7: who the client is; on a phone the line wraps.
                <p className="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-muted-foreground">
                    {order.client_details.source && (
                        <>
                            <SourceLabel client={order.client_details} />
                            <span aria-hidden="true">·</span>
                        </>
                    )}
                    <span>{order.client_details.city}</span>
                    <span aria-hidden="true">·</span>
                    <a
                        href={telHref(order.client_details.phone)}
                        aria-label={`Call ${order.client_details.name}, ${order.client_details.phone}`}
                        className="font-mono hover:underline focus-visible:underline"
                    >
                        {order.client_details.phone}
                    </a>
                    <span aria-hidden="true" className="max-sm:hidden">
                        ·
                    </span>
                    <Link
                        href={showClient(order.client_details.id)}
                        className="font-semibold text-foreground hover:underline focus-visible:underline max-sm:basis-full"
                    >
                        All orders of {order.client_details.name}
                    </Link>
                </p>
            )}

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
