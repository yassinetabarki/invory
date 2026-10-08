import { ImageUp, RotateCcw, Trash2, Upload } from 'lucide-react';
import { useEffect, useId, useRef, useState } from 'react';
import type { ChangeEvent, DragEvent } from 'react';
import { PhotoThumb } from '@/components/shared/photo-thumb';
import type { PhotoShape } from '@/components/shared/photo-thumb';
import { Button, buttonVariants } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

const ACCEPTED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
const EXTENSION_PATTERN = /\.(jpe?g|png|webp)$/i;
/** 4 MB, the same limit as the server (4096 KB). The server stays authoritative. */
const MAX_BYTES = 4 * 1024 * 1024;

type PhotoFieldProps = {
    /** Name of the native file input (what a `<Form>` submits). */
    name?: string;
    /** Name of the hidden input that carries `1` / `0` for "remove the current photo". */
    removeName?: string;
    label: string;
    /** URL of the photo the item has now, or null. */
    currentUrl: string | null;
    /** Server validation error for this field. */
    error?: string;
    aspect: PhotoShape;
    /** For `useForm`: the picked file, or null when none is selected. */
    onChange?: (file: File | null) => void;
    /** For `useForm`: whether the current photo is marked for removal. */
    onRemoveChange?: (remove: boolean) => void;
};

/** A quick pre-check so a wrong file is caught before upload. */
function photoProblem(file: File): string | null {
    const typeIsAllowed =
        ACCEPTED_TYPES.includes(file.type) ||
        (file.type === '' && EXTENSION_PATTERN.test(file.name));

    if (!typeIsAllowed) {
        return 'Choose a JPG, PNG or WebP image.';
    }

    if (file.size > MAX_BYTES) {
        return 'The photo must be 4 MB or smaller.';
    }

    return null;
}

/** Puts a dropped file into the native input so a `<Form>` submits it. */
function putFileInInput(input: HTMLInputElement, file: File): void {
    if (typeof DataTransfer === 'undefined') {
        return;
    }

    const transfer = new DataTransfer();
    transfer.items.add(file);
    input.files = transfer.files;
}

/**
 * Photo picker for material and product forms.
 *
 * Works in both form styles:
 * - Inertia `<Form>`: the native `name`d file input and a hidden `remove_photo`
 *   input (`1` / `0`) are submitted with the form (multipart when a file is set).
 * - `useForm`: pass `onChange` / `onRemoveChange` and keep `photo` and
 *   `remove_photo` in the form data.
 *
 * Choose by click, keyboard (the file input is focusable, Enter or Space opens
 * the picker) or drag and drop. A picked file shows a preview (an object URL,
 * revoked when replaced or unmounted). Remove marks the current photo for
 * deletion and can be undone before saving.
 */
export function PhotoField({
    name = 'photo',
    removeName = 'remove_photo',
    label,
    currentUrl,
    error,
    aspect,
    onChange,
    onRemoveChange,
}: PhotoFieldProps) {
    const uid = useId();
    const inputId = `${uid}-input`;
    const labelId = `${uid}-label`;
    const hintId = `${uid}-hint`;
    const errorId = `${uid}-error`;
    const inputRef = useRef<HTMLInputElement>(null);

    const [picked, setPicked] = useState<{ file: File; url: string } | null>(
        null,
    );
    const [remove, setRemove] = useState(false);
    const [clientError, setClientError] = useState<string | null>(null);
    const [dragging, setDragging] = useState(false);

    // Free the preview's object URL once it is replaced or the field unmounts.
    const pickedUrl = picked?.url;

    useEffect(() => {
        return () => {
            if (pickedUrl) {
                URL.revokeObjectURL(pickedUrl);
            }
        };
    }, [pickedUrl]);

    const shownUrl = picked ? picked.url : remove ? null : currentUrl;
    const shownError = clientError ?? error;

    function clearInput() {
        if (inputRef.current) {
            inputRef.current.value = '';
        }
    }

    function selectFile(file: File | null) {
        const problem = file ? photoProblem(file) : null;

        if (file === null || problem !== null) {
            clearInput();
            setPicked(null);
            setClientError(problem);
            onChange?.(null);

            return;
        }

        setClientError(null);
        setPicked({ file, url: URL.createObjectURL(file) });

        if (remove) {
            setRemove(false);
            onRemoveChange?.(false);
        }

        onChange?.(file);
    }

    function handleInputChange(event: ChangeEvent<HTMLInputElement>) {
        selectFile(event.target.files?.item(0) ?? null);
    }

    function handleRemove() {
        clearInput();
        setClientError(null);

        if (picked) {
            setPicked(null);
            onChange?.(null);
        }

        // Only an existing photo needs a removal flag; a new pick just goes away.
        if (currentUrl !== null) {
            setRemove(true);
            onRemoveChange?.(true);
        }
    }

    function handleUndoRemove() {
        setRemove(false);
        onRemoveChange?.(false);
    }

    function handleDragOver(event: DragEvent<HTMLDivElement>) {
        event.preventDefault();
        setDragging(true);
    }

    function handleDragLeave(event: DragEvent<HTMLDivElement>) {
        // Moving between children of the field is not leaving it.
        if (
            !(event.relatedTarget instanceof Node) ||
            !event.currentTarget.contains(event.relatedTarget)
        ) {
            setDragging(false);
        }
    }

    function handleDrop(event: DragEvent<HTMLDivElement>) {
        event.preventDefault();
        setDragging(false);

        const file = event.dataTransfer.files.item(0);

        if (file === null) {
            return;
        }

        if (inputRef.current) {
            putFileInInput(inputRef.current, file);
        }

        selectFile(file);
    }

    return (
        <div
            data-slot="photo-field"
            data-dragging={dragging || undefined}
            className="group/photo flex flex-col gap-2"
            onDragEnter={handleDragOver}
            onDragOver={handleDragOver}
            onDragLeave={handleDragLeave}
            onDrop={handleDrop}
        >
            <Label id={labelId} htmlFor={inputId}>
                {label}
            </Label>

            <input
                ref={inputRef}
                id={inputId}
                type="file"
                name={name}
                accept={ACCEPTED_TYPES.join(',')}
                className="sr-only"
                aria-labelledby={labelId}
                aria-describedby={shownError ? `${hintId} ${errorId}` : hintId}
                aria-invalid={shownError ? true : undefined}
                onChange={handleInputChange}
            />
            <input type="hidden" name={removeName} value={remove ? '1' : '0'} />

            {shownUrl !== null ? (
                <div className="flex flex-col gap-2">
                    <PhotoThumb
                        src={shownUrl}
                        alt={picked ? 'New photo preview' : 'Current photo'}
                        size="lg"
                        shape={aspect}
                        className={cn(dragging && 'border-primary')}
                    />
                    {picked && (
                        <p className="truncate text-xs text-muted-foreground">
                            {picked.file.name}
                        </p>
                    )}
                    <div className="flex flex-wrap gap-2">
                        <label
                            htmlFor={inputId}
                            className={cn(
                                buttonVariants({ variant: 'outline' }),
                                'flex-1 cursor-pointer group-has-[input:focus-visible]/photo:border-ring group-has-[input:focus-visible]/photo:ring-[3px] group-has-[input:focus-visible]/photo:ring-ring/50',
                            )}
                        >
                            <Upload aria-hidden="true" />
                            Replace photo
                        </label>
                        <Button
                            type="button"
                            variant="outline"
                            aria-label="Remove photo"
                            onClick={handleRemove}
                        >
                            <Trash2 aria-hidden="true" />
                            Remove
                        </Button>
                    </div>
                </div>
            ) : (
                <>
                    <label
                        htmlFor={inputId}
                        className={cn(
                            'flex w-full cursor-pointer flex-col items-center justify-center gap-2.5 rounded-lg border-2 border-dashed border-input bg-background p-5 text-center transition-colors group-has-[input:focus-visible]/photo:border-ring group-has-[input:focus-visible]/photo:ring-[3px] group-has-[input:focus-visible]/photo:ring-ring/50 hover:bg-accent',
                            aspect === 'portrait'
                                ? 'aspect-[4/5]'
                                : 'aspect-square',
                            dragging && 'border-primary bg-info',
                        )}
                    >
                        <span className="flex size-12 items-center justify-center rounded-xl bg-info text-info-foreground">
                            <ImageUp className="size-6" aria-hidden="true" />
                        </span>
                        <span className="text-sm font-semibold">
                            Drop a photo here or{' '}
                            <span className="text-primary underline underline-offset-2">
                                browse
                            </span>
                        </span>
                    </label>
                    {remove && (
                        <p className="flex flex-wrap items-center gap-x-2 text-sm text-muted-foreground">
                            The current photo will be removed when you save.
                            <Button
                                type="button"
                                variant="link"
                                size="sm"
                                className="h-auto p-0"
                                onClick={handleUndoRemove}
                            >
                                <RotateCcw aria-hidden="true" />
                                Keep current photo
                            </Button>
                        </p>
                    )}
                </>
            )}

            <p id={hintId} className="text-xs text-muted-foreground">
                JPG, PNG or WebP · max 4 MB
            </p>
            {shownError && (
                <p
                    id={errorId}
                    role="alert"
                    className="text-sm text-destructive-foreground"
                >
                    {shownError}
                </p>
            )}
        </div>
    );
}
