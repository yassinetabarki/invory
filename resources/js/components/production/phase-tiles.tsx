import { Link } from '@inertiajs/react';
import { progressUrl } from '@/components/production/progress-address';
import { Skeleton } from '@/components/ui/skeleton';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import { cn } from '@/lib/utils';
import type {
    PhaseRef,
    ProgressFilters,
    ProgressStep,
    ProgressTile,
} from '@/types/domain';

type PhaseTilesProps = {
    path: ProgressStep[];
    filters: ProgressFilters;
    /** The counts; undefined while they load (the frames show at once). */
    tiles: ProgressTile[] | undefined;
    allUnits: number | undefined;
};

type TileProps = {
    name: string;
    href: string;
    current: boolean;
    count: number | undefined;
    className?: string;
};

function Tile({ name, href, current, count, className }: TileProps) {
    const { locale } = useAppSettings();

    return (
        <Link
            href={href}
            preserveScroll
            aria-current={current ? 'page' : undefined}
            className={cn(
                'flex min-h-11 flex-col gap-1 rounded-lg border bg-card p-3 text-card-foreground transition-colors hover:bg-muted/50 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
                current && 'border-2 border-primary bg-primary/5',
                className,
            )}
        >
            <span className="text-sm text-muted-foreground">{name}</span>
            {count === undefined ? (
                <Skeleton className="h-6 w-16" />
            ) : (
                <span
                    className={cn(
                        'font-mono text-lg tabular-nums',
                        current ? 'font-bold' : 'font-medium',
                    )}
                >
                    {formatCount(count, locale)}{' '}
                    <span className="font-sans text-sm font-normal text-muted-foreground">
                        {count === 1 ? 'unit' : 'units'}
                    </span>
                </span>
            )}
        </Link>
    );
}

/** The phase tiles (INV-5, AC-3): All phases, In cutting and the workshop's phases, following the filters. */
export function PhaseTiles({
    path,
    filters,
    tiles,
    allUnits,
}: PhaseTilesProps) {
    const units = (phase: PhaseRef) =>
        tiles?.find((tile) => tile.phase === phase)?.units;

    return (
        <div
            role="group"
            aria-label="Phases"
            className="grid grid-cols-2 gap-2 sm:grid-cols-[repeat(auto-fill,minmax(10rem,1fr))]"
        >
            <Tile
                name="All phases"
                href={progressUrl(filters, null)}
                current={filters.phase === null}
                count={allUnits}
                className="col-span-2 sm:col-span-1"
            />
            {path.map((step) => (
                <Tile
                    key={String(step.phase)}
                    name={step.name}
                    href={progressUrl(filters, step.phase)}
                    current={filters.phase === step.phase}
                    count={units(step.phase)}
                />
            ))}
        </div>
    );
}
