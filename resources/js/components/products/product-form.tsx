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
import { ColoursField } from '@/components/products/colours-field';
import { DecimalInput } from '@/components/products/decimal-input';
import { trimDecimal } from '@/components/products/decimals';
import { FormField } from '@/components/products/form-field';
import { SizesField } from '@/components/products/sizes-field';
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
    Colour,
    Option,
    Product,
    ProductCategory,
    ProductFormData,
    ProductStatus,
    SizeOption,
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
    /** The workshop's whole colour list, in list order (INV-1). */
    colours: Colour[];
    /** The workshop's whole size list, in list order (INV-18). */
    sizes: SizeOption[];
    /** The size ids each category suggests: Add product pre-selects them. */
    categorySizes: Record<ProductCategory, number[]>;
    /** Edit product: size id → why it can't be taken off. */
    sizeLocks?: Record<number, string>;
    /** Edit product: why One size can't be chosen, or null. */
    oneSizeLock?: string | null;
    /** Edit product: why no other size can be chosen, or null. */
    otherSizesLock?: string | null;
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
        colours: [],
        sizes: [],
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
        colours: product.colours.map((colour) => colour.id),
        sizes: product.sizes.map((size) => size.id),
        components: product.components.map((line) => ({
            uid: createRowUid(),
            material_id: line.material.id,
            quantity: trimDecimal(line.quantity),
            waste_percent: trimDecimal(line.waste_percent),
            notes: line.notes ?? '',
        })),
    };
}

/** The error keys of the colours: "colours", or "colours.N" for a wrong id. */
function isColoursKey(name: string): name is 'colours' | `colours.${number}` {
    return name === 'colours' || /^colours\.\d+$/.test(name);
}

/** "colours.removed.7": a colour taken off that still has stock (INV-2); the id of that colour. */
function removedColourId(name: string): number | null {
    const match = /^colours\.removed\.(\d+)$/.exec(name);

    return match ? Number(match[1]) : null;
}

/** The error keys of the sizes: "sizes", or "sizes.N" for a wrong id. */
function isSizesKey(name: string): name is 'sizes' | `sizes.${number}` {
    return name === 'sizes' || /^sizes\.\d+$/.test(name);
}

/** "sizes.removed.7": a size taken off that is in use; the id of that size. */
function removedSizeId(name: string): number | null {
    const match = /^sizes\.removed\.(\d+)$/.exec(name);

    return match ? Number(match[1]) : null;
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
    colours,
    sizes,
    categorySizes,
    sizeLocks,
    oneSizeLock,
    otherSizesLock,
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
    // Add product: she has pressed a size herself (a category then keeps them), and the
    // category whose suggestion the "Use the sizes of …" button applies.
    const [sizesTouched, setSizesTouched] = useState(false);
    const [keptCategory, setKeptCategory] = useState<ProductCategory | null>(
        null,
    );

    const symbol = currencySymbol(currency, locale);
    const id = (name: string) => `${uid}-${name}`;

    const errorSignature = Object.entries(errors)
        .filter(([, message]) => Boolean(message))
        .map(([name, message]) => `${name}:${message}`)
        .join('|');

    // The colours taken off that still have stock: one sentence each, in the
    // order of the colours list (never the order the keys arrived in).
    const removedErrors = colours.flatMap((colour) => {
        const message = (errors as Record<string, string | undefined>)[
            `colours.removed.${colour.id}`
        ];

        return message ? [message] : [];
    });
    // "Choose at least one colour." arrives on `colours`; a wrong id on `colours.N`.
    // One sentence each, the lock sentences after it.
    const coloursError = [
        errors.colours ??
            Object.entries(errors).find(([name]) =>
                /^colours\.\d+$/.test(name),
            )?.[1],
        ...removedErrors,
    ].filter((sentence): sentence is string => Boolean(sentence));

    // The sizes taken off that are in use: one sentence each, in list order, after
    // "Choose at least one size." (or the One size sentence), which arrives on `sizes`.
    const sizesError = [
        errors.sizes ??
            Object.entries(errors).find(([name]) =>
                /^sizes\.\d+$/.test(name),
            )?.[1],
        ...sizes.flatMap((size) => {
            const message = (errors as Record<string, string | undefined>)[
                `sizes.removed.${size.id}`
            ];

            return message ? [message] : [];
        }),
    ].filter((sentence): sentence is string => Boolean(sentence));

    /** The sizes a category suggests, in list order. */
    function suggestedSizes(category: ProductCategory): number[] {
        const ids = categorySizes[category] ?? [];

        return sizes.filter((size) => ids.includes(size.id)).map((s) => s.id);
    }

    const keptSuggestion = keptCategory ? suggestedSizes(keptCategory) : [];
    const sizesKept =
        keptCategory !== null &&
        (keptSuggestion.length !== form.data.sizes.length ||
            keptSuggestion.some((sizeId) => !form.data.sizes.includes(sizeId)));

    function chooseCategory(category: ProductCategory) {
        form.setData('category', category);

        // Edit product never changes the sizes with the category.
        if (product) {
            return;
        }

        if (sizesTouched) {
            setKeptCategory(category);

            return;
        }

        form.setData('sizes', suggestedSizes(category));
        form.clearErrors(...Object.keys(errors).filter(isSizesKey));
    }

    // "Manage sizes & colours" opens this page at #colours (the focus goes to the
    // Colours field's Add colour button) or #sizes (to the first size button), once,
    // when the form appears.
    useEffect(() => {
        if (window.location.hash === '#colours') {
            formRef.current
                ?.querySelector<HTMLElement>(
                    `[aria-describedby~="${uid}-colours-hint"]`,
                )
                ?.focus();
        } else if (window.location.hash === '#sizes') {
            formRef.current
                ?.querySelector<HTMLElement>('#sizes [aria-pressed]')
                ?.focus();
        }
    }, [uid]);

    // Move focus to the first invalid field once a submission has failed
    // (for the colours, that is the Add colour button, marked `data-invalid`).
    useEffect(() => {
        if (processing || errorSignature === '') {
            return;
        }

        formRef.current
            ?.querySelector<HTMLElement>(
                '[aria-invalid="true"], [data-invalid="true"]',
            )
            ?.focus();
    }, [errorSignature, processing]);

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        // Every sentence of the last attempt goes; the server says again what still holds.
        form.clearErrors();

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
                                    error={errors.category}
                                >
                                    {(control) => (
                                        <>
                                            <Select
                                                value={form.data.category}
                                                onValueChange={(value) =>
                                                    chooseCategory(
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
                                                    {categories.map(
                                                        (option) => (
                                                            <SelectItem
                                                                key={
                                                                    option.value
                                                                }
                                                                value={
                                                                    option.value
                                                                }
                                                            >
                                                                {option.label}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                        </>
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
                                <SizesField
                                    id={id('sizes')}
                                    sizes={sizes}
                                    value={form.data.sizes}
                                    hint={
                                        product
                                            ? 'A new size adds a variant for every colour, with no stock and no barcode yet.'
                                            : 'Choosing a category pre-selects its sizes. Change them as you like.'
                                    }
                                    locks={sizeLocks}
                                    oneSizeLock={oneSizeLock}
                                    otherSizesLock={otherSizesLock}
                                    error={sizesError}
                                    className="col-span-full"
                                    kept={
                                        sizesKept && keptCategory
                                            ? {
                                                  categoryName:
                                                      categories.find(
                                                          (option) =>
                                                              option.value ===
                                                              keptCategory,
                                                      )?.label ?? keptCategory,
                                                  onUse: () => {
                                                      form.setData(
                                                          'sizes',
                                                          keptSuggestion,
                                                      );
                                                      setSizesTouched(false);
                                                      setKeptCategory(null);
                                                  },
                                              }
                                            : null
                                    }
                                    onChange={(ids) => {
                                        form.setData('sizes', ids);
                                        setSizesTouched(true);

                                        // "Choose at least one size." (and a wrong id) are answered
                                        // as soon as there is a size; a lock sentence goes when its
                                        // own size is back. Never call clearErrors() with no names.
                                        const stale =
                                            ids.length > 0
                                                ? Object.keys(errors).filter(
                                                      isSizesKey,
                                                  )
                                                : [];
                                        const back = Object.keys(errors).filter(
                                            (name) => {
                                                const removed =
                                                    removedSizeId(name);

                                                return (
                                                    removed !== null &&
                                                    ids.includes(removed)
                                                );
                                            },
                                        ) as (keyof typeof errors)[];

                                        if (stale.length + back.length > 0) {
                                            form.clearErrors(...stale, ...back);
                                        }
                                    }}
                                />
                                <ColoursField
                                    id={id('colours')}
                                    colours={colours}
                                    value={form.data.colours}
                                    error={coloursError}
                                    className="col-span-full"
                                    onChange={(ids) => {
                                        form.setData('colours', ids);

                                        // "Choose at least one colour." (and a wrong id) are
                                        // answered as soon as there is a colour. Never call
                                        // clearErrors() with no names: that clears every error.
                                        const stale =
                                            ids.length > 0
                                                ? Object.keys(errors).filter(
                                                      isColoursKey,
                                                  )
                                                : [];
                                        // A lock sentence goes only when its own colour is back.
                                        const back = Object.keys(errors).filter(
                                            (name) => {
                                                const id =
                                                    removedColourId(name);

                                                return (
                                                    id !== null &&
                                                    ids.includes(id)
                                                );
                                            },
                                        ) as (keyof typeof errors)[];

                                        if (stale.length + back.length > 0) {
                                            form.clearErrors(...stale, ...back);
                                        }
                                    }}
                                />
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
