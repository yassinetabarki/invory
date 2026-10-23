import { Link } from '@inertiajs/react';
import { useId } from 'react';
import { lineHeadingId, MOVES } from '@/components/orders/move-units-dialog';
import { ProgressStatus } from '@/components/orders/progress-status';
import { SizeStagesTable } from '@/components/orders/size-stages-table';
import { formatUnits, StageFigure } from '@/components/orders/stage-figure';
import { Button } from '@/components/ui/button';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import { formatSizes, orderLineSizeCounts } from '@/lib/sizes';
import { show } from '@/routes/products';
import type { OrderLine, OrderLineMove, OrderStage } from '@/types/domain';

type OrderLineCardProps = {
    line: OrderLine;
    /** The page opens the move window; this component never moves units. */
    onMove: (line: OrderLine, move: OrderLineMove) => void;
};

type StageSpec = {
    stage: OrderStage;
    caption: string;
    /** Buttons under the stage, as [move, label]. */
    moves: [OrderLineMove, string][];
};

// Fixed UI copy (the props carry no stage labels).
const STAGES: StageSpec[] = [
    {
        stage: 'to_start',
        caption: 'To start',
        moves: [['start_cutting', 'Start cutting']],
    },
    {
        stage: 'in_cutting',
        caption: 'In cutting',
        moves: [
            ['mark_ready', 'Mark ready'],
            ['back_to_start', 'Move back'],
        ],
    },
    {
        stage: 'ready_to_ship',
        caption: 'Ready to ship',
        moves: [
            ['ship', 'Ship'],
            ['back_to_cutting', 'Move back'],
        ],
    },
    {
        stage: 'shipped',
        caption: 'Shipped',
        moves: [['back_to_ready', 'Move back']],
    },
];

const figuresOf = (figures: Record<OrderStage, number>) =>
    Object.fromEntries(STAGES.map(({ stage }) => [stage, figures[stage]]));

/** One product of the order: its four stage figures, the buttons that move units, and its status. */
export function OrderLineCard({ line, onMove }: OrderLineCardProps) {
    const uid = useId();
    const { locale } = useAppSettings();
    const { product } = line;
    // "S 10 · M 20" on a counted-by-size line, "One size 50" on one recorded before sizes; nothing for a one-size product.
    const sizesText =
        line.size_mode === 'one_size'
            ? ''
            : formatSizes(orderLineSizeCounts(line.sizes), locale);

    // The buttons that move units out of a stage.
    const stageButtons = ({ moves }: StageSpec) =>
        moves.map(([move, label]) => {
            // A deleted product's units still move on and back, but never into cutting.
            const cutBlocked = move === 'start_cutting' && product.deleted;

            return (
                <Button
                    key={move}
                    type="button"
                    size="sm"
                    variant={move.startsWith('back_') ? 'outline' : 'default'}
                    disabled={line[MOVES[move].from] === 0 || cutBlocked}
                    aria-describedby={cutBlocked ? `${uid}-deleted` : undefined}
                    onClick={() => onMove(line, move)}
                >
                    {label}
                </Button>
            );
        });

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
                        · {product.sku} · {formatUnits(line.quantity, locale)}
                    </h3>
                    {sizesText && (
                        <p className="text-sm text-muted-foreground">
                            {sizesText}
                        </p>
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
                        actions: stageButtons(spec),
                    }))}
                    rows={line.sizes.map((size) => ({
                        id: size.size.id,
                        label: size.size.label,
                        figures: figuresOf(size),
                    }))}
                    total={figuresOf(line)}
                />
            ) : (
                <ol className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    {STAGES.map((spec) => (
                        <StageFigure
                            key={spec.stage}
                            caption={spec.caption}
                            value={formatUnits(line[spec.stage], locale)}
                        >
                            {stageButtons(spec)}
                        </StageFigure>
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
