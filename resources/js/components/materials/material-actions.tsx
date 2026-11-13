import { Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useId, useRef, useState } from 'react';
import MaterialController from '@/actions/App/Http/Controllers/MaterialController';
import { RestockDialog } from '@/components/materials/restock-dialog';
import { ConfirmDeleteDialog } from '@/components/shared/confirm-delete-dialog';
import { Button } from '@/components/ui/button';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import type { Material } from '@/types/domain';

type MaterialActionsProps = {
    material: Material;
    /**
     * Opens the Restock sheet of a per-colour material (the page owns it).
     * Without it Restock opens the quick form in a dialog.
     */
    onRestockColours?: () => void;
};

/**
 * Restock (opens the quick form in a dialog, or the page's sheet for a
 * per-colour material), Edit and Delete. A material that
 * products use cannot be deleted: the button is disabled and the reason sits
 * next to it, linked with `aria-describedby`.
 */
export function MaterialActions({
    material,
    onRestockColours,
}: MaterialActionsProps) {
    const reasonId = useId();
    const { locale } = useAppSettings();
    const [restocking, setRestocking] = useState(false);
    const [confirming, setConfirming] = useState(false);
    const [processing, setProcessing] = useState(false);
    const restockButtonRef = useRef<HTMLButtonElement>(null);
    const deleteButtonRef = useRef<HTMLButtonElement>(null);

    const inUse = material.used_in_count > 0;

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
            <div className="flex flex-wrap items-center gap-2 sm:justify-end">
                <Button
                    type="button"
                    ref={restockButtonRef}
                    onClick={() =>
                        onRestockColours
                            ? onRestockColours()
                            : setRestocking(true)
                    }
                >
                    <Plus aria-hidden="true" />
                    Restock
                </Button>
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
                    disabled={inUse}
                    aria-describedby={inUse ? reasonId : undefined}
                    className="text-destructive-foreground hover:text-destructive-foreground"
                    onClick={() => setConfirming(true)}
                >
                    <Trash2 aria-hidden="true" />
                    Delete
                </Button>
                {inUse && (
                    <p
                        id={reasonId}
                        className="basis-full text-xs text-muted-foreground sm:text-right"
                    >
                        Used in {formatCount(material.used_in_count, locale)}{' '}
                        {material.used_in_count === 1 ? 'product' : 'products'},
                        so it can&apos;t be deleted.
                    </p>
                )}
            </div>

            {!onRestockColours && (
                <RestockDialog
                    material={material}
                    open={restocking}
                    onOpenChange={setRestocking}
                    returnFocusTo={restockButtonRef}
                />
            )}

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
