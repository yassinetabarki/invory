import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { UndoRestockDialog } from '@/components/restocks/undo-restock-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount, formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import { undo } from '@/routes/restocks';
import { show as productShow } from '@/routes/products';
import type {
    RestockBlock,
    RestockDetail,
    RestockSummary,
} from '@/types/domain';

/** "14:32" from the server's wall-clock time "2026-10-25T14:32". */
function timeOf(value: string): string {
    return value.slice(11, 16);
}

/** "BL-0412 · recorded by Admin on 25 Oct 2026 at 14:32" (no reference: "Recorded by …"). */
export function recordedLine(restock: RestockDetail, locale: string): string {
    const recorded = `recorded by ${restock.recorded_by ?? '—'} on ${formatDate(restock.recorded_at, locale)} at ${timeOf(restock.recorded_at)}`;

    return restock.reference
        ? `${restock.reference} · ${recorded}`
        : recorded.charAt(0).toUpperCase() + recorded.slice(1);
}

function Count({ value }: { value: number }) {
    const { locale } = useAppSettings();

    return (
        <span className={cn(value === 0 && 'text-muted-foreground')}>
            {formatCount(value, locale)}
        </span>
    );
}

/** One product of the restock: its name (a link, unless deleted), its SKU and what each colour got per size. */
function Block({ block }: { block: RestockBlock }) {
    const caption = `Units added · ${block.product.name}`;

    return (
        <section
            aria-label={block.product.name}
            className="flex flex-col gap-2"
        >
            <div className="flex flex-wrap items-baseline gap-x-2">
                {block.product.deleted ? (
                    <span className="font-semibold">
                        {block.product.name} (deleted)
                    </span>
                ) : (
                    <Link
                        href={productShow(block.product.id)}
                        className="font-semibold hover:underline focus-visible:underline"
                    >
                        {block.product.name}
                    </Link>
                )}
                <span className="font-mono text-xs text-muted-foreground">
                    {block.product.sku}
                </span>
            </div>
            <div className="overflow-x-auto rounded-lg border">
                <Table>
                    <TableCaption className="sr-only">{caption}</TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Colour</TableHead>
                            {block.sizes.map((size) => (
                                <TableHead key={size.id} className="text-right">
                                    {size.label}
                                </TableHead>
                            ))}
                            <TableHead className="text-right">Total</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {block.rows.map((row) => (
                            <TableRow key={row.colour.id}>
                                <TableCell>
                                    <span className="flex items-center gap-2">
                                        <span
                                            aria-hidden="true"
                                            className="size-3 shrink-0 rounded-full border"
                                            style={{
                                                backgroundColor:
                                                    row.colour.swatch,
                                            }}
                                        />
                                        {row.colour.name}
                                    </span>
                                </TableCell>
                                {row.units.map((units, index) => (
                                    <TableCell
                                        key={block.sizes[index]?.id ?? index}
                                        className="text-right font-mono tabular-nums"
                                    >
                                        <Count value={units} />
                                    </TableCell>
                                ))}
                                <TableCell className="text-right font-mono font-semibold tabular-nums">
                                    <Count value={row.total} />
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                    {block.totals && (
                        <TableFooter>
                            <TableRow>
                                <TableCell>Total</TableCell>
                                {block.totals.map((units, index) => (
                                    <TableCell
                                        key={block.sizes[index]?.id ?? index}
                                        className="text-right font-mono tabular-nums"
                                    >
                                        <Count value={units} />
                                    </TableCell>
                                ))}
                                <TableCell className="text-right font-mono font-semibold tabular-nums">
                                    <Count value={block.units} />
                                </TableCell>
                            </TableRow>
                        </TableFooter>
                    )}
                </Table>
            </div>
        </section>
    );
}

type RestockPanelProps = {
    /** The restock to show; null while it loads or when it couldn't load. */
    restock: RestockDetail | null;
    /** The list's row that was opened: its date and figures show at once while the panel loads. */
    pending: RestockSummary | null;
    open: boolean;
    failed: boolean;
    onClose: () => void;
    onRetry: () => void;
};

/**
 * A restock's panel (INV-3, AC-5 and AC-6): on the right on a laptop, about
 * 560 px wide, so the list stays in view; the whole screen on a phone. Its
 * products, what each colour got per size, and **Undo restock**, which asks
 * first and can't be pressed while the shelf holds fewer units than it added
 * (the reason shows above it and is part of its name).
 */
export function RestockPanel({
    restock,
    pending,
    open,
    failed,
    onClose,
    onRetry,
}: RestockPanelProps) {
    const { locale } = useAppSettings();
    const [confirming, setConfirming] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [refusal, setRefusal] = useState<string | null>(null);

    const summary = restock ?? pending;
    const products = restock?.products_count ?? summary?.products.length ?? 0;

    function confirmUndo() {
        if (!restock) {
            return;
        }

        router.post(
            undo.url(restock.id),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onStart: () => {
                    setProcessing(true);
                    setRefusal(null);
                },
                onFinish: () => setProcessing(false),
                onSuccess: () => setConfirming(false),
                onError: (errors) => setRefusal(errors.undo ?? null),
            },
        );
    }

    let body;

    if (failed) {
        body = (
            <div
                role="alert"
                className="flex flex-col items-center gap-3 p-8 text-center text-sm"
            >
                <p>Couldn&apos;t load this restock.</p>
                <Button type="button" variant="outline" onClick={onRetry}>
                    Try again
                </Button>
            </div>
        );
    } else if (!restock) {
        body = (
            <div
                aria-busy="true"
                aria-label="Loading restock"
                className="flex flex-col gap-3"
            >
                <Skeleton className="h-5 w-40" />
                <Skeleton className="h-24 w-full" />
                <Skeleton className="h-24 w-full" />
            </div>
        );
    } else {
        body = (
            <>
                {restock.note && (
                    <p className="rounded-lg bg-muted p-3 text-sm">
                        {restock.note}
                    </p>
                )}
                {restock.blocks.map((block) => (
                    <Block key={block.product.id} block={block} />
                ))}
            </>
        );
    }

    const canUndo =
        restock !== null && !restock.undone && restock.undo_refusal === null;

    return (
        <Sheet open={open} onOpenChange={(next) => !next && onClose()}>
            <SheetContent
                side="right"
                className="w-full gap-0 sm:max-w-[560px]"
            >
                <SheetHeader className="border-b">
                    <div className="flex flex-wrap items-center gap-2 pr-8">
                        <SheetTitle>
                            {summary
                                ? `Restock · ${formatDate(summary.restocked_on, locale)}`
                                : 'Restock'}
                        </SheetTitle>
                        {summary?.undone && (
                            <Badge
                                variant="outline"
                                className="rounded-full border-transparent bg-neutral font-semibold text-neutral-foreground"
                            >
                                Undone
                            </Badge>
                        )}
                    </div>
                    <SheetDescription>
                        {restock
                            ? recordedLine(restock, locale)
                            : (summary?.reference ?? '')}
                    </SheetDescription>
                    {summary && (
                        <p className="text-sm">
                            <span className="font-semibold">
                                {formatCount(summary.units, locale)}{' '}
                                {summary.units === 1 ? 'unit' : 'units'}
                            </span>{' '}
                            · {formatCount(products, locale)}{' '}
                            {products === 1 ? 'product' : 'products'}
                        </p>
                    )}
                    {restock?.undone && (
                        <p className="text-sm text-muted-foreground">
                            Undone by {restock.undone_by ?? '—'} on{' '}
                            {formatDate(restock.undone_at, locale)}. Its{' '}
                            {formatCount(restock.units, locale)}{' '}
                            {restock.units === 1 ? 'unit was' : 'units were'}{' '}
                            taken back off the shelf.
                        </p>
                    )}
                </SheetHeader>

                <div className="flex min-h-0 flex-1 flex-col gap-5 overflow-y-auto p-4">
                    {body}
                </div>

                <SheetFooter className="border-t">
                    {restock && !restock.undone && restock.undo_refusal && (
                        <p
                            id="undo-refusal"
                            className="text-sm text-muted-foreground"
                        >
                            {restock.undo_refusal}
                        </p>
                    )}
                    <div className="flex flex-col gap-2 sm:flex-row sm:justify-end">
                        {restock && !restock.undone && (
                            <Button
                                type="button"
                                variant="outline"
                                className="text-destructive hover:text-destructive aria-disabled:cursor-not-allowed aria-disabled:opacity-50 max-sm:h-11 max-sm:w-full"
                                aria-disabled={!canUndo || undefined}
                                aria-label={
                                    restock.undo_refusal
                                        ? `Undo restock. ${restock.undo_refusal}`
                                        : undefined
                                }
                                onClick={() => {
                                    if (canUndo) {
                                        setRefusal(null);
                                        setConfirming(true);
                                    }
                                }}
                            >
                                Undo restock
                            </Button>
                        )}
                        <Button
                            type="button"
                            variant="outline"
                            className="max-sm:h-11 max-sm:w-full"
                            onClick={onClose}
                        >
                            Close
                        </Button>
                    </div>
                </SheetFooter>

                {restock && (
                    <UndoRestockDialog
                        restock={restock}
                        open={confirming}
                        onOpenChange={(next) =>
                            !processing && setConfirming(next)
                        }
                        processing={processing}
                        error={refusal}
                        onConfirm={confirmUndo}
                    />
                )}
            </SheetContent>
        </Sheet>
    );
}
