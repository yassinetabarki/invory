import { Deferred, Head, router } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { DeleteOrderDialog } from '@/components/orders/delete-order-dialog';
import { OrderHeader } from '@/components/orders/order-header';
import { OrderProductCard } from '@/components/orders/order-product-card';
import type { UnitsMode } from '@/components/orders/order-product-card';
import {
    OrderTracker,
    OrderTrackerError,
    OrderTrackerLoading,
} from '@/components/orders/order-tracker';
import { OrderUnitsDialog } from '@/components/orders/order-units-dialog';
import { destroy, index, show } from '@/routes/orders';
import type { OrderShowProps, TrackerLine } from '@/types/domain';

/**
 * An order (INV-14): its header, the Order tracker and each product by colour and size, with Mark delivered, its move back and Cancel
 * units. The tracker arrives after the frame (a deferred prop) and its failure is rescued with "Try again".
 */
export default function OrdersShow({ order, tracker }: OrderShowProps) {
    const [opened, setOpened] = useState<{
        lineId: number;
        mode: UnitsMode;
    } | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [deleteProcessing, setDeleteProcessing] = useState(false);
    // The control that opened a dialog, so focus can return to it.
    const triggerRef = useRef<HTMLElement | null>(null);
    // The line is read from the tracker, so the window's figures follow every reload.
    const openedLine = opened
        ? tracker?.lines.find((line) => line.id === opened.lineId)
        : undefined;

    function rememberTrigger() {
        triggerRef.current =
            document.activeElement instanceof HTMLElement &&
            document.activeElement !== document.body
                ? document.activeElement
                : null;
    }

    function openUnits(line: TrackerLine, mode: UnitsMode) {
        rememberTrigger();
        setOpened({ lineId: line.id, mode });
    }

    function closeUnits() {
        setOpened(null);
        // Back to the button that opened the window, when it is still there.
        requestAnimationFrame(
            () => triggerRef.current?.isConnected && triggerRef.current.focus(),
        );
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

                <Deferred
                    data="tracker"
                    fallback={<OrderTrackerLoading />}
                    rescue={({ reloading }) => (
                        <OrderTrackerError reloading={reloading} />
                    )}
                >
                    {/* Built eagerly: never read before it lands. */}
                    {tracker && (
                        <>
                            <OrderTracker tracker={tracker} />
                            <section
                                aria-labelledby="order-products"
                                className="flex flex-col gap-3"
                            >
                                <h2
                                    id="order-products"
                                    className="text-lg font-semibold"
                                >
                                    Products
                                </h2>
                                {tracker.lines.map((line) => (
                                    <OrderProductCard
                                        key={line.id}
                                        line={line}
                                        onOpen={openUnits}
                                    />
                                ))}
                            </section>
                        </>
                    )}
                </Deferred>
            </div>

            <OrderUnitsDialog
                orderId={order.id}
                client={order.client}
                target={
                    opened && openedLine
                        ? { line: openedLine, mode: opened.mode }
                        : null
                }
                onClose={closeUnits}
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
