import { Link, useForm } from '@inertiajs/react';
import { useEffect, useId, useRef, useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { BomEditor } from '@/components/products/bom-editor';
import {
    createRowUid,
    materialOptionFromLine,
} from '@/components/products/bom-rows';
import type { BomRow } from '@/components/products/bom-rows';
import { toSubmittedRow } from '@/components/products/bom-rows';
import { DecimalInput } from '@/components/products/decimal-input';
import { trimDecimal } from '@/components/products/decimals';
import { FormField } from '@/components/products/form-field';
import { PhotoField } from '@/components/shared/photo-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useAppSettings } from '@/hooks/use-app-settings';
import { useReference } from '@/hooks/use-reference';
import { unitLabel } from '@/lib/reference';
import { cn } from '@/lib/utils';
import type {
    Option,
    Product,
    ProductCategory,
    ProductFormData,
    ProductStatus,
    SizeList,
} from '@/types/domain';

/** The form's state: the submitted fields, with client-only keys on the composition rows. */
export type ProductFormState = Omit<ProductFormData, 'components'> & {
    components: BomRow[];
};

type ProductFormProps = {
    /** The product being edited; leave out to create one. */
    product?: Product;
    title: string;
    description: string;
    categories: Option<ProductCategory>[];
    statuses: Option<ProductStatus>[];
    /** The sizes every category gives its products (S-58), shown under "Category". */
    categorySizes: Record<ProductCategory, SizeList>;
    /** Where to send the form (Wayfinder in the page). */
    action: { url: string; method: 'post' | 'put' };
    cancelHref: string;
    submitLabel: string;
};

/** A new product: blank fields, a draft status (only active products can be sold) and no materials yet. */
function emptyState(): ProductFormState {
    return {
        sku: '',
        name: '',
        category: '',
        status: 'draft',
        description: '',
        labor_cost: '',
        overhead_cost: '',
        selling_price: '',
        photo: null,
        remove_photo: false,
        components: [],
    };
}

/**
 * The form's starting values. Decimals are copied as strings (trailing zeros
 * trimmed as text, never by maths); a missing price or note becomes ''.
 */
function toFormState(product: Product | undefined): ProductFormState {
    if (!product) {
        return emptyState();
    }

    return {
        sku: product.sku,
        name: product.name,
        category: product.category,
        status: product.status,
        description: product.description ?? '',
        labor_cost: trimDecimal(product.labor_cost),
        overhead_cost: trimDecimal(product.overhead_cost),
        selling_price:
            product.selling_price === null
                ? ''
                : trimDecimal(product.selling_price),
        photo: null,
        remove_photo: false,
        components: product.components.map((line) => ({
            uid: createRowUid(),
            material_id: line.material.id,
            quantity: trimDecimal(line.quantity),
            waste_percent: trimDecimal(line.waste_percent),
            notes: line.notes ?? '',
        })),
    };
}

/** "Sizes: S, M, L, XL" for a list of several sizes, "One size" for a single one. */
function sizesLine(list: SizeList): string {
    return list.sizes.length > 1 ? `Sizes: ${list.name}` : list.name;
}

/** The currency's symbol where the locale has one ("€"), otherwise its code ("TND"). */
function currencySymbol(currency: string, locale: string): string {
    const part = new Intl.NumberFormat(locale, {
        style: 'currency',
        currency,
        currencyDisplay: 'narrowSymbol',
    })
        .formatToParts(0)
        .find((piece) => piece.type === 'currency');

    return part?.value ?? currency;
}

function FormSection({
    title,
    description,
    optional = false,
    className,
    children,
}: {
    title: string;
    description?: string;
    optional?: boolean;
    className?: string;
    children: ReactNode;
}) {
    const headingId = useId();

    return (
        <section
            aria-labelledby={headingId}
            className={cn(
                'flex flex-col gap-4 rounded-xl border bg-card p-5 text-card-foreground',
                className,
            )}
        >
            <div className="space-y-0.5">
                <h2 id={headingId} className="text-base font-bold">
                    {title}
                    {optional && (
                        <span className="text-sm font-normal text-muted-foreground">
                            {' '}
                            (optional)
                        </span>
                    )}
                </h2>
                {description && (
                    <p className="text-xs text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
            {children}
        </section>
    );
}

const FIELD_GRID = 'grid gap-4 grid-cols-[repeat(auto-fit,minmax(12rem,1fr))]';

/**
 * Create or edit a product: details, costs and selling price, photo and the
 * composition. Decimals are text, so nothing is parsed or computed here; the
 * server validates and works out cost, margin and capacity.
 *
 * Submits with `useForm`: `POST` to create, `PUT` to update. A photo makes the
 * request multipart, and PHP cannot read a multipart `PUT`, so an update with
 * a file is sent as `POST` with `_method=put`.
 */
export function ProductForm({
    product,
    title,
    description,
    categories,
    statuses,
    categorySizes,
    action,
    cancelHref,
    submitLabel,
}: ProductFormProps) {
    const uid = useId();
    const { currency, locale } = useAppSettings();
    const { units } = useReference();
    const formRef = useRef<HTMLFormElement>(null);
    // Only seeds the form; computed once so composition rows keep their keys.
    const [initial] = useState(() => toFormState(product));
    const form = useForm<ProductFormState>(initial);
    // The materials of the saved lines, so the editor shows them without a request.
    const [knownMaterials] = useState(() =>
        (product?.components ?? []).map((line) => {
            const unit = units.find((row) => row.code === line.material.unit);

            return materialOptionFromLine(
                line,
                unit ? unitLabel(unit) : line.material.unit,
            );
        }),
    );
    const { errors, processing } = form;

    // `category` is '' until one is chosen; a code the server didn't send shows no line.
    const sizeList =
        form.data.category === ''
            ? undefined
            : categorySizes[form.data.category];

    const symbol = currencySymbol(currency, locale);
    const id = (name: string) => `${uid}-${name}`;

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

        const update = action.method === 'put';
        // A file needs multipart, which PHP only reads on POST.
        const spoofPut = update && form.data.photo !== null;

        form.transform((data) => ({
            ...data,
            components: data.components.map(toSubmittedRow),
            ...(spoofPut ? { _method: 'put' } : {}),
        }));

        if (update && !spoofPut) {
            form.put(action.url, {
                preserveScroll: 'errors',
            });
        } else {
            form.post(action.url, {
                preserveScroll: 'errors',
                ...(spoofPut ? { forceFormData: true } : {}),
            });
        }
    }

    const buttons = (
        <div className="flex flex-wrap items-center gap-2">
            <Button asChild variant="outline">
                <Link href={cancelHref}>Cancel</Link>
            </Button>
            <Button type="submit" disabled={processing}>
                {processing && <Spinner />}
                {submitLabel}
            </Button>
        </div>
    );

    return (
        <form
            ref={formRef}
            aria-labelledby={id('title')}
            noValidate
            className="flex flex-col gap-6"
            onSubmit={handleSubmit}
        >
            <header className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div className="min-w-0 space-y-1">
                    <h1
                        id={id('title')}
                        className="text-2xl font-semibold tracking-tight break-words"
                    >
                        {title}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>
                </div>
                {buttons}
            </header>

            <div className="@container">
                <div className="flex flex-col gap-6 @3xl:flex-row @3xl:items-start">
                    <div className="flex min-w-0 flex-1 flex-col gap-6">
                        <FormSection title="Details">
                            <div className={FIELD_GRID}>
                                <FormField
                                    id={id('sku')}
                                    label="SKU"
                                    required
                                    error={errors.sku}
                                >
                                    {(control) => (
                                        <Input
                                            {...control}
                                            value={form.data.sku}
                                            autoComplete="off"
                                            className="font-mono"
                                            onChange={(event) =>
                                                form.setData(
                                                    'sku',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    )}
                                </FormField>
                                <FormField
                                    id={id('name')}
                                    label="Name"
                                    required
                                    error={errors.name}
                                >
                                    {(control) => (
                                        <Input
                                            {...control}
                                            value={form.data.name}
                                            autoComplete="off"
                                            onChange={(event) =>
                                                form.setData(
                                                    'name',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    )}
                                </FormField>
                                <FormField
                                    id={id('category')}
                                    label="Category"
                                    required
                                    hint={
                                        sizeList && (
                                            <>
                                                <span className="block">
                                                    {sizesLine(sizeList)}
                                                </span>{' '}
                                                <span className="block">
                                                    Sizes come with the
                                                    category.
                                                </span>
                                            </>
                                        )
                                    }
                                    error={errors.category}
                                >
                                    {(control) => (
                                        <Select
                                            value={form.data.category}
                                            onValueChange={(value) =>
                                                form.setData(
                                                    'category',
                                                    value as ProductCategory,
                                                )
                                            }
                                        >
                                            <SelectTrigger
                                                {...control}
                                                className="w-full"
                                            >
                                                <SelectValue placeholder="Select a category" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {categories.map((option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    )}
                                </FormField>
                                <FormField
                                    id={id('status')}
                                    label="Status"
                                    required
                                    hint="Only active products can be sold."
                                    error={errors.status}
                                >
                                    {(control) => (
                                        <Select
                                            value={form.data.status}
                                            onValueChange={(value) =>
                                                form.setData(
                                                    'status',
                                                    value as ProductStatus,
                                                )
                                            }
                                        >
                                            <SelectTrigger
                                                {...control}
                                                className="w-full"
                                            >
                                                <SelectValue placeholder="Select a status" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {statuses.map((option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    )}
                                </FormField>
                                <FormField
                                    id={id('description')}
                                    label="Description"
                                    optional
                                    error={errors.description}
                                    className="col-span-full"
                                >
                                    {(control) => (
                                        <Textarea
                                            {...control}
                                            rows={3}
                                            value={form.data.description}
                                            onChange={(event) =>
                                                form.setData(
                                                    'description',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    )}
                                </FormField>
                            </div>
                        </FormSection>

                        <FormSection
                            title="Costs and price"
                            description="Labor and overhead are added to the materials cost to get the unit cost."
                        >
                            <div className={FIELD_GRID}>
                                <FormField
                                    id={id('labor')}
                                    label="Labor cost"
                                    required
                                    error={errors.labor_cost}
                                >
                                    {(control) => (
                                        <DecimalInput
                                            {...control}
                                            value={form.data.labor_cost}
                                            placeholder="0"
                                            addonStart={symbol}
                                            onChange={(event) =>
                                                form.setData(
                                                    'labor_cost',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    )}
                                </FormField>
                                <FormField
                                    id={id('overhead')}
                                    label="Overhead cost"
                                    optional
                                    hint="Leave empty if you don't track overhead. It counts as 0."
                                    error={errors.overhead_cost}
                                >
                                    {(control) => (
                                        <DecimalInput
                                            {...control}
                                            value={form.data.overhead_cost}
                                            placeholder="0"
                                            addonStart={symbol}
                                            onChange={(event) =>
                                                form.setData(
                                                    'overhead_cost',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    )}
                                </FormField>
                                <FormField
                                    id={id('selling-price')}
                                    label="Selling price"
                                    optional
                                    hint="Pre-fills Record a sale. The margin is calculated when you save."
                                    error={errors.selling_price}
                                >
                                    {(control) => (
                                        <DecimalInput
                                            {...control}
                                            value={form.data.selling_price}
                                            placeholder="0"
                                            addonStart={symbol}
                                            onChange={(event) =>
                                                form.setData(
                                                    'selling_price',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    )}
                                </FormField>
                            </div>
                        </FormSection>
                    </div>

                    <FormSection
                        title="Photo"
                        optional
                        className="@3xl:w-72 @3xl:shrink-0"
                    >
                        {/* The section heading names the card: keep the field's own label for screen readers only. */}
                        <div className="[&_[data-slot=photo-field]>[data-slot=label]]:sr-only">
                            <PhotoField
                                label="Photo"
                                aspect="portrait"
                                currentUrl={product?.photo_url ?? null}
                                error={errors.photo ?? errors.remove_photo}
                                onChange={(file) => form.setData('photo', file)}
                                onRemoveChange={(remove) =>
                                    form.setData('remove_photo', remove)
                                }
                            />
                        </div>
                    </FormSection>
                </div>
            </div>

            <BomEditor
                rows={form.data.components}
                knownMaterials={knownMaterials}
                errors={errors}
                onChange={(rows) => form.setData('components', rows)}
            />

            <div className="flex justify-end">{buttons}</div>
        </form>
    );
}
