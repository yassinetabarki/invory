import { ean13Modules } from '@/lib/ean13';
import { cn } from '@/lib/utils';

/** GS1 quiet zones, in modules: 11 to the left of the bars, 7 to the right. */
const QUIET_LEFT = 11;
const QUIET_RIGHT = 7;
const BAR_HEIGHT = 56;

type BarcodeProps = {
    /** The 13 digits of an EAN-13 number, as the server sent them. */
    value: string;
    /** Width of the whole picture in px, quiet margins included. */
    width?: number;
    className?: string;
};

/** [first module, length] of every run of consecutive bar modules. */
function barRuns(modules: string): Array<[number, number]> {
    const runs: Array<[number, number]> = [];
    let start = -1;

    for (let i = 0; i <= modules.length; i++) {
        const isBar = modules[i] === '1';

        if (isBar && start < 0) {
            start = i;
        } else if (!isBar && start >= 0) {
            runs.push([start, i - start]);
            start = -1;
        }
    }

    return runs;
}

/**
 * An EAN-13 barcode: the bars and, under them, the 13 digits in the number
 * font. Always black on white, also in dark mode, so it scans. The bars are
 * decoration (`aria-hidden`); the digits are the text people and screen
 * readers read. Draws nothing for anything that isn't a valid EAN-13 number.
 */
export function Barcode({ value, width = 160, className }: BarcodeProps) {
    const modules = ean13Modules(value);

    if (modules === null) {
        return null;
    }

    return (
        <div
            data-slot="barcode"
            style={{ width }}
            className={cn(
                'inline-flex max-w-full flex-col items-center bg-white pb-1 text-black',
                className,
            )}
        >
            <svg
                aria-hidden="true"
                focusable="false"
                viewBox={`0 0 ${QUIET_LEFT + modules.length + QUIET_RIGHT} ${BAR_HEIGHT}`}
                shapeRendering="crispEdges"
                className="block w-full"
            >
                {barRuns(modules).map(([start, length]) => (
                    <rect
                        key={start}
                        x={QUIET_LEFT + start}
                        y={0}
                        width={length}
                        height={BAR_HEIGHT}
                        fill="#000"
                    />
                ))}
            </svg>
            <span className="font-mono text-xs leading-none tracking-[0.12em]">
                {value}
            </span>
        </div>
    );
}
