import type {
    ClientRef,
    ClientSource,
    ClientSummary,
    Option,
} from '@/types/domain';

/** The eight sources, in order, as the server sends them. */
export const clientSources: Option<ClientSource>[] = [
    { value: 'instagram', label: 'Instagram' },
    { value: 'facebook', label: 'Facebook' },
    { value: 'tiktok', label: 'TikTok' },
    { value: 'whatsapp', label: 'WhatsApp' },
    { value: 'website', label: 'Website' },
    { value: 'walk_in', label: 'Walk-in / shop' },
    { value: 'word_of_mouth', label: 'Word of mouth' },
    { value: 'other', label: 'Other' },
];

/** INV-4's example clients (the card's table). */
export const amira: ClientSummary = {
    id: 1,
    name: 'Amira Ben Salah',
    phone: '22 345 678',
    city: 'Sousse',
    source: 'instagram',
    source_label: 'Instagram',
    source_other: null,
    orders: 3,
    open_orders: 1,
    last_order_on: '2026-10-28',
};

export const boutiqueLina: ClientSummary = {
    id: 2,
    name: 'Boutique Lina',
    phone: '71 234 567',
    city: 'Tunis',
    source: 'walk_in',
    source_label: 'Walk-in / shop',
    source_other: null,
    orders: 3,
    open_orders: 1,
    last_order_on: '2026-10-15',
};

export const mehdi: ClientSummary = {
    id: 3,
    name: 'Mehdi Jaziri',
    phone: '29 111 222',
    city: 'Ariana',
    source: 'whatsapp',
    source_label: 'WhatsApp',
    source_other: null,
    orders: 2,
    open_orders: 0,
    last_order_on: '2026-10-12',
};

export const ines: ClientSummary = {
    id: 7,
    name: 'Ines Mrad',
    phone: '20 456 789',
    city: 'Monastir',
    source: null,
    source_label: null,
    source_other: null,
    orders: 0,
    open_orders: 0,
    last_order_on: null,
};

/** Just who a client is (a picker option, an order's client). */
export function ref(client: ClientSummary): ClientRef {
    return {
        id: client.id,
        name: client.name,
        phone: client.phone,
        city: client.city,
        source: client.source,
        source_label: client.source_label,
        source_other: client.source_other,
    };
}
