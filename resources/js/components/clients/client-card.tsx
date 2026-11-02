import { Check, Info, TriangleAlert, UserPlus } from 'lucide-react';
import { useEffect, useId, useRef, useState } from 'react';
import { ClientFieldsBlock } from '@/components/clients/client-fields';
import { ClientPicker } from '@/components/clients/client-picker';
import { SourceLabel } from '@/components/clients/source-label';
import { useClientLookup } from '@/components/clients/use-client-lookup';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount, formatDate } from '@/lib/format';
import type {
    ClientFields,
    ClientRef,
    ClientSource,
    ClientSummary,
    Option,
} from '@/types/domain';

type ClientCardProps = {
    values: ClientFields;
    onChange: (values: ClientFields) => void;
    /** The server's messages for `client.*`, by field. */
    errors: Partial<Record<keyof ClientFields, string>>;
    cities: readonly string[];
    sources: Option<ClientSource>[];
    /** From a client page: her, shown as Existing client from the start. */
    preset: ClientSummary | null;
};

/** "3 orders · 1 open · last order 28 Oct 2026" */
function ordersLine(client: ClientSummary, locale: string): string {
    const orders = `${formatCount(client.orders, locale)} ${client.orders === 1 ? 'order' : 'orders'}`;

    return client.last_order_on
        ? `${orders} · ${formatCount(client.open_orders, locale)} open · last order ${formatDate(client.last_order_on, locale)}`
        : orders;
}

/** The note when details were typed before the phone found a client. */
function differNote(
    values: ClientFields,
    client: ClientSummary,
): string | null {
    const name = values.name.trim();
    const city = values.city.trim();
    const nameDiffers =
        name !== '' && name.toLowerCase() !== client.name.toLowerCase();
    const cityDiffers =
        city !== '' && city.toLowerCase() !== client.city.toLowerCase();

    if (!nameDiffers && !cityDiffers) {
        return null;
    }

    const typed = [
        nameDiffers ? `"${name}"` : null,
        cityDiffers ? `in ${city}` : null,
    ]
        .filter(Boolean)
        .join(' ');

    return `You typed ${typed}. ${client.phone} belongs to ${client.name} (${client.city}), so the order goes to her. Her saved details stay as they are.`;
}

/**
 * New order's Client card (INV-4 AC-5, from INV-12): the phone finds the
 * client. When Phone loses focus, Invory checks the number: a client's number
 * shows her as Existing client (her saved details are used unchanged), a new
 * number says "New client" and keeps Name, City and Source. Find a client by
 * name chooses one without her phone. Saving finds or adds the client again,
 * whatever the check said; nothing here blocks typing or saving.
 */
export function ClientCard({
    values,
    onChange,
    errors,
    cities,
    sources,
    preset,
}: ClientCardProps) {
    const uid = useId();
    const { locale } = useAppSettings();
    const byPhone = useClientLookup('phone');
    const byName = useClientLookup('name');
    const [chosen, setChosen] = useState<ClientSummary | null>(preset);
    const phoneRef = useRef<HTMLDivElement>(null);
    const [refocus, setRefocus] = useState(false);

    const phoneState = byPhone.state;
    const found =
        chosen ??
        (phoneState.status === 'found' &&
        phoneState.asked === values.phone.trim()
            ? phoneState.client
            : null);
    const errorCount = Object.values(errors).filter(Boolean).length;

    useEffect(() => {
        if (refocus) {
            phoneRef.current
                ?.querySelector<HTMLInputElement>('input[type="tel"]')
                ?.focus();
            setRefocus(false);
        }
    }, [refocus]);

    function set<K extends keyof ClientFields>(
        field: K,
        value: ClientFields[K],
    ) {
        onChange({ ...values, [field]: value });
    }

    function choose(client: ClientRef | ClientSummary) {
        // The order goes to her, by her phone. A client from the name note comes with her orders; one from the picker is
        // looked up for them.
        onChange({ ...values, phone: client.phone });

        if ('orders' in client) {
            setChosen(client);
        } else {
            setChosen(null);
            byPhone.lookUp(client.phone);
        }
    }

    function useAnotherNumber() {
        setChosen(null);
        byPhone.reset();
        onChange({ ...values, phone: '' });
        setRefocus(true);
    }

    const nameKnown =
        byName.state.status === 'found' &&
        byName.state.asked.toLowerCase() === values.name.trim().toLowerCase() &&
        byName.state.client.phone !== found?.phone
            ? byName.state.client
            : null;

    let phoneNote = null;

    if (phoneState.status === 'checking') {
        phoneNote = (
            <p
                role="status"
                className="flex items-center gap-2 text-sm text-muted-foreground"
            >
                <Spinner />
                Checking {phoneState.asked}…
            </p>
        );
    } else if (
        phoneState.status === 'none' &&
        phoneState.asked === values.phone.trim()
    ) {
        phoneNote = (
            <p
                role="status"
                className="flex gap-2 rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-950 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100"
            >
                <UserPlus
                    className="mt-0.5 size-4 shrink-0"
                    aria-hidden="true"
                />
                <span>
                    <strong>New client.</strong> {phoneState.asked} isn&apos;t
                    one of your clients yet.
                </span>
            </p>
        );
    } else if (phoneState.status === 'failed') {
        phoneNote = (
            <div
                role="status"
                className="flex flex-col gap-2 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100"
            >
                <p>
                    Couldn&apos;t check this number. Save the order and Invory
                    checks it again.
                </p>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className="self-start"
                    onClick={() => byPhone.lookUp(values.phone)}
                >
                    Try again
                </Button>
            </div>
        );
    }

    const differs = found && !chosen ? differNote(values, found) : null;
    const isNew =
        !found &&
        phoneState.status === 'none' &&
        phoneState.asked === values.phone.trim();

    return (
        <section
            aria-labelledby={`${uid}-title`}
            className="flex flex-col gap-4 rounded-xl border bg-card p-5 text-card-foreground"
        >
            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div className="flex flex-col gap-1">
                    <h2 id={`${uid}-title`} className="text-base font-bold">
                        Client
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        A phone number belongs to one client. If it is already
                        one of your clients, the order goes to them.
                    </p>
                </div>
                <ClientPicker
                    id={`${uid}-find`}
                    labelledBy={`${uid}-title`}
                    value={null}
                    onSelect={(client) => client && choose(client)}
                    findButton
                />
            </div>

            {errorCount > 0 && (
                <p
                    role="alert"
                    className="rounded-md bg-destructive/10 p-3 text-sm text-destructive-foreground"
                >
                    Check the client: {errorCount}{' '}
                    {errorCount === 1 ? "field can't" : "fields can't"} be
                    saved.
                </p>
            )}

            {found ? (
                <div className="flex flex-col gap-3">
                    <div className="flex flex-col gap-3 rounded-lg border-2 border-primary p-4 sm:flex-row sm:items-start sm:justify-between">
                        <div className="flex min-w-0 flex-col gap-1.5">
                            <p className="flex flex-wrap items-center gap-2">
                                <span className="truncate font-bold">
                                    {found.name}
                                </span>
                                <span className="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">
                                    <Check
                                        className="size-3"
                                        aria-hidden="true"
                                    />
                                    Existing client
                                </span>
                            </p>
                            <p className="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-muted-foreground">
                                {found.source && (
                                    <>
                                        <SourceLabel client={found} />
                                        <span aria-hidden="true">·</span>
                                    </>
                                )}
                                {found.city} ·{' '}
                                <span className="font-mono">{found.phone}</span>
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {ordersLine(found, locale)}
                            </p>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            className="shrink-0 max-sm:h-11 max-sm:w-full"
                            onClick={useAnotherNumber}
                        >
                            Use another number
                        </Button>
                    </div>
                    {differs && (
                        <p className="flex gap-2 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                            <TriangleAlert
                                className="mt-0.5 size-4 shrink-0"
                                aria-hidden="true"
                            />
                            {differs}
                        </p>
                    )}
                    <p className="text-sm text-muted-foreground">
                        The order uses the details saved for this client. Name,
                        City and Source aren&apos;t asked again.
                    </p>
                </div>
            ) : (
                <>
                    <div
                        ref={phoneRef}
                        role="group"
                        aria-label="Client details"
                        className="rounded-lg bg-muted/50 p-4"
                    >
                        <ClientFieldsBlock
                            values={values}
                            onChange={set}
                            errors={errors}
                            cities={cities}
                            sources={sources}
                            phoneFirst
                            onPhoneBlur={() => byPhone.lookUp(values.phone)}
                            onNameBlur={() => byName.lookUp(values.name)}
                            phoneNote={phoneNote}
                            nameNote={
                                nameKnown && (
                                    <div className="flex flex-col gap-2 rounded-md border border-sky-200 bg-sky-50 p-3 text-sm text-sky-950 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-100">
                                        <p className="flex gap-2">
                                            <Info
                                                className="mt-0.5 size-4 shrink-0"
                                                aria-hidden="true"
                                            />
                                            You already have a client named{' '}
                                            {nameKnown.name} ({nameKnown.city} ·{' '}
                                            {nameKnown.phone}).
                                        </p>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            className="self-start"
                                            onClick={() => choose(nameKnown)}
                                        >
                                            Choose {nameKnown.name}
                                        </Button>
                                    </div>
                                )
                            }
                        />
                    </div>
                    <p className="text-sm text-muted-foreground">
                        {isNew
                            ? 'Saved to your clients when you save the order.'
                            : 'The client is saved to your clients when you save the order, if the phone number is new.'}
                    </p>
                </>
            )}
        </section>
    );
}
