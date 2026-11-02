import { Head, router, useHttp, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import RestockProductController from '@/actions/App/Http/Controllers/RestockProductController';
import type { Typed } from '@/components/products/finished-stock-fields';
import { FormField } from '@/components/products/form-field';
import {
    lineAdding,
    RestockLineCard,
    restockFieldId,
} from '@/components/restocks/restock-line';
import { RestockProductPicker } from '@/components/restocks/restock-product-picker';
import { PageHeader } from '@/components/shared/page-header';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useAppSettings } from '@/hooks/use-app-settings';
import { useIsMobile } from '@/hooks/use-mobile';
import { formatCount } from '@/lib/format';
import { cn } from '@/lib/utils';
import { show as productShow } from '@/routes/products';
import { create, index, store } from '@/routes/restocks';
import type {
    NewRestockFormData,
    RestockCreateProps,
    RestockLine,
    RestockProductRef,
} from '@/types/domain';

/** A restock holds at most this many products (the server says the same). */
export const MAX_PRODUCTS = 50;

/** "34 units", "1 unit". */
function units(count: number, locale: string): string {
    return `${formatCount(count, locale)} ${count === 1 ? 'unit' : 'units'}`;
}

/** Every field of a line, '' unless typed: what the server expects for each product. */
function fullGrid(
    line: RestockLine,
    values: Typed,
): Record<number, Record<number, string>> {
    return Object.fromEntries(
        line.stock.colours.map(({ colour }) => [
            colour.id,
            Object.fromEntries(
                line.sizes.map((size) => [
                    size.id,
                    values[colour.id]?.[size.id] ?? '',
                ]),
            ),
        ]),
    );
}

/** The first field of a line (its first colour, first size): the focus goes there when the line is added. */
function firstFieldOf(line: RestockLine): string | null {
    const colour = line.stock.colours[0]?.colour;
    const size = line.sizes[0];

    return colour && size
        ? restockFieldId(line.product.id, colour.id, size.id)
        : null;
}

/**
 * New restock (INV-3, AC-3): the details (date, reference, note), the products
 * that were made or arrived, each with a field per colour and size, the total,
 * and **Restock 34 units**, which saves everything at once. One wrong field
 * saves nothing and everything typed stays; the server's messages go under the
 * product they are about, and a summary at the top names the products to
 * check. From a product's page (`?product=`) that product is already there,
 * the focus in its first field, and saving or cancelling goes back to it.
 */
export default function RestocksCreate({ preset }: RestockCreateProps) {
    const { locale } = useAppSettings();
    const mobile = useIsMobile();
    const today = usePage().props.app.today;
    const { get: fetchLine } = useHttp<Record<string, never>, RestockLine>();

    const [details, setDetails] = useState({
        restocked_on: today,
        reference: '',
        note: '',
    });
    const [lines, setLines] = useState<RestockLine[]>(preset ? [preset] : []);
    const [values, setValues] = useState<Record<number, Typed>>({});
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);
    const [discarding, setDiscarding] = useState(false);
    const [addFailed, setAddFailed] = useState<string | null>(null);
    const [focusId, setFocusId] = useState<string | null>(
        preset ? firstFieldOf(preset) : null,
    );
    const topRef = useRef<HTMLDivElement>(null);

    // Focus a field once it is on the page (a line just added, or the first wrong field after saving).
    useEffect(() => {
        if (focusId !== null) {
            document.getElementById(focusId)?.focus();
            setFocusId(null);
        }
    }, [focusId]);

    const fromProduct = preset?.product.id ?? null;
    const total = lines.reduce(
        (sum, line) => sum + lineAdding(line, values[line.product.id] ?? {}),
        0,
    );

    function change(
        productId: number,
        colourId: number,
        sizeId: number,
        value: string,
    ) {
        setValues((current) => ({
            ...current,
            [productId]: {
                ...current[productId],
                [colourId]: {
                    ...current[productId]?.[colourId],
                    [sizeId]: value,
                },
            },
        }));
    }

    function remove(productId: number) {
        setLines((current) =>
            current.filter((line) => line.product.id !== productId),
        );
        setValues((current) => {
            const { [productId]: _removed, ...rest } = current;

            return rest;
        });
    }

    function add(product: RestockProductRef | null) {
        if (product === null) {
            return;
        }

        const existing = lines.find((line) => line.product.id === product.id);

        if (existing) {
            setFocusId(firstFieldOf(existing));

            return;
        }

        if (lines.length >= MAX_PRODUCTS) {
            setAddFailed('A restock can hold up to 50 products.');

            return;
        }

        setAddFailed(null);
        fetchLine(RestockProductController.show.url(product.id)).then(
            (line) => {
                setLines((current) =>
                    current.some(
                        (entry) => entry.product.id === line.product.id,
                    )
                        ? current
                        : [...current, line],
                );
                setFocusId(firstFieldOf(line));
            },
            () => setAddFailed(`Couldn't add ${product.name}. Try again.`),
        );
    }

    function leave() {
        if (fromProduct !== null) {
            router.visit(productShow.url(fromProduct));
        } else {
            router.visit(index.url());
        }
    }

    function cancel() {
        if (total > 0) {
            setDiscarding(true);
        } else {
            leave();
        }
    }

    /** The first field the server refused, in the order of the page. */
    function firstWrongField(next: Record<string, string>): string | null {
        for (const [i, line] of lines.entries()) {
            for (const { colour } of line.stock.colours) {
                for (const size of line.sizes) {
                    if (next[`products.${i}.units.${colour.id}.${size.id}`]) {
                        return restockFieldId(
                            line.product.id,
                            colour.id,
                            size.id,
                        );
                    }
                }
            }
        }

        return null;
    }

    /** A line the server says changed meanwhile: shown again with its new colours and sizes, its units cleared. */
    function refreshLine(productId: number) {
        fetchLine(RestockProductController.show.url(productId)).then(
            (line) => {
                setLines((current) =>
                    current.map((entry) =>
                        entry.product.id === productId ? line : entry,
                    ),
                );
                setValues((current) => ({ ...current, [productId]: {} }));
            },
            () => undefined,
        );
    }

    function save() {
        const data: NewRestockFormData = {
            ...details,
            from_product: fromProduct,
            products: lines.map((line) => ({
                product_id: line.product.id,
                units: fullGrid(line, values[line.product.id] ?? {}),
            })),
        };

        router.post(store.url(), data, {
            preserveScroll: true,
            preserveState: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onSuccess: () => setErrors({}),
            onError: (next) => {
                setErrors(next);
                lines.forEach((line, i) => {
                    if (next[`products.${i}.product`]) {
                        refreshLine(line.product.id);
                    }
                });

                const wrong = firstWrongField(next);

                if (wrong) {
                    setFocusId(wrong);
                } else {
                    topRef.current?.focus();
                }
            },
            onHttpException: () => {
                setErrors({ save: "Couldn't save the restock. Try again." });

                return false;
            },
            onNetworkError: () => {
                setErrors({ save: "Couldn't save the restock. Try again." });

                return false;
            },
        });
    }

    // What the top of the page says: products deleted or changed meanwhile, and the products with refused fields.
    const topMessages: string[] = [];

    lines.forEach((line, i) => {
        const product = errors[`products.${i}.product`];
        const fields = Object.keys(errors).filter((key) =>
            key.startsWith(`products.${i}.units.`),
        ).length;

        if (product) {
            topMessages.push(product);
        }

        if (fields > 0) {
            topMessages.push(
                `Check ${line.product.name}: ${fields} ${fields === 1 ? "field can't" : "fields can't"} be saved.`,
            );
        }
    });

    if (errors.save) {
        topMessages.push(errors.save);
    }

    const button = total > 0 ? `Restock ${units(total, locale)}` : 'Restock';

    return (
        <>
            <Head title="New restock" />

            <form
                noValidate
                className={cn(
                    'mx-auto flex w-full max-w-[960px] flex-1 flex-col gap-5 p-4 md:p-6',
                    mobile && 'pb-28',
                )}
                onSubmit={(event) => {
                    event.preventDefault();
                    save();
                }}
            >
                <PageHeader
                    title="New restock"
                    description="Add finished units that were made or arrived. They are added to what is on the shelf."
                />

                <div ref={topRef} tabIndex={-1} className="outline-none">
                    {topMessages.length > 0 && (
                        <div
                            role="alert"
                            className="flex flex-col gap-1 rounded-lg border border-destructive/40 bg-destructive/5 p-3 text-sm text-destructive-foreground"
                        >
                            {topMessages.map((message) => (
                                <p key={message}>{message}</p>
                            ))}
                        </div>
                    )}
                </div>

                <section
                    aria-labelledby="restock-details"
                    className="flex flex-col gap-4 rounded-xl border bg-card p-4 text-card-foreground"
                >
                    <h2 id="restock-details" className="font-semibold">
                        Details
                    </h2>
                    <div className="grid gap-4 md:grid-cols-[10rem_1fr_1.5fr]">
                        <FormField
                            id="restock-date"
                            label="Date"
                            error={errors.restocked_on}
                        >
                            {(control) => (
                                <Input
                                    {...control}
                                    type="date"
                                    max={today}
                                    className="max-sm:h-11"
                                    value={details.restocked_on}
                                    onChange={(event) =>
                                        setDetails({
                                            ...details,
                                            restocked_on: event.target.value,
                                        })
                                    }
                                />
                            )}
                        </FormField>
                        <FormField
                            id="restock-reference"
                            label="Reference"
                            optional
                            hint="Delivery note, batch or who made them"
                            error={errors.reference}
                        >
                            {(control) => (
                                <Input
                                    {...control}
                                    autoComplete="off"
                                    className="max-sm:h-11"
                                    value={details.reference}
                                    onChange={(event) =>
                                        setDetails({
                                            ...details,
                                            reference: event.target.value,
                                        })
                                    }
                                />
                            )}
                        </FormField>
                        <FormField
                            id="restock-note"
                            label="Note"
                            optional
                            error={errors.note}
                        >
                            {(control) => (
                                <Input
                                    {...control}
                                    autoComplete="off"
                                    className="max-sm:h-11"
                                    value={details.note}
                                    onChange={(event) =>
                                        setDetails({
                                            ...details,
                                            note: event.target.value,
                                        })
                                    }
                                />
                            )}
                        </FormField>
                    </div>
                </section>

                <section
                    aria-labelledby="restock-products"
                    className="flex flex-col gap-3"
                >
                    <h2 id="restock-products" className="font-semibold">
                        Products
                    </h2>
                    {lines.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                            Add the products that were made or arrived, or scan
                            their labels.
                        </p>
                    )}
                    {lines.map((line, i) => {
                        const prefix = `products.${i}.units.`;
                        const fieldErrors = Object.fromEntries(
                            Object.entries(errors)
                                .filter(([key]) => key.startsWith(prefix))
                                .map(([key, message]) => [
                                    key.slice(prefix.length),
                                    message,
                                ]),
                        );

                        return (
                            <RestockLineCard
                                key={line.product.id}
                                line={line}
                                values={values[line.product.id] ?? {}}
                                onChange={(colourId, sizeId, value) =>
                                    change(
                                        line.product.id,
                                        colourId,
                                        sizeId,
                                        value,
                                    )
                                }
                                onRemove={() => remove(line.product.id)}
                                fieldErrors={fieldErrors}
                                lineError={errors[`products.${i}`]}
                            />
                        );
                    })}
                    <div className="flex flex-col gap-2">
                        <div>
                            <RestockProductPicker
                                mode="add"
                                chosen={lines.map((line) => line.product.id)}
                                onSelect={add}
                            />
                        </div>
                        {addFailed && (
                            <p
                                role="alert"
                                className="text-sm text-destructive-foreground"
                            >
                                {addFailed}
                            </p>
                        )}
                    </div>
                </section>

                <div className="flex flex-col gap-1">
                    <div className="flex items-center justify-between gap-3 rounded-lg bg-muted px-4 py-3 text-sm">
                        <span>Total on this restock</span>
                        <span>
                            <span className="font-semibold tabular-nums">
                                {units(total, locale)}
                            </span>
                            {' · '}
                            {formatCount(lines.length, locale)}{' '}
                            {lines.length === 1 ? 'product' : 'products'}
                        </span>
                    </div>
                    {errors.products && (
                        <p className="text-sm text-destructive-foreground">
                            {errors.products}
                        </p>
                    )}
                </div>

                <p className="text-sm text-muted-foreground">
                    Adds to what is on the shelf. Doesn&apos;t change materials,
                    &quot;Can produce now&quot; or sales. To set a count after a
                    stock check, use Update stock on the product&apos;s page.
                </p>

                <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <Button
                        type="button"
                        variant="outline"
                        className="max-sm:h-11"
                        onClick={cancel}
                    >
                        Cancel
                    </Button>
                    {!mobile && (
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            {button}
                        </Button>
                    )}
                </div>

                {/* On a phone the total and the button stay at the bottom of the screen; the form's bottom padding keeps
                    the field with the focus above it. */}
                {mobile && (
                    <div className="fixed inset-x-0 bottom-0 z-10 flex items-center gap-3 border-t bg-background p-3">
                        <span className="shrink-0 text-sm">
                            Total{' '}
                            <span className="font-semibold tabular-nums">
                                {units(total, locale)}
                            </span>
                        </span>
                        <Button
                            type="submit"
                            disabled={processing}
                            className="h-11 flex-1"
                        >
                            {processing && <Spinner />}
                            {button}
                        </Button>
                    </div>
                )}
            </form>

            <AlertDialog open={discarding} onOpenChange={setDiscarding}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            Discard this restock?
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            The {units(total, locale)} you entered{' '}
                            {total === 1 ? "isn't" : "aren't"} added to the
                            shelf.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Keep editing</AlertDialogCancel>
                        <AlertDialogAction
                            variant="destructive"
                            onClick={leave}
                        >
                            Discard restock
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}

RestocksCreate.layout = {
    breadcrumbs: [
        { title: 'Restocks', href: index() },
        { title: 'New restock', href: create() },
    ],
};
