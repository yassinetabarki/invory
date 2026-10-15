import { Head } from '@inertiajs/react';
import { OrderForm } from '@/components/orders/order-form';
import { edit, index, update } from '@/routes/orders';
import type { OrderEditProps } from '@/types/domain';

export default function OrdersEdit({ order, products }: OrderEditProps) {
    return (
        <>
            <Head title="Edit order" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                {/* Keyed by id: the form's values are only seeded from the order. */}
                <OrderForm
                    key={order.id}
                    order={order}
                    title="Edit order"
                    products={products}
                    action={update(order.id)}
                    cancelHref={index.url()}
                />
            </div>
        </>
    );
}

OrdersEdit.layout = (props: OrderEditProps) => ({
    breadcrumbs: [
        { title: 'Orders', href: index() },
        { title: 'Edit order', href: edit(props.order.id) },
    ],
});
