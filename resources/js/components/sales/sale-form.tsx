import { useForm, usePage } from '@inertiajs/react';
import { Info } from 'lucide-react';
import { useEffect, useId, useRef } from 'react';
import type { FormEvent, ReactNode } from 'react';
import SaleController from '@/actions/App/Http/Controllers/SaleController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { useAppSettings } from '@/hooks/use-app-settings';
import { cn } from '@/lib/utils';
import type { Sale, SaleFormData, SaleProductOption } from '@/types/domain';

type SaleFormProps = {
    /** The sale being edited; `null` records a new one. */
    sale: Sale | null;
    /** Every non-deleted product; only the active ones can be picked. */
    products: SaleProductOption[];
    /** Product to pre-select when recording (the page's `record` prop). */
    initialProductId: number | null;
    /** Called after a successful save and by Cancel. */
    onClose: () => void;
};

/** Attributes every control receives so label, hint and error are wired up. */
type ControlProps = {
    id: string;
    'aria-invalid': true | undefined;
    'aria-describedby': string | undefined;
};

type FieldProps = {
    id: string;
    label: string;
    optional?: boolean;
    hint?: string;
    error?: string;
    /** Id of a visible prefix (currency) that should also be announced. */
    prefixId?: string;
    className?: string;
    children: (control: ControlProps) => ReactNode;
};

function Field({
    id,
    label,
    optional = false,
    hint,
    error,
    prefixId,
    className,
    children,
}: FieldProps) {
    const describedBy =
        [
            prefixId ?? null,
            hint ? `${id}-hint` : null,
            error ? `${id}-error` : null,
        ]
            .filter(Boolean)
            .join(' ') || undefined;

    return (
        <div className={cn('space-y-2', className)}>
            <Label htmlFor={id}>
                {label}
                {optional && (
                    <span className="font-normal text-muted-foreground">
                        {' '}
                        (optional)
                    </span>
                )}
            </Label>
            {children({
                id,
                'aria-invalid': error ? true : undefined,
                'aria-describedby': describedBy,
            })}
            {hint && (
                <p id={`${id}-hint`} className="text-xs text-muted-foreground">
                    {hint}
                </p>
            )}
            {error && (
                <p
                    id={`${id}-error`}
                    className="text-sm text-destructive-foreground"
                >
                    {error}
                </p>
            )}
        </div>
    );
}

/** The currency's own symbol or code from Intl: `€` for EUR, `TND` for TND (en). */
function currencyAffix(currency: string, locale: string): string {
    try {
        return (
            new Intl.NumberFormat(locale, {
                style: 'currency',
                currency,
                currencyDisplay: 'narrowSymbol',
            })
                .formatToParts(0)
                .find((part) => part.type === 'currency')?.value ?? currency
        );
    } catch {
        return currency;
    }
}

/**
 * Record a sale / edit a sale. One form for both: pass `sale` to edit.
 * The total is never computed here (the server does it when saving), and the
 * unit price is the server's selling-price string copied as is. The panel or
 * sheet around it owns the heading and the close button.
 */
export function SaleForm({
    sale,
    products,
    initialProductId,
    onClose,
}: SaleFormProps) {
    const uid = useId();
    const { currency, locale } = useAppSettings();
    const { today } = usePage().props.app;
    const formRef = useRef<HTMLFormElement>(null);
    const productRef = useRef<HTMLButtonElement>(null);

    // The product being edited stays selectable even when it is no longer active.
    const selectable = products.filter(
        (product) =>
            product.status === 'active' || product.id === sale?.product.id,
    );
    const preselected =
        sale === null
            ? selectable.find((product) => product.id === initialProductId)
            : undefined;

    const form = useForm<SaleFormData>({
        product_id: sale?.product.id ?? preselected?.id ?? '',
        quantity: sale ? String(sale.quantity) : '',
        unit_price: sale?.unit_price ?? preselected?.selling_price ?? '',
        sold_on: sale?.sold_on ?? today,
        note: sale?.note ?? '',
    });

    const { errors, processing } = form;
    const errorSignature = Object.entries(errors)
        .filter(([, message]) => Boolean(message))
        .map(([name, message]) => `${name}:${message}`)
        .join('|');

    // Land in the form when it opens.
    useEffect(() => {
        productRef.current?.focus();
    }, []);

    // Move focus to the first invalid field once a submission has failed.
    useEffect(() => {
        if (processing || errorSignature === '') {
            return;
        }

        formRef.current
            ?.querySelector<HTMLElement>('[aria-invalid="true"]')
            ?.focus();
    }, [errorSignature, processing]);

    function selectProduct(value: string) {
        const product = selectable.find(
            (option) => String(option.id) === value,
        );

        if (!product) {
            return;
        }

        // The selling price pre-fills the unit price, which stays editable;
        // a product without one leaves what was typed.
        form.setData((current) => ({
            ...current,
            product_id: product.id,
            unit_price: product.selling_price ?? current.unit_price,
        }));
    }

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => onClose(),
        };

        if (sale) {
            form.put(SaleController.update.url(sale.id), options);
        } else {
            form.post(SaleController.store.url(), options);
        }
    }

    const affix = currencyAffix(currency, locale);
    const priceAffixId = `${uid}-unit-price-affix`;

    return (
        <form
            ref={formRef}
            noValidate
            onSubmit={submit}
            className="flex flex-col gap-4"
        >
            <Field
                id={`${uid}-product`}
                label="Product"
                hint="Only active products are listed."
                error={errors.product_id}
            >
                {(control) => (
                    <Select
                        value={
                            form.data.product_id === ''
                                ? ''
                                : String(form.data.product_id)
                        }
                        onValueChange={selectProduct}
                    >
                        <SelectTrigger
                            {...control}
                            ref={productRef}
                            className="w-full"
                        >
                            <SelectValue placeholder="Choose a product…" />
                        </SelectTrigger>
                        <SelectContent>
                            {selectable.map((product) => (
                                <SelectItem
                                    key={product.id}
                                    value={String(product.id)}
                                >
                                    {product.name} · {product.sku}
                                    {product.status !== 'active' &&
                                        ' (not active)'}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                )}
            </Field>

            <div className="grid grid-cols-2 gap-3">
                <Field
                    id={`${uid}-quantity`}
                    label="Quantity"
                    error={errors.quantity}
                >
                    {(control) => (
                        <Input
                            {...control}
                            type="text"
                            inputMode="numeric"
                            placeholder="0"
                            autoComplete="off"
                            value={form.data.quantity}
                            onChange={(event) =>
                                form.setData('quantity', event.target.value)
                            }
                        />
                    )}
                </Field>
                <Field
                    id={`${uid}-unit-price`}
                    label="Unit price"
                    error={errors.unit_price}
                    prefixId={priceAffixId}
                >
                    {(control) => (
                        <div className="relative">
                            <span
                                id={priceAffixId}
                                className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-muted-foreground"
                            >
                                {affix}
                            </span>
                            <Input
                                {...control}
                                type="text"
                                inputMode="decimal"
                                placeholder="0.00"
                                autoComplete="off"
                                value={form.data.unit_price}
                                style={{
                                    paddingLeft: `calc(${affix.length}ch + 1.75rem)`,
                                }}
                                onChange={(event) =>
                                    form.setData(
                                        'unit_price',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                    )}
                </Field>
            </div>

            <Field id={`${uid}-sold-on`} label="Date" error={errors.sold_on}>
                {(control) => (
                    <Input
                        {...control}
                        type="date"
                        max={today}
                        value={form.data.sold_on}
                        onChange={(event) =>
                            form.setData('sold_on', event.target.value)
                        }
                    />
                )}
            </Field>

            <Field id={`${uid}-note`} label="Note" optional error={errors.note}>
                {(control) => (
                    <Input
                        {...control}
                        type="text"
                        placeholder="Channel, customer or order number"
                        autoComplete="off"
                        value={form.data.note}
                        onChange={(event) =>
                            form.setData('note', event.target.value)
                        }
                    />
                )}
            </Field>

            <p className="flex gap-2 rounded-lg bg-neutral px-3 py-2.5 text-[0.8125rem] text-neutral-foreground">
                <Info className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                <span>
                    The total is calculated when you save. Recording a sale
                    doesn't change material stock.
                </span>
            </p>

            <div className="flex flex-wrap gap-2">
                <Button type="submit" className="flex-1" disabled={processing}>
                    {processing && <Spinner />}
                    Save sale
                </Button>
                <Button type="button" variant="outline" onClick={onClose}>
                    Cancel
                </Button>
            </div>
        </form>
    );
}
