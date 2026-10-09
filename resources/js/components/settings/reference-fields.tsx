import { FormField } from '@/components/materials/form-field';
import { Input } from '@/components/ui/input';

type FieldProps = {
    /** Id of the control; the hint and error ids derive from it. */
    id: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
};

/** The code is typed once, when the row is created, and never changes. */
export function CodeField({
    id,
    value,
    onChange,
    error,
    maxLength,
}: FieldProps & { maxLength: number }) {
    return (
        <FormField
            id={id}
            label="Code"
            required
            hint={`Lowercase letters, digits and _ only, up to ${maxLength} characters. It can't be changed later.`}
            error={error}
        >
            {(control) => (
                <Input
                    {...control}
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    maxLength={maxLength}
                    autoComplete="off"
                    autoCapitalize="none"
                    spellCheck={false}
                    className="font-mono"
                />
            )}
        </FormField>
    );
}

export function NameField({ id, value, onChange, error }: FieldProps) {
    return (
        <FormField id={id} label="Name" required error={error}>
            {(control) => (
                <Input
                    {...control}
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    maxLength={64}
                    autoComplete="off"
                />
            )}
        </FormField>
    );
}

export function PositionField({ id, value, onChange, error }: FieldProps) {
    return (
        <FormField
            id={id}
            label="Position"
            optional
            hint="Lower numbers are listed first. Leave empty to list it last."
            error={error}
        >
            {(control) => (
                <Input
                    {...control}
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    inputMode="numeric"
                    autoComplete="off"
                    placeholder="—"
                    className="sm:w-32"
                />
            )}
        </FormField>
    );
}
