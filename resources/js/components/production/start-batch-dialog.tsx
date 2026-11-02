import { router, useHttp } from '@inertiajs/react';
import { TriangleAlert } from 'lucide-react';
import { useEffect, useId, useState } from 'react';
import type { FormEvent } from 'react';
import { typedUnits } from '@/components/orders/order-size-fields';
import { RestockProductPicker } from '@/components/restocks/restock-product-picker';
import { variantName } from '@/components/restocks/restock-line';
import { ColourTag } from '@/components/shared/colour-tag';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useAppSettings } from '@/hooks/use-app-settings';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import { useIsMobile } from '@/hooks/use-mobile';
import { formatCount, formatQuantity } from '@/lib/format';
import { cn } from '@/lib/utils';
import { store } from '@/routes/progress/batches';
import {
    line as lineRoute,
    materials as materialsRoute,
} from '@/routes/progress/products';
import type {
    BatchesStarted,
    RestockProductRef,
    StartBatchLine,
    StartBatchPreview,
} from '@/types/domain';

type Typed = Record<number, Record<number, string>>;

type StartBatchDialogProps = {
    open: boolean;
    /** The product chosen already (a product card's Start batch); null from the page header. */
    preset: RestockProductRef | null;
    onClose: () => void;
};

/** What the grid adds up to: the whole numbers typed, per colour and size (the page adds the typed counts, as Restock does). */
function typedVariants(line: StartBatchLine, typed: Typed) {
    return line.stock.colours.flatMap(({ colour }) =>
        line.sizes.flatMap((size) => {
            const units = typedUnits(typed[colour.id]?.[size.id]);

            return units > 0
                ? [
                      {
                          name: variantName(colour.name, size, line.one_size),
                          units,
                      },
                  ]
                : [];
        }),
    );
}

/** The grid as the server expects it: every colour × size, '' unless typed. */
function payloadUnits(line: StartBatchLine, typed: Typed): Typed {
    return Object.fromEntries(
        line.stock.colours.map(({ colour }) => [
            colour.id,
            Object.fromEntries(
                line.sizes.map((size) => [
                    size.id,
                    typed[colour.id]?.[size.id] ?? '',
                ]),
            ),
        ]),
    );
}

function useStartLine(productId: number | null) {
    const { get, cancel } = useHttp<Record<string, never>, StartBatchLine>();
    const [state, setState] = useState<{
        id: number;
        line: StartBatchLine | null;
        failed: boolean;
    } | null>(null);
    const [attempt, setAttempt] = useState(0);

    useEffect(() => {
        if (productId === null) {
            return;
        }

        let current = true;

        get(lineRoute.url(productId)).then(
            (data) =>
                current &&
                setState({ id: productId, line: data, failed: false }),
            () =>
                current &&
                setState({ id: productId, line: null, failed: true }),
        );

        return () => {
            current = false;
            cancel();
        };
    }, [get, cancel, productId, attempt]);

    const mine = state !== null && state.id === productId ? state : null;

    return {
        line: mine?.line ?? null,
        failed: mine?.failed ?? false,
        retry: () => setAttempt((count) => count + 1),
    };
}

function usePreview(productId: number | null, total: number) {
    const { get, cancel } = useHttp<Record<string, never>, StartBatchPreview>();
    const units = useDebouncedValue(total, 250);
    const [state, setState] = useState<{
        key: string;
        preview: StartBatchPreview;
    } | null>(null);
    const key = `${productId}:${units}`;

    useEffect(() => {
        if (productId === null || units < 1) {
            return;
        }

        let current = true;

        get(materialsRoute.url(productId, { query: { units } })).then(
            (data) =>
                current &&
                data &&
                Array.isArray(data.rows) &&
                setState({ key, preview: data }),
            () => undefined,
        );

        return () => {
            current = false;
            cancel();
        };
    }, [get, cancel, productId, units, key]);

    return state !== null && state.key === key && units === total
        ? state.preview
        : null;
}

function MaterialsTable({
    preview,
    compact,
}: {
    preview: StartBatchPreview;
    compact: boolean;
}) {
    const { locale } = useAppSettings();
    const short = preview.rows.some((row) => row.short_by !== null);

    if (!preview.has_composition) {
        return (
            <p className="text-sm text-muted-foreground">
                This product has no composition: no material is taken out of
                stock.
            </p>
        );
    }

    return (
        <table className="w-full text-sm">
            <caption className="mb-2 text-left font-medium">
                Materials for {formatCount(preview.units, locale)}{' '}
                {preview.units === 1 ? 'unit' : 'units'}
            </caption>
            <thead>
                <tr className="border-b text-left text-muted-foreground">
                    <th scope="col" className="py-1 pr-2 font-medium">
                        Material
                    </th>
                    <th
                        scope="col"
                        className="px-2 py-1 text-right font-medium"
                    >
                        Taken
                    </th>
                    {!compact && (
                        <th
                            scope="col"
                            className="px-2 py-1 text-right font-medium"
                        >
                            In stock
                        </th>
                    )}
                    <th
                        scope="col"
                        className="py-1 pl-2 text-right font-medium"
                    >
                        {short ? 'After · Short by' : 'After'}
                    </th>
                </tr>
            </thead>
            <tbody>
                {preview.rows.map((row) => {
                    const unit = row.material.unit;

                    return (
                        <tr
                            key={row.material.id}
                            className="border-b last:border-b-0"
                        >
                            <th
                                scope="row"
                                className="py-1 pr-2 text-left font-normal"
                            >
                                {row.material.name}
                            </th>
                            <td className="px-2 py-1 text-right font-mono tabular-nums">
                                {formatQuantity(row.quantity, { locale, unit })}
                            </td>
                            {!compact && (
                                <td className="px-2 py-1 text-right font-mono tabular-nums">
                                    {formatQuantity(row.in_stock, {
                                        locale,
                                        unit,
                                    })}
                                </td>
                            )}
                            <td className="py-1 pl-2 text-right font-mono tabular-nums">
                                {row.short_by !== null ? (
                                    <span className="inline-flex items-center gap-1 font-sans font-medium text-destructive-foreground">
                                        <TriangleAlert
                                            className="size-4"
                                            aria-hidden="true"
                                        />
                                        Short by{' '}
                                        {formatQuantity(row.short_by, {
                                            locale,
                                            unit,
                                        })}
                                    </span>
                                ) : (
                                    formatQuantity(row.after, { locale, unit })
                                )}
                            </td>
                        </tr>
                    );
                })}
            </tbody>
        </table>
    );
}

function Started({
    started,
    onClose,
}: {
    started: BatchesStarted;
    onClose: () => void;
}) {
    const { locale } = useAppSettings();
    const count = started.batches.length;

    return (
        <div className="flex flex-col gap-3">
            <p role="status" className="text-base font-semibold">
                {formatCount(count, locale)} {count === 1 ? 'batch' : 'batches'}{' '}
                started
            </p>
            <p className="text-sm">
                {formatCount(started.units, locale)}{' '}
                {started.units === 1 ? 'unit' : 'units'} of {started.product}{' '}
                started. Materials taken out of stock.
            </p>
            <ul className="flex flex-col gap-1 text-sm">
                {started.batches.map((batch) => (
                    <li key={batch.number}>
                        <span className="font-mono">{batch.number}</span> ·{' '}
                        {batch.size === null
                            ? batch.colour
                            : `${batch.colour} ${batch.size}`}{' '}
                        · {formatCount(batch.units, locale)}{' '}
                        {batch.units === 1 ? 'unit' : 'units'}
                    </li>
                ))}
            </ul>
            <DialogFooter>
                <Button type="button" onClick={onClose} className="max-sm:h-11">
                    Done
                </Button>
            </DialogFooter>
        </div>
    );
}

function StartForm({
    preset,
    onClose,
}: Pick<StartBatchDialogProps, 'preset' | 'onClose'>) {
    const uid = useId();
    const { locale } = useAppSettings();
    const isMobile = useIsMobile();
    const [product, setProduct] = useState<RestockProductRef | null>(preset);
    const [typed, setTyped] = useState<Typed>({});
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);
    const [started, setStarted] = useState<BatchesStarted | null>(null);
    const { line, failed, retry } = useStartLine(product?.id ?? null);
    const variants = line ? typedVariants(line, typed) : [];
    const total = variants.reduce((sum, variant) => sum + variant.units, 0);
    const preview = usePreview(line ? line.product.id : null, total);

    function setField(colourId: number, sizeId: number, value: string) {
        setTyped((current) => ({
            ...current,
            [colourId]: { ...current[colourId], [sizeId]: value },
        }));
    }

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        setProcessing(true);
        router.post(
            store.url(),
            {
                product_id: product?.id ?? null,
                units: line ? payloadUnits(line, typed) : {},
            },
            {
                preserveScroll: true,
                // The window stays open to show the batches started.
                preserveState: true,
                onSuccess: () => setErrors({}),
                // The batches started come back as a flash, once: the window then shows them.
                onFlash: (flash) => {
                    const done = (flash as { batches_started?: BatchesStarted })
                        .batches_started;

                    if (done) {
                        setStarted(done);
                    }
                },
                onError: (next) => setErrors(next),
                onHttpException: () => {
                    setErrors({
                        units: "Couldn't start the batch. Try again.",
                    });

                    return false;
                },
                onNetworkError: () => {
                    setErrors({
                        units: "Couldn't start the batch. Try again.",
                    });

                    return false;
                },
                onFinish: () => setProcessing(false),
            },
        );
    }

    if (started) {
        return <Started started={started} onClose={onClose} />;
    }

    const shelf = (colourId: number, sizeId: number) =>
        line?.stock.colours
            .find((entry) => entry.colour.id === colourId)
            ?.sizes.find((entry) => entry.size.id === sizeId)?.units ?? 0;

    return (
        <form
            noValidate
            onSubmit={submit}
            className="flex min-h-0 flex-1 flex-col gap-4"
        >
            <div className="-mx-1 flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto px-1">
                <div className="flex flex-col gap-1.5">
                    <span
                        id={`${uid}-product-label`}
                        className="text-sm font-medium"
                    >
                        Product
                    </span>
                    <RestockProductPicker
                        mode="filter"
                        id={`${uid}-product`}
                        labelledBy={`${uid}-product-label`}
                        value={product}
                        onSelect={(next) => {
                            setProduct(next);
                            setTyped({});
                            setErrors({});
                        }}
                    />
                    {line && line.can_produce !== null && (
                        <p className="text-sm text-muted-foreground">
                            Can produce now{' '}
                            <span className="font-mono">
                                {formatCount(line.can_produce, locale)}
                            </span>
                        </p>
                    )}
                    {errors.product_id && (
                        <p
                            className="text-sm text-destructive-foreground"
                            role="alert"
                        >
                            {errors.product_id}
                        </p>
                    )}
                </div>

                {failed && (
                    <div
                        role="alert"
                        className="flex items-center gap-3 text-sm"
                    >
                        <p>Couldn&apos;t load the product. Try again.</p>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={retry}
                        >
                            Try again
                        </Button>
                    </div>
                )}

                {line && (
                    <fieldset className="flex flex-col gap-3">
                        <legend className="mb-2 text-sm font-medium">
                            How many units of each colour and size?
                        </legend>
                        {line.stock.colours.map(({ colour }) => (
                            <div
                                key={colour.id}
                                className="flex flex-col gap-2"
                            >
                                <ColourTag
                                    colour={colour}
                                    className="text-sm font-medium"
                                />
                                <div className="grid grid-cols-3 gap-2 sm:grid-cols-[repeat(auto-fill,minmax(5rem,1fr))]">
                                    {line.sizes.map((size) => {
                                        const name = variantName(
                                            colour.name,
                                            size,
                                            line.one_size,
                                        );
                                        const fieldId = `${uid}-c${colour.id}-s${size.id}`;
                                        const error =
                                            errors[
                                                `units.${colour.id}.${size.id}`
                                            ];
                                        const onShelf = shelf(
                                            colour.id,
                                            size.id,
                                        );

                                        return (
                                            <div
                                                key={size.id}
                                                className="flex flex-col gap-1"
                                            >
                                                <label
                                                    htmlFor={fieldId}
                                                    className="text-xs font-semibold"
                                                >
                                                    {line.one_size
                                                        ? colour.name
                                                        : size.label}
                                                </label>
                                                <Input
                                                    id={fieldId}
                                                    aria-label={name}
                                                    inputMode="numeric"
                                                    autoComplete="off"
                                                    aria-invalid={
                                                        error ? true : undefined
                                                    }
                                                    aria-describedby={`${fieldId}-shelf`}
                                                    value={
                                                        typed[colour.id]?.[
                                                            size.id
                                                        ] ?? ''
                                                    }
                                                    className="h-11 text-center font-mono sm:h-9"
                                                    onChange={(event) =>
                                                        setField(
                                                            colour.id,
                                                            size.id,
                                                            event.target.value,
                                                        )
                                                    }
                                                />
                                                <span
                                                    id={`${fieldId}-shelf`}
                                                    className={cn(
                                                        'text-xs',
                                                        onShelf === 0
                                                            ? 'font-medium text-amber-700 dark:text-amber-400'
                                                            : 'text-muted-foreground',
                                                    )}
                                                >
                                                    Shelf{' '}
                                                    {onShelf === 0
                                                        ? 'Out'
                                                        : formatCount(
                                                              onShelf,
                                                              locale,
                                                          )}
                                                </span>
                                                {error && (
                                                    <span className="text-xs text-destructive-foreground">
                                                        {error}
                                                    </span>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        ))}
                        <p className="text-sm" aria-live="polite">
                            Total {formatCount(total, locale)}{' '}
                            {total === 1 ? 'unit' : 'units'} ·{' '}
                            {formatCount(variants.length, locale)}{' '}
                            {variants.length === 1 ? 'batch' : 'batches'}
                            {variants.length > 0 &&
                                ` (${variants.map((variant) => `${variant.name} ${formatCount(variant.units, locale)}`).join(', ')})`}
                        </p>
                    </fieldset>
                )}

                {preview && total > 0 && (
                    <MaterialsTable preview={preview} compact={isMobile} />
                )}

                {(errors.units || preview?.short_message) && (
                    <p
                        role="alert"
                        className="text-sm text-destructive-foreground"
                    >
                        {errors.units ?? preview?.short_message}
                    </p>
                )}
            </div>

            <DialogFooter className="shrink-0 max-sm:sticky max-sm:bottom-0 max-sm:bg-background max-sm:pt-2">
                <Button
                    type="button"
                    variant="outline"
                    disabled={processing}
                    onClick={onClose}
                    className="max-sm:h-11"
                >
                    Cancel
                </Button>
                <Button
                    type="submit"
                    disabled={processing}
                    className="max-sm:h-11"
                >
                    {processing && <Spinner />}
                    Start batch
                </Button>
            </DialogFooter>
        </form>
    );
}

/**
 * Start batch (INV-5, AC-2): a product, the units of each colour and size (with the shelf under each field), the materials it takes,
 * then the batches started. Full screen on a phone, its buttons in a bar at the bottom.
 */
export function StartBatchDialog({
    open,
    preset,
    onClose,
}: StartBatchDialogProps) {
    return (
        <Dialog open={open} onOpenChange={(next) => !next && onClose()}>
            <DialogContent className="flex max-h-[90vh] flex-col max-sm:h-full max-sm:max-h-full max-sm:max-w-full max-sm:rounded-none sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Start batch</DialogTitle>
                    <DialogDescription>
                        Make products for your shelf. Materials are taken out of
                        stock now.
                    </DialogDescription>
                </DialogHeader>
                {open && (
                    <StartForm
                        key={preset?.id ?? 'any'}
                        preset={preset}
                        onClose={onClose}
                    />
                )}
            </DialogContent>
        </Dialog>
    );
}
