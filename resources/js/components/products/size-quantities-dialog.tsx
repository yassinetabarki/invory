import { useState } from 'react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

/** What the window says: the card's wording, fixed when the question is asked. */
export type SizeQuestion = {
    title: string;
    body: string;
    keep: string;
    confirm: string;
};

const BUTTON = 'h-11 sm:h-9';

/** "the 2 quantities", "the 1 quantity". */
function quantities(count: number): string {
    return `the ${count} ${count === 1 ? 'quantity' : 'quantities'}`;
}

/** A size in a sentence: "XL", or "size 36" for a name that is only digits. */
function inSentence(label: string): string {
    return /^\d+$/.test(label) ? `size ${label}` : label;
}

/** A size pressed off (or One size pressed off by another size): "Take XL off this product?". */
export function takeOffQuestion(label: string, count: number): SizeQuestion {
    const name = inSentence(label);

    return {
        title: `Take ${name} off this product?`,
        body: `This clears ${quantities(count)} you set for ${name} across its materials.`,
        keep: `Keep ${name}`,
        confirm: `Take ${name} off`,
    };
}

/**
 * Sizes replaced as a whole: "Use One size?" or "Use the sizes of Footwear?".
 * `use` completes the title, `button` completes the confirm button.
 */
export function replaceQuestion(
    use: string,
    button: string,
    count: number,
): SizeQuestion {
    return {
        title: `Use ${use}?`,
        body: `This replaces the sizes and clears ${quantities(count)} you set across its materials.`,
        keep: 'Keep the sizes',
        confirm: `Use ${button}`,
    };
}

/**
 * The question asked before a change of the Sizes field clears typed quantities.
 * Keep (focused; Esc and any other way out do the same) changes nothing; the
 * red button runs `onConfirm`. Pass `null` to close it.
 */
export function SizeQuantitiesDialog({
    question,
    onKeep,
    onConfirm,
}: {
    question: SizeQuestion | null;
    onKeep: () => void;
    onConfirm: () => void;
}) {
    // The words stay while the window fades out.
    const [last, setLast] = useState(question);

    if (question && question !== last) {
        setLast(question);
    }

    const shown = question ?? last;

    return (
        <AlertDialog
            open={question !== null}
            onOpenChange={(open) => {
                if (!open) {
                    onKeep();
                }
            }}
        >
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{shown?.title}</AlertDialogTitle>
                    <AlertDialogDescription>
                        {shown?.body}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel className={BUTTON}>
                        {shown?.keep}
                    </AlertDialogCancel>
                    <AlertDialogAction
                        variant="destructive"
                        className={BUTTON}
                        onClick={onConfirm}
                    >
                        {shown?.confirm}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
