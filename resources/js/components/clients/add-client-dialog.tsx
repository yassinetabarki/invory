import { Link, useForm } from '@inertiajs/react';
import { Info } from 'lucide-react';
import { useEffect, useRef } from 'react';
import type { FormEvent } from 'react';
import {
    ClientFieldsBlock,
    EMPTY_CLIENT,
} from '@/components/clients/client-fields';
import { useClientLookup } from '@/components/clients/use-client-lookup';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { show, store } from '@/routes/clients';
import type {
    ClientFields,
    ClientRef,
    ClientSource,
    ClientSummary,
    Option,
} from '@/types/domain';

type AddClientDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** What the window starts with: a name typed in the picker, or its digits as the phone. */
    initial?: Partial<ClientFields>;
    /** The workshop's cities (City suggests them); empty while they load. */
    cities: readonly string[];
    sources: Option<ClientSource>[];
    /**
     * Where the window is: on the Clients page a known client is opened ("Open
     * Amira Ben Salah"); over an order she is chosen ("Choose Amira Ben Salah
     * instead").
     */
    onChooseExisting?: (client: ClientSummary) => void;
    /** Called with the new client once saved (Edit order chooses her). */
    onCreated?: (client: ClientRef) => void;
};

/** "Amira Ben Salah (Sousse · 22 345 678)" */
function described(client: ClientSummary): string {
    return `${client.name} (${client.city} · ${client.phone})`;
}

/**
 * The Add client window (INV-4, AC-3): Name, Phone, City and Source. Save
 * client → "Client created." (the server's toast), and the window closes. On
 * an error it stays open with what was typed. A phone another client has
 * offers her; a name already used is only a note and never blocks saving. On a
 * phone the window covers the screen, with Save client in a bar at the bottom.
 */
export function AddClientDialog({
    open,
    onOpenChange,
    initial,
    cities,
    sources,
    onChooseExisting,
    onCreated,
}: AddClientDialogProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="flex max-h-[calc(100dvh-2rem)] flex-col max-sm:h-dvh max-sm:max-h-dvh max-sm:max-w-none max-sm:rounded-none sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Add client</DialogTitle>
                    <DialogDescription className="sr-only">
                        A client&apos;s name, phone, city and, if known, where
                        they found you.
                    </DialogDescription>
                </DialogHeader>
                {open && (
                    <AddClientForm
                        initial={initial}
                        cities={cities}
                        sources={sources}
                        onChooseExisting={onChooseExisting}
                        onDone={(client) => {
                            onCreated?.(client);
                            onOpenChange(false);
                        }}
                        onCancel={() => onOpenChange(false)}
                    />
                )}
            </DialogContent>
        </Dialog>
    );
}

type AddClientFormProps = Pick<
    AddClientDialogProps,
    'initial' | 'cities' | 'sources' | 'onChooseExisting'
> & {
    onDone: (client: ClientRef) => void;
    onCancel: () => void;
};

function AddClientForm({
    initial,
    cities,
    sources,
    onChooseExisting,
    onDone,
    onCancel,
}: AddClientFormProps) {
    const formRef = useRef<HTMLFormElement>(null);
    const form = useForm<ClientFields>({ ...EMPTY_CLIENT, ...initial });
    const { data, processing } = form;
    const errors = form.errors as Partial<
        Record<keyof ClientFields | 'phone_taken_by', string>
    >;
    const byName = useClientLookup('name');
    const byPhone = useClientLookup('phone');
    const takenBy = errors.phone_taken_by;

    // A phone another client has: find her, so the window can offer her.
    useEffect(() => {
        if (takenBy) {
            byPhone.lookUp(data.phone);
        }
        // Only when the server says so; the phone typed is read at that moment.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [takenBy]);

    const errorSignature = Object.values(errors).join('|');

    // The focus goes to the first field the server refused.
    useEffect(() => {
        if (!processing && errorSignature !== '') {
            formRef.current
                ?.querySelector<HTMLElement>('[aria-invalid="true"]')
                ?.focus();
        }
    }, [errorSignature, processing]);

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.post(store.url(), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: (page) => {
                const created = (
                    page as { flash?: { client_created?: ClientRef } }
                ).flash?.client_created;

                if (created) {
                    onDone(created);
                } else {
                    onCancel();
                }
            },
        });
    }

    function offer(client: ClientSummary) {
        return onChooseExisting ? (
            <Button
                type="button"
                variant="outline"
                size="sm"
                className="self-start"
                onClick={() => onChooseExisting(client)}
            >
                Choose {client.name}
                {takenBy ? ' instead' : ''}
            </Button>
        ) : (
            <Button asChild variant="outline" size="sm" className="self-start">
                <Link href={show.url(client.id)}>Open {client.name}</Link>
            </Button>
        );
    }

    const nameKnown =
        byName.state.status === 'found' &&
        byName.state.asked.toLowerCase() === data.name.trim().toLowerCase()
            ? byName.state.client
            : null;
    const phoneOwner =
        takenBy && byPhone.state.status === 'found'
            ? byPhone.state.client
            : null;

    return (
        <form
            ref={formRef}
            noValidate
            onSubmit={submit}
            className="flex min-h-0 flex-1 flex-col gap-4"
        >
            <div className="-mx-1 flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto px-1">
                <ClientFieldsBlock
                    values={data}
                    onChange={(field, value) =>
                        form.setData((current) => ({
                            ...current,
                            [field]: value,
                        }))
                    }
                    errors={errors}
                    cities={cities}
                    sources={sources}
                    autoFocus={initial?.name ? 'phone' : 'name'}
                    onNameBlur={() => byName.lookUp(data.name)}
                    nameNote={
                        nameKnown && (
                            <div className="flex flex-col gap-2 rounded-md border border-sky-200 bg-sky-50 p-3 text-sm text-sky-950 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-100">
                                <p className="flex gap-2">
                                    <Info
                                        className="mt-0.5 size-4 shrink-0"
                                        aria-hidden="true"
                                    />
                                    You already have a client named{' '}
                                    {described(nameKnown)}.
                                </p>
                                {offer(nameKnown)}
                            </div>
                        )
                    }
                    phoneNote={phoneOwner && offer(phoneOwner)}
                />
            </div>

            <DialogFooter className="shrink-0 max-sm:sticky max-sm:bottom-0 max-sm:border-t max-sm:bg-background max-sm:pt-3">
                <Button
                    type="button"
                    variant="outline"
                    disabled={processing}
                    onClick={onCancel}
                >
                    Cancel
                </Button>
                <Button type="submit" disabled={processing}>
                    {processing && <Spinner />}
                    Save client
                </Button>
            </DialogFooter>
        </form>
    );
}
