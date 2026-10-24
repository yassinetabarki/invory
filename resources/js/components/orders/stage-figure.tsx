import type { ReactNode } from 'react';
import { formatCount } from '@/lib/format';

/** "1 unit" / "50 units": the count is the server's, only formatted here. */
export function formatUnits(count: number, locale: string): string {
    return `${formatCount(count, locale)} ${count === 1 ? 'unit' : 'units'}`;
}

/**
 * The grid the boxes sit in: each box spans three of its rows (figure, forward line, back line),
 * so the lines line up across the boxes of a row. The caller adds its own column count.
 */
export const STAGE_BOXES = 'grid grid-cols-2 gap-x-3 gap-y-2';

type StageFigureProps = {
    /** The stage's fixed caption ("To start", "In cutting"…). */
    caption: string;
    /** Already formatted ("50 units"). */
    value: string;
    /** Id of the caption, so a "Move back" can be described by it. */
    captionId?: string;
    /** The button that moves units on from this stage (first line). */
    forward?: ReactNode;
    /** The button that moves units back (second line). */
    back?: ReactNode;
};

/** One of a line's four stages: caption, figure and its two lines of buttons (an empty line keeps its place). */
export function StageFigure({
    caption,
    value,
    captionId,
    forward,
    back,
}: StageFigureProps) {
    return (
        <li className="row-span-3 grid min-w-0 grid-cols-1 grid-rows-subgrid rounded-lg border bg-background p-3">
            <p className="flex flex-col">
                <span id={captionId} className="text-sm text-muted-foreground">
                    {caption}
                </span>{' '}
                <span className="text-lg font-bold tabular-nums">{value}</span>
            </p>
            <div data-line="forward" className="flex items-start">
                {forward}
            </div>
            <div data-line="back" className="flex items-start">
                {back}
            </div>
        </li>
    );
}
