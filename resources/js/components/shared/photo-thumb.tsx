import { ImageIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

export type PhotoThumbSize = 'sm' | 'md' | 'lg';
export type PhotoShape = 'square' | 'portrait';

type PhotoThumbProps = {
    /** Public URL of the photo, or null when the item has none. */
    src: string | null;
    /**
     * What the photo shows. Pass an empty string when the item's name is
     * right next to the thumbnail (table rows), so it is not announced twice.
     * Only used when there is a photo: the placeholder is decorative.
     */
    alt: string;
    /** `sm` 36 px, `md` 40 px, `lg` fills the width of its container. */
    size: PhotoThumbSize;
    /** `square` 1:1 (default) or `portrait` 4:5. */
    shape?: PhotoShape;
    className?: string;
};

const WIDTH: Record<PhotoThumbSize, string> = {
    sm: 'w-9',
    md: 'w-10',
    lg: 'w-full',
};

const ICON: Record<PhotoThumbSize, string> = {
    sm: 'size-4',
    md: 'size-5',
    lg: 'size-10',
};

/**
 * A photo cropped to its box (`object-cover`) with rounded corners, or a
 * neutral tile with an image icon when there is no photo.
 */
export function PhotoThumb({
    src,
    alt,
    size,
    shape = 'square',
    className,
}: PhotoThumbProps) {
    const box = cn(
        'shrink-0 rounded-md border',
        WIDTH[size],
        shape === 'portrait' ? 'aspect-[4/5]' : 'aspect-square',
        className,
    );

    if (src === null) {
        return (
            <div
                data-slot="photo-thumb"
                data-empty="true"
                className={cn(
                    box,
                    'flex items-center justify-center bg-muted text-muted-foreground',
                )}
            >
                <ImageIcon className={ICON[size]} aria-hidden="true" />
            </div>
        );
    }

    return (
        <img
            data-slot="photo-thumb"
            src={src}
            alt={alt}
            loading="lazy"
            decoding="async"
            className={cn(box, 'bg-muted object-cover')}
        />
    );
}
