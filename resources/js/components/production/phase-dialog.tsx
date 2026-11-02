import { useForm } from '@inertiajs/react';
import { useId } from 'react';
import type { FormEvent } from 'react';
import { FormField } from '@/components/materials/form-field';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { store, update } from '@/routes/settings/production-phases';
import type { ProductionPhase, ProductionPhaseFormData } from '@/types/domain';

type PhaseDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** The phase being renamed; null when adding. */
    phase: ProductionPhase | null;
};

function PhaseForm({
    phase,
    onOpenChange,
}: Pick<PhaseDialogProps, 'phase' | 'onOpenChange'>) {
    const uid = useId();
    const form = useForm<ProductionPhaseFormData>({ name: phase?.name ?? '' });
    const { data, errors, processing } = form;

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        form.submit(phase ? update(phase.id) : store(), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    }

    return (
        <form noValidate onSubmit={submit} className="flex flex-col gap-4">
            <FormField
                id={`${uid}-name`}
                label="Name"
                required
                hint="A short verb or noun your team uses, such as Sewing or Quality check."
                error={errors.name}
            >
                {(control) => (
                    <Input
                        {...control}
                        value={data.name}
                        autoComplete="off"
                        onChange={(event) =>
                            form.setData('name', event.target.value)
                        }
                    />
                )}
            </FormField>

            <DialogFooter>
                <Button
                    type="button"
                    variant="outline"
                    disabled={processing}
                    onClick={() => onOpenChange(false)}
                    className="max-sm:h-11"
                >
                    Cancel
                </Button>
                <Button
                    type="submit"
                    disabled={processing}
                    className="max-sm:h-11"
                >
                    {processing && <Spinner />}
                    Save phase
                </Button>
            </DialogFooter>
        </form>
    );
}

/** Add phase / Edit Sewing (INV-5, AC-1): one field, Name. */
export function PhaseDialog({ open, onOpenChange, phase }: PhaseDialogProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {phase ? `Edit ${phase.name}` : 'Add phase'}
                    </DialogTitle>
                </DialogHeader>
                {open && (
                    <PhaseForm
                        key={phase?.id ?? 'new'}
                        phase={phase}
                        onOpenChange={onOpenChange}
                    />
                )}
            </DialogContent>
        </Dialog>
    );
}
