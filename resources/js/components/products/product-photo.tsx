import { PhotoThumb } from '@/components/shared/photo-thumb';

type ProductPhotoProps = {
    name: string;
    photoUrl: string | null;
    description: string | null;
};

/** The product's portrait photo (or a placeholder) with its description as the caption. */
export function ProductPhoto({
    name,
    photoUrl,
    description,
}: ProductPhotoProps) {
    return (
        <figure className="m-0 flex w-full max-w-xs flex-col gap-2.5 rounded-xl border bg-card p-3 text-card-foreground lg:max-w-80">
            <PhotoThumb
                src={photoUrl}
                alt={`Photo of ${name}`}
                size="lg"
                shape="portrait"
                className="rounded-lg"
            />
            {description && (
                <figcaption className="text-[0.8125rem] text-muted-foreground">
                    {description}
                </figcaption>
            )}
        </figure>
    );
}
