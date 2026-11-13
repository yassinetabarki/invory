import { Link } from '@inertiajs/react';
import {
    ChevronDown,
    ChevronUp,
    Eye,
    History,
    Pencil,
    Plus,
} from 'lucide-react';
import { Fragment, useState } from 'react';
import { CategoryBadge } from '@/components/materials/category-badge';
import { HistoryPanel } from '@/components/materials/history-panel';
import { RestockPanel } from '@/components/materials/restock-panel';
import { RestockSheet } from '@/components/materials/restock-sheet';
import { StockBadge } from '@/components/materials/stock-badge';
import { StockByColourPanel } from '@/components/materials/stock-by-colour-panel';
import { useRowPanels } from '@/components/materials/use-row-panels';
import type { RowPanels } from '@/components/materials/use-row-panels';
import { Money, MoneyRange } from '@/components/shared/money';
import { PhotoThumb } from '@/components/shared/photo-thumb';
import { Quantity } from '@/components/shared/quantity';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useAppSettings } from '@/hooks/use-app-settings';
import { EMPTY_VALUE, formatCount } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Material, StockMovement } from '@/types/domain';

type MaterialsTableProps = {
    materials: Material[];
    /** URL builders (Wayfinder in the page, plain functions in tests). */
    hrefs: {
        show: (material: Material) => string;
        edit: (material: Material) => string;
    };
    /**
     * The page's deferred `recent_history`: undefined until it has loaded.
     * The History panel shows a skeleton until then.
     */
    recentHistory?: StockMovement[];
};

type ViewProps = MaterialsTableProps & {
    /** Opens the Restock sheet of a per-colour material. */
    onRestockColours: (material: Material) => void;
};

type PanelProps = Pick<MaterialsTableProps, 'hrefs' | 'recentHistory'> & {
    panels: RowPanels;
};

type RowProps = PanelProps & { material: Material };

/** Columns of the table: the open panel spans all of them. */
const COLUMN_COUNT = 9;

/** A muted line under a figure of a per-colour material ("each colour"). */
function Hint({ children }: { children: string }) {
    return (
        <span className="block text-xs font-normal text-muted-foreground">
            {children}
        </span>
    );
}

/**
 * Cost per one unit of the material, e.g. "€8.50 / m"; with cost per colour
 * the server's range, "TND 8.500–9.200 / m", and "per colour" under it.
 */
function UnitCost({ material }: { material: Material }) {
    return (
        <>
            {material.unit_cost_range ? (
                <MoneyRange value={material.unit_cost_range} />
            ) : (
                <Money value={material.unit_cost} precise />
            )}
            <span className="text-muted-foreground"> / {material.unit}</span>
            {material.unit_cost_range && <Hint>per colour</Hint>}
        </>
    );
}

/**
 * The total in bold; for a per-colour material the "2 colours" button under it
 * (44 px on a phone card) that opens the Stock by colour panel.
 */
function InStock({
    material,
    panels,
    layout,
}: {
    material: Material;
    panels: RowPanels;
    layout: 'table' | 'card';
}) {
    const { locale } = useAppSettings();
    const count = material.colours.length;
    const trigger = panels.triggerProps(material.id, 'colours');
    const Chevron = trigger['aria-expanded'] ? ChevronUp : ChevronDown;

    return (
        <>
            <Quantity value={material.quantity_on_hand} unit={material.unit} />
            {material.stock_mode === 'per_colour' && (
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    aria-label={`${formatCount(count, locale)} ${count === 1 ? 'colour' : 'colours'}, show stock by colour for ${material.name}`}
                    className={cn(
                        'flex w-fit text-xs font-medium text-muted-foreground',
                        layout === 'card' ? '-ml-2 h-11' : '-mr-2 ml-auto h-7',
                        trigger['aria-expanded'] && 'text-foreground',
                    )}
                    {...trigger}
                >
                    {formatCount(count, locale)}{' '}
                    {count === 1 ? 'colour' : 'colours'}
                    <Chevron aria-hidden="true" />
                </Button>
            )}
        </>
    );
}

/** The badge and, for a per-colour material, the colours that need restock under it. */
function Status({ material }: { material: Material }) {
    return (
        <>
            <StockBadge status={material.stock_status} />
            {material.restock_colours && (
                <Hint>{material.restock_colours}</Hint>
            )}
        </>
    );
}

/** "3 products", "1 product" or an em dash when no product uses the material. */
function UsedIn({ count }: { count: number }) {
    const { locale } = useAppSettings();

    if (count === 0) {
        return <>{EMPTY_VALUE}</>;
    }

    return (
        <>
            {formatCount(count, locale)} {count === 1 ? 'product' : 'products'}
        </>
    );
}

function ReorderLevel({ material }: { material: Material }) {
    return material.reorder_level === null ? (
        <>{EMPTY_VALUE}</>
    ) : (
        <>
            <Quantity value={material.reorder_level} unit={material.unit} />
            {material.stock_mode === 'per_colour' && <Hint>each colour</Hint>}
        </>
    );
}

/** The open panel of a row (restock or history), or nothing. */
function RowPanel({ material, panels, hrefs, recentHistory }: RowProps) {
    const kind = panels.openKind(material.id);

    if (kind === 'restock') {
        return (
            <RestockPanel
                id={panels.panelId(material.id, 'restock')}
                material={material}
                onClose={() => panels.close(material.id)}
            />
        );
    }

    if (kind === 'colours') {
        return (
            <StockByColourPanel
                id={panels.panelId(material.id, 'colours')}
                material={material}
                onClose={() => panels.close(material.id)}
            />
        );
    }

    if (kind === 'history') {
        return (
            <HistoryPanel
                id={panels.panelId(material.id, 'history')}
                material={material}
                recentHistory={recentHistory}
                showHref={hrefs.show(material)}
                onClose={() => panels.close(material.id)}
            />
        );
    }

    return null;
}

type RowActionsProps = {
    material: Material;
    hrefs: MaterialsTableProps['hrefs'];
    panels: RowPanels;
    /** `table`: History, View and Edit are icons. `card`: labelled buttons. */
    layout: 'table' | 'card';
    onRestockColours: (material: Material) => void;
};

/**
 * Restock (primary when the material needs restocking), History, View and
 * Edit. Every control names the material; Restock and History open an inline
 * panel and announce it with `aria-expanded`.
 */
function RowActions({
    material,
    hrefs,
    panels,
    layout,
    onRestockColours,
}: RowActionsProps) {
    // A per-colour material restocks in a side sheet, not an inline panel.
    const sheet = material.stock_mode === 'per_colour';
    const restock = panels.triggerProps(material.id, 'restock');
    const history = panels.triggerProps(material.id, 'history');
    const restockOpen = !sheet && restock['aria-expanded'];
    const historyOpen = history['aria-expanded'];
    const needsRestock = material.stock_status !== 'in_stock';
    const isTable = layout === 'table';

    return (
        <div
            className={cn(
                'flex items-center gap-1',
                isTable ? 'justify-end' : 'flex-wrap gap-2',
            )}
        >
            <Button
                type="button"
                size="sm"
                variant={needsRestock && !restockOpen ? 'default' : 'outline'}
                aria-label={`Restock ${material.name}`}
                className={cn(
                    restockOpen &&
                        'border-transparent bg-info text-info-foreground hover:bg-info',
                )}
                {...(sheet
                    ? {
                          'aria-haspopup': 'dialog' as const,
                          onClick: () => onRestockColours(material),
                      }
                    : restock)}
            >
                <Plus aria-hidden="true" />
                Restock
            </Button>
            {isTable ? (
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label={`History of ${material.name}`}
                    title="History"
                    className={cn(
                        historyOpen && 'bg-info text-info-foreground',
                    )}
                    {...history}
                >
                    <History aria-hidden="true" />
                </Button>
            ) : (
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    aria-label={`History of ${material.name}`}
                    className={cn(
                        historyOpen &&
                            'border-transparent bg-info text-info-foreground hover:bg-info',
                    )}
                    {...history}
                >
                    <History aria-hidden="true" />
                    History
                </Button>
            )}
            <Button
                asChild
                variant={isTable ? 'ghost' : 'outline'}
                size={isTable ? 'icon' : 'sm'}
            >
                <Link
                    href={hrefs.show(material)}
                    aria-label={`View ${material.name}`}
                    title="View"
                >
                    <Eye aria-hidden="true" />
                    {!isTable && 'View'}
                </Link>
            </Button>
            <Button
                asChild
                variant={isTable ? 'ghost' : 'outline'}
                size={isTable ? 'icon' : 'sm'}
            >
                <Link
                    href={hrefs.edit(material)}
                    aria-label={`Edit ${material.name}`}
                    title="Edit"
                >
                    <Pencil aria-hidden="true" />
                    {!isTable && 'Edit'}
                </Link>
            </Button>
        </div>
    );
}

/** >= md: one row per material; an open panel is a full-width row below it. */
function MaterialsTableView({
    materials,
    hrefs,
    recentHistory,
    onRestockColours,
}: ViewProps) {
    const panels = useRowPanels();

    return (
        <div className="hidden rounded-lg border bg-card md:block">
            <Table>
                <TableCaption className="sr-only">Materials</TableCaption>
                <TableHeader>
                    <TableRow>
                        <TableHead scope="col">Material</TableHead>
                        <TableHead scope="col">Category</TableHead>
                        <TableHead scope="col" className="text-right">
                            In stock
                        </TableHead>
                        <TableHead scope="col" className="text-right">
                            Reorder at
                        </TableHead>
                        <TableHead scope="col" className="text-right">
                            Unit cost
                        </TableHead>
                        <TableHead scope="col" className="text-right">
                            Stock value
                        </TableHead>
                        <TableHead scope="col">Status</TableHead>
                        <TableHead scope="col" className="text-right">
                            Used in
                        </TableHead>
                        <TableHead scope="col" className="text-right">
                            <span className="sr-only">Actions</span>
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {materials.map((material) => (
                        <Fragment key={material.id}>
                            <TableRow>
                                <TableCell className="min-w-60 whitespace-normal">
                                    <div className="flex items-center gap-3">
                                        <PhotoThumb
                                            src={material.photo_url}
                                            alt=""
                                            size="sm"
                                        />
                                        <div className="flex min-w-0 flex-col">
                                            <Link
                                                href={hrefs.show(material)}
                                                className="font-medium hover:underline focus-visible:underline"
                                            >
                                                {material.name}
                                            </Link>
                                            <span className="font-mono text-xs text-muted-foreground">
                                                {material.sku}
                                            </span>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <CategoryBadge
                                        label={material.category_label}
                                    />
                                </TableCell>
                                <TableCell className="text-right font-semibold tabular-nums">
                                    <InStock
                                        material={material}
                                        panels={panels}
                                        layout="table"
                                    />
                                </TableCell>
                                <TableCell className="text-right text-muted-foreground tabular-nums">
                                    <ReorderLevel material={material} />
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    <UnitCost material={material} />
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    <Money value={material.stock_value} />
                                </TableCell>
                                <TableCell>
                                    <Status material={material} />
                                </TableCell>
                                <TableCell className="text-right text-muted-foreground tabular-nums">
                                    <UsedIn count={material.used_in_count} />
                                </TableCell>
                                <TableCell>
                                    <RowActions
                                        material={material}
                                        hrefs={hrefs}
                                        panels={panels}
                                        layout="table"
                                        onRestockColours={onRestockColours}
                                    />
                                </TableCell>
                            </TableRow>
                            {panels.openKind(material.id) && (
                                <TableRow className="hover:bg-transparent">
                                    <TableCell
                                        colSpan={COLUMN_COUNT}
                                        className="bg-background px-4 pt-0 pb-4 whitespace-normal"
                                    >
                                        <RowPanel
                                            material={material}
                                            panels={panels}
                                            hrefs={hrefs}
                                            recentHistory={recentHistory}
                                        />
                                    </TableCell>
                                </TableRow>
                            )}
                        </Fragment>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

/** < md: stacked cards with the same actions; panels open inside the card. */
function MaterialsCardsView({
    materials,
    hrefs,
    recentHistory,
    onRestockColours,
}: ViewProps) {
    const panels = useRowPanels();

    return (
        <ul aria-label="Materials" className="space-y-3 md:hidden">
            {materials.map((material) => (
                <li
                    key={material.id}
                    className="space-y-3 rounded-lg border bg-card p-4 text-card-foreground"
                >
                    <div className="flex items-start justify-between gap-3">
                        <div className="flex min-w-0 items-center gap-3">
                            <PhotoThumb
                                src={material.photo_url}
                                alt=""
                                size="md"
                            />
                            <div className="min-w-0">
                                <Link
                                    href={hrefs.show(material)}
                                    className="font-medium break-words hover:underline focus-visible:underline"
                                >
                                    {material.name}
                                </Link>
                                <p className="text-xs text-muted-foreground">
                                    <span className="font-mono">
                                        {material.sku}
                                    </span>
                                    {' · '}
                                    {material.category_label}
                                </p>
                            </div>
                        </div>
                        <div className="shrink-0">
                            <StockBadge status={material.stock_status} />
                        </div>
                    </div>
                    {/* Its own full-width line, so the name and SKU above keep their room (down to 320 px). */}
                    {material.restock_colours && (
                        <Hint>{material.restock_colours}</Hint>
                    )}
                    <dl className="grid grid-cols-2 gap-2 text-sm sm:grid-cols-3">
                        <div>
                            <dt className="text-xs text-muted-foreground">
                                In stock
                            </dt>
                            <dd className="font-semibold tabular-nums">
                                <InStock
                                    material={material}
                                    panels={panels}
                                    layout="card"
                                />
                            </dd>
                        </div>
                        <div>
                            <dt className="text-xs text-muted-foreground">
                                Reorder at
                            </dt>
                            <dd className="tabular-nums">
                                <ReorderLevel material={material} />
                            </dd>
                        </div>
                        <div>
                            <dt className="text-xs text-muted-foreground">
                                Unit cost
                            </dt>
                            <dd className="tabular-nums">
                                <UnitCost material={material} />
                            </dd>
                        </div>
                        <div>
                            <dt className="text-xs text-muted-foreground">
                                Stock value
                            </dt>
                            <dd className="tabular-nums">
                                <Money value={material.stock_value} />
                            </dd>
                        </div>
                        <div>
                            <dt className="text-xs text-muted-foreground">
                                Used in
                            </dt>
                            <dd>
                                <UsedIn count={material.used_in_count} />
                            </dd>
                        </div>
                    </dl>
                    <RowActions
                        material={material}
                        hrefs={hrefs}
                        panels={panels}
                        layout="card"
                        onRestockColours={onRestockColours}
                    />
                    <RowPanel
                        material={material}
                        panels={panels}
                        hrefs={hrefs}
                        recentHistory={recentHistory}
                    />
                </li>
            ))}
        </ul>
    );
}

export function MaterialsTable(props: MaterialsTableProps) {
    const [restockId, setRestockId] = useState<number | null>(null);
    const restocking = props.materials.find(
        (material) => material.id === restockId,
    );

    return (
        <>
            <MaterialsTableView
                {...props}
                onRestockColours={(material) => setRestockId(material.id)}
            />
            <MaterialsCardsView
                {...props}
                onRestockColours={(material) => setRestockId(material.id)}
            />
            {restocking && (
                <RestockSheet
                    material={restocking}
                    open
                    onOpenChange={(open) => {
                        if (!open) {
                            setRestockId(null);
                        }
                    }}
                />
            )}
        </>
    );
}
