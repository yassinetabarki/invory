import { Head } from '@inertiajs/react';
import { OrderForm } from '@/components/orders/order-form';
import { create, index, store } from '@/routes/orders';
import type { OrderCreateProps } from '@/types/domain';

export default function OrdersCreate({ products }: OrderCreateProps) {
    return (
        <>
            <Head title="New order" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <OrderForm
                    title="New order"
                    products={products}
                    action={store()}
                    cancelHref={index.url()}
                />
            </div>
        </>
    );
}

OrdersCreate.layout = {
    breadcrumbs: [
        { title: 'Orders', href: index() },
        { title: 'New order', href: create() },
    ],
};
