<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

enum CatalogImageContentType: string
{
    case Jpeg = 'image/jpeg';
    case Png = 'image/png';
    case Webp = 'image/webp';
    case Gif = 'image/gif';

    public static function tryFromMimeType(string $mime): ?self
    {
        $normalized = strtolower(trim($mime));

        return match ($normalized) {
            'image/jpeg', 'image/jpg' => self::Jpeg,
            'image/png' => self::Png,
            'image/webp' => self::Webp,
            'image/gif' => self::Gif,
            default => null,
        };
    }

    public function preferredExtension(): string
    {
        return match ($this) {
            self::Jpeg => 'jpg',
            self::Png => 'png',
            self::Webp => 'webp',
            self::Gif => 'gif',
        };
    }
}
