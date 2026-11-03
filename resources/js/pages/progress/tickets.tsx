import { Head } from '@inertiajs/react';
import { Printer } from 'lucide-react';
import { useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount, formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import type {
    PrintTicketsProps,
    TicketQr,
    TicketSize,
    WorkTicket,
} from '@/types/domain';

/**
 * The page's own print rules: an A4 sheet of 8 tickets (2 × 4 of 105 × 74 mm) or one ticket per 100 × 60 mm label, no margins, and
 * nothing but the tickets on paper.
 */
const PAGE_RULES: Record<TicketSize, string> = {
    a4_8: '@page { size: A4; margin: 0; }',
    label_100x60: '@page { size: 100mm 60mm; margin: 0; }',
};

/** The QR code of a ticket: the server's path of dark modules, black on white, 30 mm (smaller on a label). */
function QrCode({ qr, className }: { qr: TicketQr; className?: string }) {
    return (
        <svg
            viewBox={`0 0 ${qr.size} ${qr.size}`}
            className={cn('shrink-0 bg-white', className)}
            shapeRendering="crispEdges"
            role="img"
            aria-label="QR code"
        >
            <path d={qr.path} fill="#000" />
        </svg>
    );
}

/** "☑ In cutting ☐ Sewing ☐ Finishing ☐ Put in stock": the path as it is now, the batch's step ticked. */
function pathLine(ticket: WorkTicket): string {
    return ticket.path
        .map((step, index) => `${index === ticket.current ? '☑' : '☐'} ${step}`)
        .join(' ');
}

/** One work ticket (INV-15, AC-1): no client, no phone number and no price. */
function Ticket({ ticket, size }: { ticket: WorkTicket; size: TicketSize }) {
    const { locale } = useAppSettings();
    const label = size === 'label_100x60';

    return (
        <article
            aria-label={`Work ticket ${ticket.number}`}
            className={cn(
                'flex break-inside-avoid flex-col justify-between overflow-hidden bg-white text-black',
                label
                    ? 'h-[60mm] w-[100mm] break-after-page p-[3mm] last:break-after-auto'
                    : 'h-[74mm] w-[105mm] border border-dashed border-neutral-300 p-[5mm] print:border-neutral-200',
            )}
        >
            <header className="flex items-baseline justify-between gap-2">
                <span className="text-[9pt] tracking-wide uppercase">
                    Work ticket
                </span>
                <span
                    className={cn(
                        'font-mono font-bold',
                        label ? 'text-[18pt]' : 'text-[22pt]',
                    )}
                >
                    {ticket.number}
                </span>
            </header>

            <div className="flex items-start gap-[4mm]">
                <QrCode
                    qr={ticket.qr}
                    className={label ? 'size-[24mm]' : 'size-[30mm]'}
                />
                <div className="flex min-w-0 flex-col gap-[1mm]">
                    <p className="line-clamp-2 text-[11pt] leading-tight font-bold">
                        {ticket.product}
                    </p>
                    <p className="text-[10pt]">{ticket.variant}</p>
                    <p
                        className={cn(
                            'font-bold',
                            label ? 'text-[14pt]' : 'text-[18pt]',
                        )}
                    >
                        {formatCount(ticket.units, locale)}{' '}
                        {ticket.units === 1 ? 'unit' : 'units'}
                    </p>
                    <p className="text-[9pt]">
                        Started {formatDate(ticket.started_on, locale)}
                    </p>
                    <p className="truncate font-mono text-[8pt]">
                        {ticket.sku}
                    </p>
                </div>
            </div>

            <div className="flex flex-col gap-[1mm] text-[8pt]">
                <p>{pathLine(ticket)}</p>
                <p>Printed {formatDate(ticket.printed_on, locale)}</p>
                <p className="flex justify-between gap-2 border-t border-black pt-[1mm]">
                    <span>Scan to move it to the next phase.</span>
                    {ticket.split_from && (
                        <span>Split from {ticket.split_from}</span>
                    )}
                </p>
            </div>
        </article>
    );
}

/**
 * Print tickets (INV-15, AC-1, AC-2, ADR-0024 decision 6): opened in a new tab from Progress, with no app frame. It calls the browser's
 * print window once drawn; **Print** tries again.
 */
export default function PrintTickets({ tickets, size }: PrintTicketsProps) {
    const { locale } = useAppSettings();
    const count = tickets.length;

    useEffect(() => {
        if (count === 0) {
            return;
        }

        // After the first paint, so the sheet is drawn before the print window takes a picture of it.
        const frame = window.requestAnimationFrame(() => window.print());

        return () => window.cancelAnimationFrame(frame);
    }, [count]);

    return (
        <>
            <Head
                title={`${formatCount(count, locale)} ${count === 1 ? 'work ticket' : 'work tickets'}`}
            />
            <style>{PAGE_RULES[size]}</style>

            <div className="min-h-screen bg-neutral-100 text-black print:bg-white">
                <div className="flex items-center justify-between gap-4 p-4 print:hidden">
                    <h1 className="text-lg font-semibold">
                        {formatCount(count, locale)}{' '}
                        {count === 1 ? 'work ticket' : 'work tickets'}
                    </h1>
                    {count > 0 && (
                        <Button type="button" onClick={() => window.print()}>
                            <Printer aria-hidden="true" />
                            Print
                        </Button>
                    )}
                </div>

                {count === 0 ? (
                    <p className="p-4 text-sm">
                        These batches have left Progress, so there is nothing to
                        print.
                    </p>
                ) : size === 'a4_8' ? (
                    <div className="mx-auto grid w-[210mm] grid-cols-2 bg-white print:w-auto">
                        {tickets.map((ticket) => (
                            <Ticket
                                key={ticket.id}
                                ticket={ticket}
                                size={size}
                            />
                        ))}
                    </div>
                ) : (
                    <div className="mx-auto flex w-[100mm] flex-col gap-4 print:gap-0">
                        {tickets.map((ticket) => (
                            <Ticket
                                key={ticket.id}
                                ticket={ticket}
                                size={size}
                            />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
