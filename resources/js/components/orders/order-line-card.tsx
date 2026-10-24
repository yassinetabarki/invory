import { Link } from '@inertiajs/react';
import { useId } from 'react';
import { MoveButton } from '@/components/orders/move-button';
import { lineHeadingId, MOVES } from '@/components/orders/move-units-dialog';
import { ProgressStatus } from '@/components/orders/progress-status';
import { SizeStagesTable } from '@/components/orders/size-stages-table';
import {
    formatUnits,
    STAGE_BOXES,
    StageFigure,
} from '@/components/orders/stage-figure';
import { SizeChips } from '@/components/shared/size-chips';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import { orderLineSizeCounts } from '@/lib/sizes';
import { show } from '@/routes/products';
import type { OrderLine, OrderLineMove, OrderStage } from '@/types/domain';

type OrderLineCardProps = {
    line: OrderLine;
    /** The page opens the move window; this component never moves units. */
    onMove: (line: OrderLine, move: OrderLineMove) => void;
};

type MoveSpec = [OrderLineMove, string];

type StageSpec = {
    stage: OrderStage;
    caption: string;
    /** The button that moves units on from this stage, as [move, label]. */
    forward?: MoveSpec;
    /** The button that moves them back, as [move, label]. */
    back?: MoveSpec;
};

// Fixed UI copy (the props carry no stage labels).
const STAGES: StageSpec[] = [
    {
        stage: 'to_start',
        caption: 'To start',
        forward: ['start_cutting', 'Start cutting'],
    },
    {
        stage: 'in_cutting',
        caption: 'In cutting',
        forward: ['mark_ready', 'Mark ready'],
        back: ['back_to_start', 'Move back'],
    },
    {
        stage: 'ready_to_ship',
        caption: 'Ready to ship',
        forward: ['ship', 'Ship'],
        back: ['back_to_cutting', 'Move back'],
    },
    {
        stage: 'shipped',
        caption: 'Shipped',
        back: ['back_to_ready', 'Move back'],
    },
];

const figuresOf = (figures: Record<OrderStage, number>) =>
    Object.fromEntries(STAGES.map(({ stage }) => [stage, figures[stage]]));

/** One product of the order: its four stage figures, the buttons that move units, and its status. */
export function OrderLineCard({ line, onMove }: OrderLineCardProps) {
    const uid = useId();
    const { locale } = useAppSettings();
    const { product } = line;
    // One move button. A "Move back" is described by its stage's caption (`describedBy`).
    const moveButton = ([move, label]: MoveSpec, describedBy?: string) => {
        // A deleted product's units still move on and back, but never into cutting.
        const cutBlocked = move === 'start_cutting' && product.deleted;

        return (
            <MoveButton
                type="button"
                size="sm"
                variant={move.startsWith('back_') ? 'outline' : 'default'}
                disabled={line[MOVES[move].from] === 0 || cutBlocked}
                aria-describedby={cutBlocked ? `${uid}-deleted` : describedBy}
                onClick={() => onMove(line, move)}
            >
                {label}
            </MoveButton>
        );
    };
    const forwardOf = ({ forward }: StageSpec) =>
        forward && moveButton(forward);
    const backOf = ({ back }: StageSpec, captionId: string) =>
        back && moveButton(back, captionId);
    const columnId = (stage: OrderStage) => `${uid}-column-${stage}`;
    const boxCaptionId = (stage: OrderStage) => `${uid}-box-${stage}`;

    return (
        <section
            aria-labelledby={lineHeadingId(line.id)}
            className="flex flex-col gap-4 rounded-xl border bg-card p-4 text-card-foreground"
        >
            <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div className="flex min-w-0 flex-col gap-1">
                    <h3
                        id={lineHeadingId(line.id)}
                        tabIndex={-1}
                        className="rounded-sm text-base font-bold break-words"
                    >
                        {product.deleted ? (
                            product.name
                        ) : (
                            <Link
                                href={show(product.id)}
                                className="hover:underline focus-visible:underline"
                            >
                                {product.name}
                            </Link>
                        )}{' '}
                        ·{' '}
                        <span className="font-mono text-[0.8125rem] tabular-nums">
                            {product.sku}
                        </span>{' '}
                        · {formatUnits(line.quantity, locale)}
                    </h3>
                    {/* Chips of a counted-by-size line; one "One size 50" chip on a line recorded before sizes; none for a one-size product. */}
                    {line.size_mode !== 'one_size' && (
                        <SizeChips sizes={orderLineSizeCounts(line.sizes)} />
                    )}
                </div>
                <div className="flex flex-wrap items-center gap-3">
                    <ProgressStatus
                        status={line.status}
                        label={line.status_label}
                    />
                    <p className="text-sm font-medium tabular-nums">
                        {formatCount(line.shipped, locale)} of{' '}
                        {formatCount(line.quantity, locale)} shipped
                    </p>
                </div>
            </div>

            {line.size_mode === 'by_size' ? (
                <SizeStagesTable
                    productName={product.name}
                    columns={STAGES.map((spec) => ({
                        key: spec.stage,
                        caption: spec.caption,
                        captionId: columnId(spec.stage),
                        forward: forwardOf(spec),
                        back: backOf(spec, columnId(spec.stage)),
                    }))}
                    rows={line.sizes.map((size) => ({
                        id: size.size.id,
                        label: size.size.label,
                        figures: figuresOf(size),
                    }))}
                    total={figuresOf(line)}
                />
            ) : (
                <ol className={`${STAGE_BOXES} lg:grid-cols-4`}>
                    {STAGES.map((spec) => (
                        <StageFigure
                            key={spec.stage}
                            caption={spec.caption}
                            captionId={boxCaptionId(spec.stage)}
                            value={formatUnits(line[spec.stage], locale)}
                            forward={forwardOf(spec)}
                            back={backOf(spec, boxCaptionId(spec.stage))}
                        />
                    ))}
                </ol>
            )}

            {product.deleted && (
                <p
                    id={`${uid}-deleted`}
                    className="text-sm text-muted-foreground"
                >
                    {product.name} was deleted, so it can&apos;t be cut.
                </p>
            )}
        </section>
    );
}
