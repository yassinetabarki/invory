import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

type MaterialFormActionsProps = {
    processing: boolean;
    submitLabel: string;
    cancelHref?: string;
};

/**
 * Cancel and Save. Rendered twice on the material pages (header and bottom of
 * the form); both submit the same `<form>`, so place them inside it.
 */
export function MaterialFormActions({
    processing,
    submitLabel,
    cancelHref,
}: MaterialFormActionsProps) {
    return (
        <>
            {cancelHref && (
                <Button asChild variant="outline">
                    <Link href={cancelHref}>Cancel</Link>
                </Button>
            )}
            <Button type="submit" disabled={processing}>
                {processing && <Spinner />}
                {submitLabel}
            </Button>
        </>
    );
}
