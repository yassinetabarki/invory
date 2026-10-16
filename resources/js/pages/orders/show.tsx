import { Head, router } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { DeleteOrderDialog } from '@/components/orders/delete-order-dialog';
import { MoveUnitsDialog } from '@/components/orders/move-units-dialog';
import type { MoveTarget } from '@/components/orders/move-units-dialog';
import { OrderHeader } from '@/components/orders/order-header';
import { OrderLineCard } from '@/components/orders/order-line-card';
import { destroy, index, show } from '@/routes/orders';
import type { OrderLine, OrderLineMove, OrderShowProps } from '@/types/domain';

export default function OrdersShow({ order }: OrderShowProps) {
    const [moving, setMoving] = useState<MoveTarget | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [deleteProcessing, setDeleteProcessing] = useState(false);
    // The control that opened a dialog, so focus can return to it.
    const triggerRef = useRef<HTMLElement | null>(null);

    function rememberTrigger() {
        triggerRef.current =
            document.activeElement instanceof HTMLElement &&
            document.activeElement !== document.body
                ? document.activeElement
                : null;
    }

    function askToMove(line: OrderLine, move: OrderLineMove) {
        rememberTrigger();
        setMoving({ lineId: line.id, move });
    }

    function askToDelete() {
        rememberTrigger();
        setDeleting(true);
    }

    function confirmDelete() {
        router.delete(destroy.url(order.id), {
            onStart: () => setDeleteProcessing(true),
            onFinish: () => {
                setDeleteProcessing(false);
                setDeleting(false);
            },
        });
    }

    return (
        <>
            <Head title={order.client} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <OrderHeader order={order} onDelete={askToDelete} />

                <section
                    aria-labelledby="order-products"
                    className="flex flex-col gap-3"
                >
                    <h2 id="order-products" className="text-lg font-semibold">
                        Products
                    </h2>
                    {order.lines.map((line) => (
                        <OrderLineCard
                            key={line.id}
                            line={line}
                            onMove={askToMove}
                        />
                    ))}
                </section>
            </div>

            <MoveUnitsDialog
                order={order}
                target={moving}
                returnFocusTo={triggerRef}
                onClose={() => setMoving(null)}
            />
            <DeleteOrderDialog
                order={deleting ? order : null}
                processing={deleteProcessing}
                returnFocusTo={triggerRef}
                onCancel={() => setDeleting(false)}
                onConfirm={confirmDelete}
            />
        </>
    );
}

OrdersShow.layout = (props: OrderShowProps) => ({
    breadcrumbs: [
        { title: 'Orders', href: index() },
        { title: props.order.client, href: show(props.order.id) },
    ],
});
