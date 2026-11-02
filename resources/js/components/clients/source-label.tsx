import {
    Camera,
    Ellipsis,
    Globe,
    MessageCircle,
    MessagesSquare,
    Music,
    Store,
    ThumbsUp,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { ClientRef, ClientSource } from '@/types/domain';

/** One icon per source (INV-4): a hint beside the word, never a brand logo or colour. */
export const SOURCE_ICONS: Record<ClientSource, LucideIcon> = {
    instagram: Camera,
    facebook: ThumbsUp,
    tiktok: Music,
    whatsapp: MessageCircle,
    website: Globe,
    walk_in: Store,
    word_of_mouth: MessagesSquare,
    other: Ellipsis,
};

/** The words of a client's source: "Instagram", "Other · Fashion fair Sousse"; null with no source. */
export function sourceText(
    client: Pick<ClientRef, 'source_label' | 'source_other'>,
): string | null {
    if (!client.source_label) {
        return null;
    }

    return client.source_other
        ? `${client.source_label} · ${client.source_other}`
        : client.source_label;
}

type SourceLabelProps = {
    client: Pick<ClientRef, 'source' | 'source_label' | 'source_other'>;
    className?: string;
};

/**
 * Where a client found the workshop (INV-4, AC-1): a small rounded grey label,
 * the same tone for every source, with an icon and the word. Screen readers
 * read the word only. A client with no source shows nothing.
 */
export function SourceLabel({ client, className }: SourceLabelProps) {
    const text = sourceText(client);

    if (!client.source || !text) {
        return null;
    }

    const Icon = SOURCE_ICONS[client.source];

    return (
        <span
            className={cn(
                'inline-flex max-w-full min-w-0 items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground',
                className,
            )}
        >
            <Icon className="size-3 shrink-0" aria-hidden="true" />
            <span className="truncate">{text}</span>
        </span>
    );
}
