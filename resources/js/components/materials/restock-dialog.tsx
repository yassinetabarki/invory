import type { RefObject } from 'react';
import {
    RestockForm,
    RestockSummary,
} from '@/components/materials/restock-form';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Material } from '@/types/domain';

type RestockDialogProps = {
    material: Material;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** The button that opened the dialog: gets focus back when it closes. */
    returnFocusTo?: RefObject<HTMLElement | null>;
};

/** The restock quick form in a dialog (the material's detail page). */
export function RestockDialog({
    material,
    open,
    onOpenChange,
    returnFocusTo,
}: RestockDialogProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent
                className="sm:max-w-xl"
                onCloseAutoFocus={(event) => {
                    if (returnFocusTo?.current) {
                        event.preventDefault();
                        returnFocusTo.current.focus();
                    }
                }}
            >
                <DialogHeader>
                    <DialogTitle>Restock {material.name}</DialogTitle>
                    <DialogDescription>
                        <RestockSummary material={material} />
                    </DialogDescription>
                </DialogHeader>
                <RestockForm
                    material={material}
                    onCancel={() => onOpenChange(false)}
                    onSuccess={() => onOpenChange(false)}
                />
            </DialogContent>
        </Dialog>
    );
}
