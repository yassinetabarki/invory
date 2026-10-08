<?php

declare(strict_types=1);

namespace App\Enums;

enum MaterialCategory: string
{
    case Fabric = 'fabric';
    case Lining = 'lining';
    case Interlining = 'interlining';
    case Leather = 'leather';
    case Thread = 'thread';
    case Trim = 'trim';
    case Fastener = 'fastener';
    case Label = 'label';
    case Sole = 'sole';
    case Hardware = 'hardware';
    case Packaging = 'packaging';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Fabric => __('Fabric'),
            self::Lining => __('Lining'),
            self::Interlining => __('Interlining'),
            self::Leather => __('Leather'),
            self::Thread => __('Thread'),
            self::Trim => __('Trim'),
            self::Fastener => __('Fastener'),
            self::Label => __('Label'),
            self::Sole => __('Sole'),
            self::Hardware => __('Hardware'),
            self::Packaging => __('Packaging'),
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
