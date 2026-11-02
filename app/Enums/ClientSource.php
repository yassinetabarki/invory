<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where a client found the workshop (INV-4): a fixed list, the same for every workshop, in this order. "Other" may carry a short text
 * of its own (`clients.source_other`, up to 40 characters).
 */
enum ClientSource: string
{
    case Instagram = 'instagram';
    case Facebook = 'facebook';
    case TikTok = 'tiktok';
    case WhatsApp = 'whatsapp';
    case Website = 'website';
    case WalkIn = 'walk_in';
    case WordOfMouth = 'word_of_mouth';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Instagram => __('Instagram'),
            self::Facebook => __('Facebook'),
            self::TikTok => __('TikTok'),
            self::WhatsApp => __('WhatsApp'),
            self::Website => __('Website'),
            self::WalkIn => __('Walk-in / shop'),
            self::WordOfMouth => __('Word of mouth'),
            self::Other => __('Other'),
        };
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
