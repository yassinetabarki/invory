import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import type { ReactNode } from 'react';
import { useRef, useState } from 'react';
import MaterialController from '@/actions/App/Http/Controllers/MaterialController';
import { StockBadge } from '@/components/materials/stock-badge';
import { ConfirmDeleteDialog } from '@/components/shared/confirm-delete-dialog';
import { Money } from '@/components/shared/money';
import { PageHeader } from '@/components/shared/page-header';
import { Quantity } from '@/components/shared/quantity';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { EMPTY_VALUE } from '@/lib/format';
import type { MaterialShowProps } from '@/types/domain';

function Detail({
    label,
    children,
    className,
}: {
    label: string;
    children: ReactNode;
    className?: string;
}) {
    return (
        <div className={className}>
            <dt className="text-sm text-muted-foreground">{label}</dt>
            <dd className="mt-1 font-medium break-words">{children}</dd>
        </div>
    );
}

/** Optional text: the value, or an em dash. */
function orDash(value: string | null): string {
    return value === null || value.trim() === '' ? EMPTY_VALUE : value;
}

export default function MaterialsShow({ material }: MaterialShowProps) {
    const [confirming, setConfirming] = useState(false);
    const [processing, setProcessing] = useState(false);
    const deleteButtonRef = useRef<HTMLButtonElement>(null);

    function destroy() {
        router.delete(MaterialController.destroy.url(material), {
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setConfirming(false);
            },
        });
    }

    return (
        <>
            <Head title={material.name} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={material.name}
                    description={`${material.sku} · ${material.category_label}`}
                    actions={
                        <>
                            <Button asChild variant="outline">
                                <Link href={MaterialController.edit(material)}>
                                    <Pencil aria-hidden="true" />
                                    Edit
                                </Link>
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                ref={deleteButtonRef}
                                className="text-destructive-foreground hover:text-destructive-foreground"
                                onClick={() => setConfirming(true)}
                            >
                                <Trash2 aria-hidden="true" />
                                Delete
                            </Button>
                        </>
                    }
                />

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle role="heading" aria-level={2}>
                                Stock
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-4 sm:grid-cols-2">
                                <Detail label="Quantity on hand">
                                    <Quantity
                                        value={material.quantity_on_hand}
                                        unit={material.unit}
                                    />
                                </Detail>
                                <Detail label="Status">
                                    <StockBadge
                                        status={material.stock_status}
                                    />
                                </Detail>
                                <Detail label="Reorder level">
                                    {material.reorder_level === null ? (
                                        'Not set'
                                    ) : (
                                        <Quantity
                                            value={material.reorder_level}
                                            unit={material.unit}
                                        />
                                    )}
                                </Detail>
                                <Detail label="Unit cost">
                                    <Money value={material.unit_cost} precise />
                                    <span className="font-normal text-muted-foreground">
                                        {' '}
                                        per {material.unit_label}
                                    </span>
                                </Detail>
                                <Detail label="Stock value">
                                    <Money value={material.stock_value} />
                                </Detail>
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle role="heading" aria-level={2}>
                                Details
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-4 sm:grid-cols-2">
                                <Detail label="Supplier">
                                    {orDash(material.supplier_name)}
                                </Detail>
                                <Detail label="Supplier reference">
                                    {orDash(material.supplier_reference)}
                                </Detail>
                                <Detail label="Colour">
                                    {orDash(material.color)}
                                </Detail>
                                <Detail label="Composition">
                                    {orDash(material.composition)}
                                </Detail>
                                <Detail label="Notes" className="sm:col-span-2">
                                    <span className="whitespace-pre-line">
                                        {orDash(material.notes)}
                                    </span>
                                </Detail>
                            </dl>
                        </CardContent>
                    </Card>
                </div>

                {/* "Used in" products (S-07) will go here. */}
            </div>

            <ConfirmDeleteDialog
                open={confirming}
                onOpenChange={(open) => {
                    if (!open && !processing) {
                        setConfirming(false);
                    }
                }}
                title="Delete material?"
                description={
                    <p>
                        <strong>{material.name}</strong> (SKU {material.sku})
                        will be removed from your inventory. This cannot be
                        undone.
                    </p>
                }
                confirmLabel="Delete material"
                processing={processing}
                returnFocusTo={deleteButtonRef}
                onConfirm={destroy}
            />
        </>
    );
}

MaterialsShow.layout = (props: MaterialShowProps) => ({
    breadcrumbs: [
        {
            title: 'Materials',
            href: MaterialController.index(),
        },
        {
            title: props.material.name,
            href: MaterialController.show(props.material),
        },
    ],
});
