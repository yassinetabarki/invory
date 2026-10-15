import { Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useId, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import { newOrderLine } from '@/components/orders/order-line-row';
import type { OrderLineRow } from '@/components/orders/order-line-row';
import { OrderLinesEditor } from '@/components/orders/order-lines-editor';
import { FormField } from '@/components/products/form-field';
import { PageHeader } from '@/components/shared/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import type { Order, OrderFormData, OrderProduct } from '@/types/domain';

/** The form's state: the submitted fields, with a client-only key on each line. */
type OrderFormState = Omit<OrderFormData, 'lines'> & { lines: OrderLineRow[] };

type OrderFormProps = {
    /** The order being edited; leave out to record a new one. */
    order?: Order;
    title: string;
    /** Every product the picker offers (the server decides which). */
    products: OrderProduct[];
    /** Where to send the form (Wayfinder in the page). */
    action: { url: string; method: 'post' | 'put' };
    cancelHref: string;
};

/** Starting values: today and one empty line, or the order's own details and lines. */
function toFormState(order: Order | undefined, today: string): OrderFormState {
    if (!order) {
        return {
            client: '',
            ordered_on: today,
            note: '',
            lines: [newOrderLine()],
        };
    }

    return {
        client: order.client,
        ordered_on: order.ordered_on,
        note: order.note ?? '',
        lines: order.lines.map((line) =>
            newOrderLine({
                product_id: line.product.id,
                quantity: String(line.quantity),
            }),
        ),
    };
}

/**
 * Record or edit an order: client, date, note and its product lines. Nothing
 * is computed or validated here; every message comes from the server and shows
 * under its field. Toasts come from the server's flash.
 */
export function OrderForm({
    order,
    title,
    products,
    action,
    cancelHref,
}: OrderFormProps) {
    const uid = useId();
    const { today } = usePage().props.app;
    const formRef = useRef<HTMLFormElement>(null);
    // Only seeds the form; computed once so the lines keep their keys.
    const [initial] = useState(() => toFormState(order, today));
    const form = useForm<OrderFormState>(initial);
    const { errors, processing } = form;

    const errorSignature = Object.entries(errors)
        .filter(([, message]) => Boolean(message))
        .map(([name, message]) => `${name}:${message}`)
        .join('|');

    // Move focus to the first invalid field once a submission has failed.
    useEffect(() => {
        if (processing || errorSignature === '') {
            return;
        }

        formRef.current
            ?.querySelector<HTMLElement>('[aria-invalid="true"]')
            ?.focus();
    }, [errorSignature, processing]);

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        // The client-only keys never leave the browser.
        form.transform((data) => ({
            ...data,
            lines: data.lines.map(({ product_id, quantity }) => ({
                product_id,
                quantity,
            })),
        }));
        form.submit(action, { preserveScroll: 'errors' });
    }

    return (
        <form
            ref={formRef}
            noValidate
            className="flex flex-col gap-6"
            onSubmit={handleSubmit}
        >
            <PageHeader title={title} />

            <section
                aria-labelledby={`${uid}-details`}
                className="flex flex-col gap-4 rounded-xl border bg-card p-5 text-card-foreground"
            >
                <h2 id={`${uid}-details`} className="text-base font-bold">
                    Details
                </h2>
                <div className="grid gap-4 sm:grid-cols-[2fr_1fr]">
                    <FormField
                        id={`${uid}-client`}
                        label="Client"
                        error={errors.client}
                    >
                        {(control) => (
                            <Input
                                {...control}
                                value={form.data.client}
                                placeholder="e.g. Boutique Lina"
                                autoComplete="off"
                                onChange={(event) =>
                                    form.setData('client', event.target.value)
                                }
                            />
                        )}
                    </FormField>
                    <FormField
                        id={`${uid}-ordered-on`}
                        label="Order date"
                        error={errors.ordered_on}
                    >
                        {(control) => (
                            <Input
                                {...control}
                                type="date"
                                max={today}
                                value={form.data.ordered_on}
                                onChange={(event) =>
                                    form.setData(
                                        'ordered_on',
                                        event.target.value,
                                    )
                                }
                            />
                        )}
                    </FormField>
                    <FormField
                        id={`${uid}-note`}
                        label="Note"
                        optional
                        error={errors.note}
                        className="sm:col-span-2"
                    >
                        {(control) => (
                            <Textarea
                                {...control}
                                rows={3}
                                value={form.data.note}
                                placeholder="Delivery date, packing, anything the client asked for"
                                onChange={(event) =>
                                    form.setData('note', event.target.value)
                                }
                            />
                        )}
                    </FormField>
                </div>
            </section>

            <OrderLinesEditor
                rows={form.data.lines}
                products={products}
                errors={errors}
                onChange={(lines) => form.setData('lines', lines)}
            />

            <div className="flex flex-wrap items-center gap-2">
                <Button type="submit" disabled={processing}>
                    {processing && <Spinner />}
                    Save order
                </Button>
                <Button asChild variant="outline">
                    <Link href={cancelHref}>Cancel</Link>
                </Button>
            </div>
        </form>
    );
}
