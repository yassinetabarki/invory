import { router } from '@inertiajs/react';
import { useState } from 'react';
import { AddClientDialog } from '@/components/clients/add-client-dialog';
import { ClientPicker, clientLine } from '@/components/clients/client-picker';
import { useClientOptions } from '@/components/clients/use-client-options';
import type {
    ClientFields,
    ClientRef,
    ClientSource,
    Option,
} from '@/types/domain';

type EditClientFieldProps = {
    id: string;
    /** The order's client: her id and the name the order keeps. */
    clientId: number | '';
    clientName: string;
    onChange: (client: ClientRef) => void;
    error?: string;
    /** The cities for the Add client window; absent until it asks for them. */
    cities?: string[];
    sources: Option<ClientSource>[];
};

/**
 * Edit order's Client field (INV-4, AC-6): the client picker, with no phone
 * lookup, showing the order's client and her grey line ("Instagram · Sousse ·
 * 22 345 678"). Its last option adds a client: the Add client window opens over
 * the order with the name typed (or the digits as the phone), and the new
 * client is chosen once saved.
 */
export function EditClientField({
    id,
    clientId,
    clientName,
    onChange,
    error,
    cities,
    sources,
}: EditClientFieldProps) {
    const [chosen, setChosen] = useState<ClientRef | null>(null);
    const [adding, setAdding] = useState<Partial<ClientFields> | null>(null);
    // Who the order's client is: the picker's own search brings her along by id.
    const { options } = useClientOptions('', clientId === '' ? null : clientId);
    const saved =
        clientId === ''
            ? null
            : (options.find((option) => option.id === clientId) ?? null);
    const value: ClientRef | null =
        chosen ??
        saved ??
        (clientId === ''
            ? null
            : {
                  id: clientId,
                  name: clientName,
                  phone: '',
                  city: '',
                  source: null,
                  source_label: null,
                  source_other: null,
              });

    function choose(client: ClientRef) {
        setChosen(client);
        onChange(client);
    }

    function add(typed: string) {
        // Digits typed in the search are a phone; anything else is a name.
        setAdding(
            /^[+\d][\d\s.-]*$/.test(typed) ? { phone: typed } : { name: typed },
        );

        if (cities === undefined) {
            router.reload({ only: ['cities'] });
        }
    }

    const errorId = `${id}-error`;
    const lineId = `${id}-line`;
    const describedBy =
        [value?.phone ? lineId : null, error ? errorId : null]
            .filter(Boolean)
            .join(' ') || undefined;

    return (
        <div className="flex min-w-0 flex-col gap-2 sm:col-span-2">
            <p id={`${id}-label`} className="text-sm leading-none font-medium">
                Client
            </p>
            <ClientPicker
                id={id}
                labelledBy={`${id}-label`}
                aria-invalid={error ? true : undefined}
                aria-describedby={describedBy}
                value={value}
                onSelect={(client) => client && choose(client)}
                onAdd={add}
            />
            {value?.phone && (
                <p id={lineId} className="text-xs text-muted-foreground">
                    {clientLine(value)}
                </p>
            )}
            {error && (
                <p id={errorId} className="text-sm text-destructive-foreground">
                    {error}
                </p>
            )}
            <AddClientDialog
                open={adding !== null}
                onOpenChange={(open) => !open && setAdding(null)}
                initial={adding ?? undefined}
                cities={cities ?? []}
                sources={sources}
                onCreated={choose}
                onChooseExisting={(client) => {
                    choose(client);
                    setAdding(null);
                }}
            />
        </div>
    );
}
