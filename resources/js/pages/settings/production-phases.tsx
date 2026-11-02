import { router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Lock, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { PhaseDialog } from '@/components/production/phase-dialog';
import { ReasonedButton } from '@/components/production/reasoned-button';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import { destroy, index, position } from '@/routes/settings/production-phases';
import type { ProductionPhase, ProductionPhasesProps } from '@/types/domain';

type DialogTarget = { open: boolean; phase: ProductionPhase | null };

/** "Holds 15 units" / "Holds 1 unit" / "Empty". */
function holds(phase: ProductionPhase, locale: string): string {
    if (phase.units === 0) {
        return 'Empty';
    }

    return `Holds ${formatCount(phase.units, locale)} ${phase.units === 1 ? 'unit' : 'units'}`;
}

/** What "Remove Quality check?" says the path becomes. */
function removalText(
    phase: ProductionPhase,
    phases: ProductionPhase[],
): string {
    const i = phases.findIndex((candidate) => candidate.id === phase.id);
    const before = i > 0 ? phases[i - 1].name : 'In cutting';
    const after = i < phases.length - 1 ? phases[i + 1].name : 'Put in stock';

    return `${phase.name} is empty. It leaves Progress; batches then go from ${before} to ${after}.`;
}

function FixedStep({ name, note }: { name: string; note: string }) {
    return (
        <li className="flex items-start gap-3 rounded-lg border border-dashed bg-muted/40 p-3 text-muted-foreground">
            <Lock className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
            <div className="min-w-0">
                <p className="font-medium">{name}</p>
                <p className="text-sm">{note}</p>
            </div>
        </li>
    );
}

/** Settings › Production phases (INV-5, AC-1). */
export default function ProductionPhases({ phases }: ProductionPhasesProps) {
    const { locale } = useAppSettings();
    const [dialog, setDialog] = useState<DialogTarget>({
        open: false,
        phase: null,
    });
    const [removing, setRemoving] = useState<ProductionPhase | null>(null);
    const [processing, setProcessing] = useState(false);
    const [refusal, setRefusal] = useState<string | null>(null);
    const [moveError, setMoveError] = useState<string | null>(null);

    function move(phase: ProductionPhase, direction: 'up' | 'down') {
        setMoveError(null);
        router.patch(
            position(phase.id).url,
            { direction },
            {
                preserveScroll: true,
                preserveState: true,
                onError: (errors) => setMoveError(errors.phase ?? null),
            },
        );
    }

    function remove() {
        if (!removing) {
            return;
        }

        setProcessing(true);
        router.delete(destroy(removing.id).url, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => setRemoving(null),
            onError: (errors) => setRefusal(errors.phase ?? null),
            onFinish: () => setProcessing(false),
        });
    }

    /** A phase that holds units keeps its place: neither it nor its neighbour can swap with it. */
    function moveReason(i: number, direction: 'up' | 'down'): string | null {
        const neighbour = phases[direction === 'up' ? i - 1 : i + 1];

        return phases[i].refusal ?? neighbour?.refusal ?? null;
    }

    return (
        <>
            <h1 className="sr-only">Production phases settings</h1>

            <div className="space-y-6">
                <div className="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                    <Heading
                        variant="small"
                        title="Production phases"
                        description="The phases a batch goes through between cutting and putting it in stock. In cutting and Put in stock are always there."
                    />
                    <div className="shrink-0">
                        <Button
                            type="button"
                            className="w-full max-sm:h-11 xl:w-auto"
                            onClick={() =>
                                setDialog({ open: true, phase: null })
                            }
                        >
                            <Plus aria-hidden="true" />
                            Add phase
                        </Button>
                    </div>
                </div>

                {moveError && (
                    <p role="alert" className="text-sm text-destructive">
                        {moveError}
                    </p>
                )}

                <ol
                    className="flex flex-col gap-2"
                    aria-label="The path of a batch"
                >
                    <FixedStep
                        name="In cutting"
                        note="Always there. Start batch puts a batch here and takes the materials."
                    />

                    {phases.length === 0 && (
                        <li className="rounded-lg border p-4 text-sm">
                            <p>
                                No phase of your own. Batches go from In cutting
                                straight to Put in stock.
                            </p>
                        </li>
                    )}

                    {phases.map((phase, i) => (
                        <li
                            key={phase.id}
                            className="flex flex-col gap-3 rounded-lg border p-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div className="min-w-0">
                                <p className="font-medium">{phase.name}</p>
                                <p className="text-sm text-muted-foreground">
                                    {holds(phase, locale)}
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-1">
                                <ReasonedButton
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="size-11 sm:size-9"
                                    aria-label="Move up"
                                    reason={
                                        i === 0 ? null : moveReason(i, 'up')
                                    }
                                    disabled={i === 0}
                                    onClick={() => move(phase, 'up')}
                                >
                                    <ArrowUp aria-hidden="true" />
                                </ReasonedButton>
                                <ReasonedButton
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="size-11 sm:size-9"
                                    aria-label="Move down"
                                    reason={
                                        i === phases.length - 1
                                            ? null
                                            : moveReason(i, 'down')
                                    }
                                    disabled={i === phases.length - 1}
                                    onClick={() => move(phase, 'down')}
                                >
                                    <ArrowDown aria-hidden="true" />
                                </ReasonedButton>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="size-11 sm:size-9"
                                    aria-label={`Edit ${phase.name}`}
                                    onClick={() =>
                                        setDialog({ open: true, phase })
                                    }
                                >
                                    <Pencil aria-hidden="true" />
                                </Button>
                                <ReasonedButton
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="size-11 text-destructive hover:text-destructive sm:size-9"
                                    aria-label={`Remove ${phase.name}`}
                                    reason={phase.refusal}
                                    onClick={() => {
                                        setRefusal(null);
                                        setRemoving(phase);
                                    }}
                                >
                                    <Trash2 aria-hidden="true" />
                                </ReasonedButton>
                            </div>
                        </li>
                    ))}

                    <FixedStep
                        name="Put in stock"
                        note="Always there. Ends a batch: its units are added to your finished stock."
                    />
                </ol>
            </div>

            <PhaseDialog
                open={dialog.open}
                phase={dialog.phase}
                onOpenChange={(open) => {
                    if (!open) {
                        setDialog((current) => ({ ...current, open: false }));
                    }
                }}
            />

            <AlertDialog
                open={removing !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setRemoving(null);
                    }
                }}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            Remove {removing?.name}?
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            {removing && removalText(removing, phases)}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    {refusal && (
                        <p role="alert" className="text-sm text-destructive">
                            {refusal}
                        </p>
                    )}
                    <AlertDialogFooter>
                        <AlertDialogCancel disabled={processing}>
                            Keep phase
                        </AlertDialogCancel>
                        <AlertDialogAction
                            variant="destructive"
                            disabled={processing}
                            onClick={(event) => {
                                event.preventDefault();
                                remove();
                            }}
                        >
                            {processing && <Spinner />}
                            Remove phase
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}

ProductionPhases.layout = {
    breadcrumbs: [
        {
            title: 'Production phases',
            href: index(),
        },
    ],
};
