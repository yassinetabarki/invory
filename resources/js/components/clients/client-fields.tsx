import { useId } from 'react';
import type { ReactNode } from 'react';
import { SourceChips } from '@/components/clients/source-chips';
import { FormField } from '@/components/products/form-field';
import { Input } from '@/components/ui/input';
import type { ClientFields, ClientSource, Option } from '@/types/domain';

export const NAME_HINT = 'The person or the shop, as you call them.';
export const PHONE_HINT =
    '8 digits, such as 22 345 678. For a number abroad, start with + and the country code.';

/** An empty client: the Add client window and New order's Client card start from it. */
export const EMPTY_CLIENT: ClientFields = {
    phone: '',
    name: '',
    city: '',
    source: '',
    source_other: '',
};

type ClientFieldsProps = {
    values: ClientFields;
    onChange: <K extends keyof ClientFields>(
        field: K,
        value: ClientFields[K],
    ) => void;
    /** The server's messages, by field (without any `client.` prefix). */
    errors: Partial<Record<keyof ClientFields, string>>;
    /** The workshop's cities: City suggests them. */
    cities: readonly string[];
    sources: Option<ClientSource>[];
    /** New order puts Phone first, since the phone finds the client (INV-12); Add client starts with Name. */
    phoneFirst?: boolean;
    onPhoneBlur?: () => void;
    onNameBlur?: () => void;
    /** A note under Phone (the lookup's answer). */
    phoneNote?: ReactNode;
    /** A note under Name ("You already have a client named …"). */
    nameNote?: ReactNode;
    autoFocus?: 'name' | 'phone';
};

/**
 * A client's fields (INV-4, AC-1): Name, Phone, City and Source (optional),
 * with Other source when Other is chosen. Every message comes from the server
 * and shows under its field. Phone is in the number font, opens the number
 * keypad and is 16 px on a phone, so the screen doesn't zoom.
 */
export function ClientFieldsBlock({
    values,
    onChange,
    errors,
    cities,
    sources,
    phoneFirst = false,
    onPhoneBlur,
    onNameBlur,
    phoneNote,
    nameNote,
    autoFocus,
}: ClientFieldsProps) {
    const uid = useId();
    const citiesId = `${uid}-cities`;

    const name = (
        <FormField
            key="name"
            id={`${uid}-name`}
            label="Name"
            hint={NAME_HINT}
            error={errors.name}
        >
            {(control) => (
                <>
                    <Input
                        {...control}
                        value={values.name}
                        autoComplete="off"
                        autoFocus={autoFocus === 'name'}
                        onChange={(event) =>
                            onChange('name', event.target.value)
                        }
                        onBlur={onNameBlur}
                    />
                    {nameNote}
                </>
            )}
        </FormField>
    );

    const phone = (
        <FormField
            key="phone"
            id={`${uid}-phone`}
            label="Phone"
            hint={PHONE_HINT}
            error={errors.phone}
        >
            {(control) => (
                <>
                    <Input
                        {...control}
                        type="tel"
                        inputMode="tel"
                        value={values.phone}
                        autoComplete="off"
                        autoFocus={autoFocus === 'phone'}
                        className="font-mono text-base sm:text-sm"
                        onChange={(event) =>
                            onChange('phone', event.target.value)
                        }
                        onBlur={onPhoneBlur}
                    />
                    {phoneNote}
                </>
            )}
        </FormField>
    );

    return (
        <div className="flex flex-col gap-4">
            <div className="grid gap-4 lg:grid-cols-2">
                {phoneFirst ? [phone, name] : [name, phone]}
            </div>

            <FormField
                id={`${uid}-city`}
                label="City"
                error={errors.city}
                className="lg:max-w-[calc(50%-0.5rem)]"
            >
                {(control) => (
                    <>
                        <Input
                            {...control}
                            list={citiesId}
                            value={values.city}
                            autoComplete="off"
                            onChange={(event) =>
                                onChange('city', event.target.value)
                            }
                        />
                        <datalist id={citiesId}>
                            {cities.map((city) => (
                                <option key={city} value={city} />
                            ))}
                        </datalist>
                    </>
                )}
            </FormField>

            <div className="flex flex-col gap-2">
                <p
                    id={`${uid}-source-label`}
                    className="text-sm leading-none font-medium select-none"
                >
                    Source{' '}
                    <span className="font-normal text-muted-foreground">
                        (optional)
                    </span>
                </p>
                <SourceChips
                    labelledBy={`${uid}-source-label`}
                    describedBy={`${uid}-source-hint`}
                    options={sources}
                    value={values.source}
                    onChange={(source) => onChange('source', source)}
                />
                <p
                    id={`${uid}-source-hint`}
                    className="text-xs text-muted-foreground"
                >
                    Where did this client find you?
                </p>
            </div>

            {values.source === 'other' && (
                <FormField
                    id={`${uid}-source-other`}
                    label="Other source"
                    optional
                    hint="In a few words, such as a fashion fair."
                    error={errors.source_other}
                    className="lg:max-w-[calc(50%-0.5rem)]"
                >
                    {(control) => (
                        <Input
                            {...control}
                            value={values.source_other}
                            autoComplete="off"
                            onChange={(event) =>
                                onChange('source_other', event.target.value)
                            }
                        />
                    )}
                </FormField>
            )}
        </div>
    );
}
