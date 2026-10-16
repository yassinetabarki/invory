import { Trash2 } from 'lucide-react';
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
import type { OrderLineFormRow, OrderProduct } from '@/types/domain';

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

/** A saved line with units in cutting, ready to ship or shipped (S-49): it keeps its product and can't be removed. */
export type StartedLine = { name: string; count: number };

type OrderLineRowProps = {
    row: OrderLineRow;
    /** 1-based position, for the group's name. */
    number: number;
    /** Prefix that makes this editor's element ids unique on the page. */
    idPrefix: string;
    products: readonly OrderProduct[];
    /** The server's messages for this line, by field. */
    errors: { product_id?: string; quantity?: string };
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
    errors,
    started,
    onChange,
    onRemove,
}: OrderLineRowProps) {
    const { locale } = useAppSettings();
    const product = products.find((option) => option.id === row.product_id);
    const base = `${idPrefix}-${row.uid}`;
    const removeHintId = `${base}-remove-hint`;

    return (
        <div
            role="group"
            aria-label={`Line ${number}`}
            data-line-uid={row.uid}
            className="grid gap-3 rounded-lg border p-3 sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_auto]"
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
            <FormField
                id={`${base}-quantity`}
                label="Quantity"
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
            <Button
                type="button"
                variant="ghost"
                size="icon"
                aria-label={product ? `Remove ${product.name}` : 'Remove line'}
                title="Remove"
                disabled={started !== undefined}
                aria-describedby={started ? removeHintId : undefined}
                className="text-destructive-foreground hover:text-destructive-foreground sm:mt-7"
                onClick={onRemove}
            >
                <Trash2 aria-hidden="true" />
            </Button>
            {started && (
                <p
                    id={removeHintId}
                    className="text-xs text-muted-foreground sm:col-span-3"
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
