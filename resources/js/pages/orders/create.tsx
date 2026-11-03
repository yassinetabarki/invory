import { Head } from '@inertiajs/react';
import { OrderForm } from '@/components/orders/order-form';
import { create, index, store } from '@/routes/orders';
import type { OrderCreateProps } from '@/types/domain';

export default function OrdersCreate({
    cities,
    client_sources,
    client_preset,
}: OrderCreateProps) {
    return (
        <>
            <Head title="New order" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <OrderForm
                    title="New order"
                    action={store()}
                    cancelHref={index.url()}
                    cities={cities}
                    clientSources={client_sources}
                    clientPreset={client_preset}
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
