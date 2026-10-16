import { Head } from '@inertiajs/react';
import { index, show } from '@/routes/orders';
import type { OrderShowProps } from '@/types/domain';

// STUB: S-49-d
export default function OrdersShow({ order }: OrderShowProps) {
    return (
        <>
            <Head title={order.client} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <h1 className="text-xl font-semibold">{order.client}</h1>
            </div>
        </>
    );
}

OrdersShow.layout = (props: OrderShowProps) => ({
    breadcrumbs: [
        { title: 'Orders', href: index() },
        { title: props.order.client, href: show(props.order.id) },
    ],
});
