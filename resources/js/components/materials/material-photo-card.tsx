import { Link } from '@inertiajs/react';
import { PhotoThumb } from '@/components/shared/photo-thumb';
import { Button } from '@/components/ui/button';

type MaterialPhotoCardProps = {
    name: string;
    photoUrl: string | null;
    /** The edit page, where the photo is added or replaced. */
    editHref: string;
};

/** The material's photo (square), or a placeholder, with a link to change it. */
export function MaterialPhotoCard({
    name,
    photoUrl,
    editHref,
}: MaterialPhotoCardProps) {
    return (
        <figure className="m-0 flex w-full max-w-sm flex-col gap-2.5 rounded-xl border bg-card p-3 shadow-sm">
            <PhotoThumb
                src={photoUrl}
                alt={`Photo of ${name}`}
                size="lg"
                shape="square"
                className="rounded-lg"
            />
            <figcaption className="flex flex-wrap items-center justify-between gap-2">
                <span className="text-xs text-muted-foreground">
                    JPG, PNG or WebP · max 4 MB
                </span>
                <Button asChild variant="outline" size="sm">
                    <Link href={editHref}>
                        {photoUrl === null ? 'Add photo' : 'Replace photo'}
                    </Link>
                </Button>
            </figcaption>
        </figure>
    );
}
