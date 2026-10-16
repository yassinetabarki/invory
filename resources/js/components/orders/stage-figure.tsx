import type { ReactNode } from 'react';
import { formatCount } from '@/lib/format';

/** "1 unit" / "50 units": the count is the server's, only formatted here. */
export function formatUnits(count: number, locale: string): string {
    return `${formatCount(count, locale)} ${count === 1 ? 'unit' : 'units'}`;
}

type StageFigureProps = {
    /** The stage's fixed caption ("To start", "In cutting"…). */
    caption: string;
    /** Already formatted ("50 units"). */
    value: string;
    /** The buttons that move units out of this stage. */
    children?: ReactNode;
};

/** One of a line's four stages: caption, figure and its buttons. */
export function StageFigure({ caption, value, children }: StageFigureProps) {
    return (
        <li className="flex min-w-0 flex-col gap-2 rounded-lg border bg-background p-3">
            <p className="flex flex-col">
                <span className="text-sm text-muted-foreground">{caption}</span>{' '}
                <span className="text-lg font-bold tabular-nums">{value}</span>
            </p>
            {children && (
                <div className="mt-auto flex flex-col gap-2">{children}</div>
            )}
        </li>
    );
}
