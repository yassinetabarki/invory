import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent, RefObject } from 'react';
import { toast } from 'sonner';
import ProductBarcodeController from '@/actions/App/Http/Controllers/ProductBarcodeController';
import type { GenerateTarget } from '@/components/products/barcodes-tab';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import type { Colour, Product, ProductBarcodes, Size } from '@/types/domain';

type Variant = { colour: Colour; size: Size };

/** What the window says and sends, fixed when it is built (so it can't change under the person). */
type View = {
    title: string;
    text: string;
    confirm: string;
    variants: Variant[];
};

/** The toast when the barcodes asked for are no longer what is missing (client text from the card). */
const CHANGED = 'Barcodes changed. Check the list.';
const NEVER_CHANGES =
    'A barcode never changes, and its number is never given to anything else.';
const BUTTON = 'max-sm:h-11 max-sm:w-full';

const keyOf = ({ colour, size }: Variant) => `${colour.id}-${size.id}`;

/** Every variant of the product without a barcode, in every colour. */
function missingVariants(barcodes: ProductBarcodes): Variant[] {
    return barcodes.colours.flatMap(({ colour, variants }) =>
        variants
            .filter(({ barcode }) => barcode === null)
            .map(({ size }) => ({ colour, size })),
    );
}

/**
 * Whether what was sent is still what the barcodes show as missing: every
 * variant of a row's request, the very same set for the main button.
 */
function stillMissing(
    sent: Variant[],
    target: GenerateTarget,
    barcodes: ProductBarcodes,
): boolean {
    const missing = missingVariants(barcodes).map(keyOf);

    return (
        sent.every((variant) => missing.includes(keyOf(variant))) &&
        (target.kind === 'one' || sent.length === missing.length)
    );
}

/**
 * Which variants the window asks for: the row's one, or every variant of the
 * product without a barcode, in every colour. Exactly one missing is the row's
 * window. The list of names in the text is the server's `missing_names`.
 */
function viewOf(
    product: Pick<Product, 'name'>,
    barcodes: ProductBarcodes,
    oneSize: boolean,
    target: GenerateTarget,
): View | null {
    const variants: Variant[] =
        target.kind === 'one'
            ? [{ colour: target.colour, size: target.size }]
            : missingVariants(barcodes);

    if (variants.length === 0) {
        return null;
    }

    if (variants.length === 1) {
        const { colour, size } = variants[0];

        return {
            title: 'Generate a barcode?',
            text: `${product.name} in ${colour.name}${oneSize ? '' : `, size ${size.label},`} gets a barcode from your in-store numbers. ${NEVER_CHANGES}`,
            confirm: 'Generate barcode',
            variants,
        };
    }

    return {
        title: `Generate ${barcodes.missing} barcodes?`,
        text: `${product.name} gets a barcode for ${barcodes.missing_names ?? ''}, from your in-store numbers. ${NEVER_CHANGES}`,
        confirm: `Generate ${barcodes.missing} barcodes`,
        variants,
    };
}

function GenerateForm({
    product,
    live,
    hideFailure,
    onCancel,
    onAttempt,
    onRefused,
    onSuccess,
}: {
    product: Pick<Product, 'id'>;
    /** What the tab's data says now. */
    live: View | null;
    /** The barcodes changed meanwhile: the window is about to close with a toast, not this sentence. */
    hideFailure: boolean;
    onCancel: () => void;
    /** A request starts: forget an earlier refusal. */
    onAttempt: () => void;
    /** The server refused `variants`: the parent follows the reload. */
    onRefused: (asked: Variant[]) => void;
    onSuccess: () => void;
}) {
    // After success the window keeps what it showed while it fades out, although
    // the reloaded barcodes have nothing missing any more.
    const [sent, setSent] = useState<View | null>(null);
    const [failed, setFailed] = useState(false);
    const [processing, setProcessing] = useState(false);
    const view = sent ?? live;

    if (!view) {
        return null;
    }

    function generate(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (!view || processing) {
            return;
        }

        const asked = view;

        router.post(
            ProductBarcodeController.store.url(product.id),
            {
                variants: asked.variants.map(({ colour, size }) => ({
                    colour_id: colour.id,
                    size_id: size.id,
                })),
            },
            {
                // Only the barcodes come back; the rest of the page stays as it is.
                only: ['barcodes'],
                preserveScroll: true,
                preserveState: true,
                onStart: () => {
                    setProcessing(true);
                    setFailed(false);
                    onAttempt();
                },
                onSuccess: () => {
                    setSent(asked);
                    onSuccess();
                },
                // A refusal: show the sentence and reload the barcodes. When they
                // land, the parent closes the window with a toast if the numbers
                // asked for are no longer what is missing (the sentence goes then).
                onError: () => {
                    setFailed(true);
                    onRefused(asked.variants);
                },
                // A server or network failure: keep the window. Returning false also
                // stops Inertia's own error window.
                onHttpException: () => {
                    setFailed(true);

                    return false;
                },
                onNetworkError: () => {
                    setFailed(true);

                    return false;
                },
                onFinish: () => setProcessing(false),
            },
        );
    }

    return (
        <form noValidate className="grid gap-4" onSubmit={generate}>
            <DialogHeader className="pr-6">
                <DialogTitle>{view.title}</DialogTitle>
                <DialogDescription className="text-sm text-muted-foreground">
                    {view.text}
                </DialogDescription>
            </DialogHeader>

            {failed && !hideFailure && (
                <p role="alert" className="text-sm text-destructive-foreground">
                    Couldn&apos;t generate the barcodes. Try again.
                </p>
            )}

            <DialogFooter>
                <Button
                    type="button"
                    variant="outline"
                    className={BUTTON}
                    onClick={onCancel}
                >
                    Not now
                </Button>
                <Button type="submit" className={BUTTON} disabled={processing}>
                    {processing && <Spinner />}
                    {view.confirm}
                </Button>
            </DialogFooter>
        </form>
    );
}

/**
 * "Generate 6 barcodes?" (every missing variant of the product) or "Generate a
 * barcode?" (a row's, or the only one missing): it always asks first, because a
 * number is used for good. Confirming posts the variants and reloads only
 * `barcodes`; the window closes on success and the focus goes to the table of
 * barcodes (`tableRef`), the toast being the server's flash. A failed request
 * keeps the window with its message. So does a refusal, which also reloads the
 * barcodes: when they land and the numbers asked for are no longer what is
 * missing, the window closes with the toast "Barcodes changed. Check the list."
 * instead (never both). Nothing missing any more closes the window as well.
 */
export function GenerateBarcodesDialog({
    product,
    barcodes,
    oneSize,
    target,
    open,
    onOpenChange,
    tableRef,
}: {
    product: Pick<Product, 'id' | 'name'>;
    barcodes: ProductBarcodes;
    oneSize: boolean;
    target: GenerateTarget;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** Gets the focus when the barcodes were generated. */
    tableRef: RefObject<HTMLElement | null>;
}) {
    const generated = useRef(false);
    const announced = useRef(false);
    // The request the server refused, and whether the reload that followed has landed.
    const [refusal, setRefusal] = useState<{
        asked: Variant[];
        reloaded: boolean;
    } | null>(null);
    const live = viewOf(product, barcodes, oneSize, target);
    const changed =
        refusal !== null &&
        refusal.reloaded &&
        !stillMissing(refusal.asked, target, barcodes);

    useEffect(() => {
        if (changed && !announced.current) {
            announced.current = true;
            toast.info(CHANGED);
            onOpenChange(false);
        }
    }, [changed, onOpenChange]);

    // However the person closes the window, an earlier refusal no longer matters.
    const handleOpenChange = (next: boolean) => {
        if (!next) {
            setRefusal(null);
        }

        onOpenChange(next);
    };

    return (
        <Dialog open={open && live !== null} onOpenChange={handleOpenChange}>
            <DialogContent
                onCloseAutoFocus={(event) => {
                    if (generated.current) {
                        generated.current = false;
                        event.preventDefault();
                        tableRef.current?.focus();
                    }
                }}
            >
                <GenerateForm
                    product={product}
                    live={live}
                    hideFailure={changed}
                    onCancel={() => handleOpenChange(false)}
                    onAttempt={() => setRefusal(null)}
                    onRefused={(asked) => {
                        announced.current = false;
                        setRefusal({ asked, reloaded: false });
                        router.reload({
                            only: ['barcodes'],
                            onSuccess: () =>
                                setRefusal(
                                    (current) =>
                                        current && {
                                            ...current,
                                            reloaded: true,
                                        },
                                ),
                        });
                    }}
                    onSuccess={() => {
                        generated.current = true;
                        onOpenChange(false);
                    }}
                />
            </DialogContent>
        </Dialog>
    );
}
