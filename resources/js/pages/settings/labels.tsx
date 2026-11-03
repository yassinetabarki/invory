import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/settings/labels';
import type { LabelSettingsProps, TicketSize } from '@/types/domain';

/** One size in the list: "A4 sheet, 8 tickets (2 × 4)". */
function TicketSizeOption({
    option,
}: {
    option: LabelSettingsProps['ticket_sizes'][number];
}) {
    return <SelectItem value={option.value}>{option.label}</SelectItem>;
}

/**
 * Settings › Labels (INV-15-g, AC-7): how the workshop's work tickets print. Any member may change it. INV-9 adds its label size and the
 * selling price to this page. On a phone **Save label settings** sits in a bar fixed at the bottom.
 */
export default function Labels({
    ticket_size: ticketSize,
    ticket_sizes: ticketSizes,
}: LabelSettingsProps) {
    const form = useForm<{ ticket_size: TicketSize }>({
        ticket_size: ticketSize,
    });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.submit(update(), { preserveScroll: true });
    }

    return (
        <>
            <Head title="Labels" />

            <h1 className="sr-only">Labels settings</h1>

            <form
                noValidate
                onSubmit={submit}
                className="space-y-6 pb-20 sm:pb-0"
            >
                <Heading
                    variant="small"
                    title="Labels"
                    description="How your work tickets print."
                />

                <div className="grid max-w-md gap-2">
                    <Label htmlFor="ticket-size">Ticket size</Label>
                    <Select
                        value={form.data.ticket_size}
                        onValueChange={(value) =>
                            form.setData('ticket_size', value as TicketSize)
                        }
                    >
                        <SelectTrigger
                            id="ticket-size"
                            className="w-full max-sm:h-11"
                            aria-invalid={
                                form.errors.ticket_size ? true : undefined
                            }
                            aria-describedby={
                                form.errors.ticket_size
                                    ? 'ticket-size-error'
                                    : undefined
                            }
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {ticketSizes.map((option) => (
                                <TicketSizeOption
                                    key={option.label}
                                    option={option}
                                />
                            ))}
                        </SelectContent>
                    </Select>
                    {form.errors.ticket_size && (
                        <p
                            id="ticket-size-error"
                            className="text-sm text-destructive"
                        >
                            {form.errors.ticket_size}
                        </p>
                    )}
                </div>

                <div className="max-sm:fixed max-sm:inset-x-0 max-sm:bottom-0 max-sm:z-10 max-sm:border-t max-sm:bg-background max-sm:p-3">
                    <Button
                        type="submit"
                        disabled={form.processing}
                        className="max-sm:h-11 max-sm:w-full"
                    >
                        {form.processing && <Spinner />}
                        Save label settings
                    </Button>
                </div>
            </form>
        </>
    );
}
