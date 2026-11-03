import { Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useId, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import { ClientCard } from '@/components/clients/client-card';
import { EMPTY_CLIENT } from '@/components/clients/client-fields';
import { EditClientField } from '@/components/clients/edit-client-field';
import {
    newOrderLine,
    OrderLinesEditor,
    orderSummary,
    orderUnits,
} from '@/components/orders/order-lines-editor';
import type { OrderLineEntry } from '@/components/orders/order-lines-editor';
import { gridColours } from '@/components/orders/quantity-grid';
import { FormField } from '@/components/products/form-field';
import { PageHeader } from '@/components/shared/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useAppSettings } from '@/hooks/use-app-settings';
import type {
    ClientSource,
    ClientSummary,
    Option,
    Colour,
    Order,
    OrderFormData,
    OrderStockProduct,
} from '@/types/domain';

/** The form's state: the submitted fields, each line with its product's grid data and a client-only key. */
type OrderFormState = Omit<OrderFormData, 'lines'> & {
    lines: OrderLineEntry[];
};

type OrderFormProps = {
    /** The order being edited; leave out to record a new one. */
    order?: Order;
    title: string;
    /** Edit order: each line's product with its sizes and shelf stock, in line order (INV-13). */
    lineProducts?: OrderStockProduct[];
    /** Where to send the form (Wayfinder in the page). */
    action: { url: string; method: 'post' | 'put' };
    cancelHref: string;
    /** New order's Client card (INV-4): the workshop's cities, the sources, and the client chosen on her page. */
    cities?: readonly string[];
    clientSources?: Option<ClientSource>[];
    clientPreset?: ClientSummary | null;
};

/** Starting values: today and no product yet, or the order's own details and lines. */
function toFormState(
    order: Order | undefined,
    today: string,
    preset: ClientSummary | null,
    lineProducts: readonly OrderStockProduct[],
): OrderFormState {
    if (!order) {
        return {
            client: { ...EMPTY_CLIENT, phone: preset?.phone ?? '' },
            ordered_on: today,
            note: '',
            lines: [],
        };
    }

    // Edit order sends the order's client (INV-4-i gives it a picker).
    return {
        client_id: order.client_id ?? '',
        ordered_on: order.ordered_on,
        note: order.note ?? '',
        lines: order.lines.flatMap((line) => {
            const product = lineProducts.find(
                (entry) => entry.product.id === line.product.id,
            );

            if (!product) {
                return [];
            }

            const units: Record<number, Record<number, string>> = {};
            const floors: Record<string, number> = {};
            const held = new Map<number, Colour>();

            for (const row of line.sizes) {
                units[row.colour.id] = {
                    ...units[row.colour.id],
                    [row.size.id]: String(row.quantity),
                };
                held.set(row.colour.id, row.colour);

                // A cell can't go below its delivered and cancelled units (INV-14 AC-6).
                if (row.delivered + row.cancelled >= 1) {
                    floors[`${row.colour.id}.${row.size.id}`] =
                        row.delivered + row.cancelled;
                }
            }

            return [
                newOrderLine(product, {
                    units,
                    floors,
                    heldColours: [...held.values()],
                    started: line.delivered + line.cancelled,
                }),
            ];
        }),
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
    lineProducts = [],
    action,
    cancelHref,
    cities,
    clientSources = [],
    clientPreset = null,
}: OrderFormProps) {
    const uid = useId();
    const { today } = usePage().props.app;
    const formRef = useRef<HTMLFormElement>(null);
    const { locale } = useAppSettings();
    // Only seeds the form; computed once so the lines keep their keys.
    const [initial] = useState(() =>
        toFormState(order, today, clientPreset, lineProducts),
    );
    const form = useForm<OrderFormState>(initial);
    const [saveFailed, setSaveFailed] = useState(false);
    const { errors, processing } = form;
    const client = form.data.client;
    const clientErrors = {
        phone: errors['client.phone'],
        name: errors['client.name'],
        city: errors['client.city'],
        source: errors['client.source'],
        source_other: errors['client.source_other'],
    };

    // From a client page, the client is known: the focus starts in Products (INV-4, AC-5).
    useEffect(() => {
        if (clientPreset) {
            formRef.current
                ?.querySelector<HTMLElement>('[data-action="product"]')
                ?.focus();
        }
    }, [clientPreset]);

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

        // Only the product and its units leave the browser: every colour and size of the grid, an untouched one as ''.
        form.transform((data) => ({
            ...data,
            lines: data.lines.map((entry) => ({
                product_id: entry.product.product.id,
                units: Object.fromEntries(
                    gridColours(entry.product, entry.heldColours).map(
                        (colour) => [
                            colour.id,
                            Object.fromEntries(
                                entry.product.sizes.map((size) => [
                                    size.id,
                                    entry.units[colour.id]?.[size.id] ?? '',
                                ]),
                            ),
                        ],
                    ),
                ),
            })),
        }));
        setSaveFailed(false);
        form.submit(action, {
            preserveScroll: 'errors',
            // A server or network failure keeps everything typed (INV-13 AC-3).
            onHttpException: () => {
                setSaveFailed(true);

                return false;
            },
            onNetworkError: () => {
                setSaveFailed(true);

                return false;
            },
        });
    }

    const details = (
        <section
            aria-labelledby={`${uid}-details`}
            className="flex flex-col gap-4 rounded-xl border bg-card p-5 text-card-foreground"
        >
            <h2 id={`${uid}-details`} className="text-base font-bold">
                Details
            </h2>
            <div className="grid gap-4 sm:grid-cols-[2fr_1fr]">
                {!client && order && (
                    <EditClientField
                        id={`${uid}-client`}
                        clientId={form.data.client_id ?? ''}
                        clientName={order.client}
                        onChange={(chosen) =>
                            form.setData('client_id', chosen.id)
                        }
                        error={errors.client_id}
                        cities={cities ? [...cities] : undefined}
                        sources={clientSources}
                    />
                )}
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
                                form.setData('ordered_on', event.target.value)
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
    );

    const lines = (
        <OrderLinesEditor
            entries={form.data.lines}
            errors={errors}
            onChange={(lines) => form.setData('lines', lines)}
        />
    );

    return (
        <form
            ref={formRef}
            noValidate
            // On a phone, room under the fields for the fixed Save bar, and a focused field always scrolls clear of it.
            className="flex flex-col gap-6 max-md:pb-32 max-md:[&_input]:scroll-mb-36 max-md:[&_textarea]:scroll-mb-36"
            onSubmit={handleSubmit}
        >
            <PageHeader title={title} />

            {client ? (
                <>
                    <ClientCard
                        values={client}
                        onChange={(values) => form.setData('client', values)}
                        errors={clientErrors}
                        cities={cities ?? []}
                        sources={clientSources}
                        preset={clientPreset}
                    />
                    {lines}
                    {details}
                </>
            ) : (
                <>
                    {details}
                    {lines}
                </>
            )}

            {saveFailed && (
                <p role="alert" className="text-sm text-destructive-foreground">
                    Couldn't save the order. Try again.
                </p>
            )}

            <div className="flex flex-wrap items-center gap-2 max-md:fixed max-md:inset-x-0 max-md:bottom-0 max-md:z-20 max-md:border-t max-md:bg-background max-md:p-3">
                {form.data.lines.length > 0 && (
                    <p className="text-sm text-muted-foreground tabular-nums max-md:w-full md:order-last">
                        {orderSummary(
                            orderUnits(form.data.lines),
                            form.data.lines.length,
                            locale,
                        )}
                    </p>
                )}
                <Button
                    type="submit"
                    disabled={processing}
                    className="max-md:h-11 max-md:flex-1"
                >
                    {processing && <Spinner />}
                    Save order
                </Button>
                <Button asChild variant="outline" className="max-md:h-11">
                    <Link href={cancelHref}>Cancel</Link>
                </Button>
            </div>
        </form>
    );
}
