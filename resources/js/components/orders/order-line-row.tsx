import { Trash2 } from 'lucide-react';
import { OrderSizeFields } from '@/components/orders/order-size-fields';
import { FormField } from '@/components/products/form-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import { cn } from '@/lib/utils';
import type {
    OrderFormProduct,
    OrderLineFormRow,
    OrderLineSizeMode,
    Size,
    SizeList,
} from '@/types/domain';

/**
 * A line in the form: the submitted fields plus a client-only `uid`, so React
 * keeps a row's typed values when another row is removed. Never sent.
 */
export type OrderLineRow = OrderLineFormRow & { uid: string };

let lineCounter = 0;

/** An empty line, with a key that is unique on the page and never its index. */
export function newOrderLine(
    fields: OrderLineFormRow = { product_id: '', quantity: '' },
): OrderLineRow {
    lineCounter += 1;

    return { uid: `line-${lineCounter}`, ...fields };
}

/** How a line counts its units in the form: one field per size, or a single "Quantity". */
export type LineCounting =
    | { kind: 'sizes'; sizes: readonly Size[] }
    | { kind: 'quantity'; beforeSizes: boolean };

/**
 * How the line counts (S-60): a product already on the saved order keeps its
 * saved way (the server matches lines by product, so a removed line whose
 * product is added back keeps it too); any other line follows its product's
 * size list. Without a product, a single "Quantity".
 */
export function countingFor(
    row: OrderLineFormRow,
    products: readonly OrderFormProduct[],
    sizeLists: Readonly<Record<string, SizeList>>,
    savedModes?: ReadonlyMap<number, OrderLineSizeMode>,
): LineCounting {
    const product = products.find((option) => option.id === row.product_id);
    const saved = product ? savedModes?.get(product.id) : undefined;

    if (!product || saved === 'one_size') {
        return { kind: 'quantity', beforeSizes: false };
    }

    if (saved === 'before_sizes') {
        return { kind: 'quantity', beforeSizes: true };
    }

    const sizes = sizeLists[product.size_list_code]?.sizes ?? [];

    return sizes.length > 1 || (saved === 'by_size' && sizes.length > 0)
        ? { kind: 'sizes', sizes }
        : { kind: 'quantity', beforeSizes: false };
}

/** A saved line with units in cutting, ready to ship or shipped (S-49): it keeps its product and can't be removed. */
export type StartedLine = { name: string; count: number };

type OrderLineRowProps = {
    row: OrderLineRow;
    /** 1-based position, for the group's name. */
    number: number;
    /** Prefix that makes this editor's element ids unique on the page. */
    idPrefix: string;
    products: readonly OrderFormProduct[];
    counting: LineCounting;
    /** The server's messages for this line: by field, `sizes` for the line's sizes as a whole, `bySize` by size id. */
    errors: {
        product_id?: string;
        quantity?: string;
        sizes?: string;
        bySize?: Record<number, string | undefined>;
    };
    /** Set when work has started on this saved line. */
    started?: StartedLine;
    onChange: (patch: Partial<OrderLineFormRow>) => void;
    onRemove: () => void;
};

/** One product of the order: the picker, its quantity and a remove button named after the product. */
export function OrderLineRowFields({
    row,
    number,
    idPrefix,
    products,
    counting,
    errors,
    started,
    onChange,
    onRemove,
}: OrderLineRowProps) {
    const { locale } = useAppSettings();
    const product = products.find((option) => option.id === row.product_id);
    const base = `${idPrefix}-${row.uid}`;
    const removeHintId = `${base}-remove-hint`;
    const bySize = counting.kind === 'sizes';

    return (
        <div
            role="group"
            aria-label={`Line ${number}`}
            data-line-uid={row.uid}
            className={cn(
                'grid gap-3 rounded-lg border p-3',
                bySize
                    ? 'sm:grid-cols-[minmax(0,1fr)_auto]'
                    : 'sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_auto]',
            )}
        >
            <FormField
                id={`${base}-product`}
                label="Product"
                hint={
                    started &&
                    "The product can't change once work on this line has started."
                }
                error={errors.product_id}
            >
                {(control) => (
                    <Select
                        value={
                            row.product_id === '' ? '' : String(row.product_id)
                        }
                        disabled={started !== undefined}
                        onValueChange={(value) =>
                            onChange({ product_id: Number(value) })
                        }
                    >
                        <SelectTrigger
                            {...control}
                            data-action="product"
                            className="w-full"
                        >
                            <SelectValue placeholder="Choose a product…" />
                        </SelectTrigger>
                        <SelectContent>
                            {products.map((option) => (
                                <SelectItem
                                    key={option.id}
                                    value={String(option.id)}
                                >
                                    {option.name} · {option.sku}
                                    {(option.status !== 'active' ||
                                        option.deleted) &&
                                        ' (not active)'}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                )}
            </FormField>
            {counting.kind === 'sizes' ? (
                <div className="sm:col-span-2 sm:row-start-2">
                    <OrderSizeFields
                        idPrefix={base}
                        legend="Quantity per size"
                        showLegend
                        sizes={counting.sizes}
                        values={row.sizes ?? {}}
                        errors={errors.bySize}
                        totalError={errors.sizes}
                        onChange={(sizeId, value) =>
                            onChange({
                                sizes: { ...row.sizes, [sizeId]: value },
                            })
                        }
                    />
                </div>
            ) : (
                <FormField
                    id={`${base}-quantity`}
                    label="Quantity"
                    hint={counting.beforeSizes && 'Recorded before sizes.'}
                    error={errors.quantity}
                >
                    {(control) => (
                        <Input
                            {...control}
                            type="text"
                            inputMode="numeric"
                            autoComplete="off"
                            value={row.quantity}
                            onChange={(event) =>
                                onChange({ quantity: event.target.value })
                            }
                        />
                    )}
                </FormField>
            )}
            <Button
                type="button"
                variant="ghost"
                size="icon"
                aria-label={product ? `Remove ${product.name}` : 'Remove line'}
                title="Remove"
                disabled={started !== undefined}
                aria-describedby={started ? removeHintId : undefined}
                className={cn(
                    'text-destructive-foreground hover:text-destructive-foreground',
                    bySize ? 'sm:col-start-2 sm:row-start-1' : 'sm:mt-7',
                )}
                onClick={onRemove}
            >
                <Trash2 aria-hidden="true" />
            </Button>
            {started && (
                <p
                    id={removeHintId}
                    className="text-xs text-muted-foreground sm:col-span-full"
                >
                    {started.name} can't be removed:{' '}
                    {started.count === 1
                        ? '1 unit has'
                        : `${formatCount(started.count, locale)} units have`}{' '}
                    started.
                </p>
            )}
        </div>
    );
}
