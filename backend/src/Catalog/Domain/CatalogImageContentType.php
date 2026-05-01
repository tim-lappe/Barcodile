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
            'image/jpeg', 'image/jpg', 'application/jpeg', 'image/pjpeg' => self::Jpeg,
            'image/png', 'image/x-png' => self::Png,
            'image/webp' => self::Webp,
            'image/gif' => self::Gif,
            default => null,
        };
    }

    public static function tryDetectFromImageBinary(string $body): ?self
    {
        $len = \strlen($body);
        if ($len < 3) {
            return null;
        }
        if (str_starts_with($body, "\xFF\xD8\xFF")) {
            return self::Jpeg;
        }
        if ($len >= 8 && str_starts_with($body, "\x89PNG\r\n\x1A\n")) {
            return self::Png;
        }
        if ($len >= 6 && (str_starts_with($body, 'GIF87a') || str_starts_with($body, 'GIF89a'))) {
            return self::Gif;
        }
        if ($len >= 12 && str_starts_with($body, 'RIFF') && 'WEBP' === substr($body, 8, 4)) {
            return self::Webp;
        }

        return null;
    }

    public static function tryFromUrlPath(string $url): ?self
    {
        $path = parse_url($url, \PHP_URL_PATH);
        if (!\is_string($path)) {
            return null;
        }
        $lower = strtolower($path);
        if (str_ends_with($lower, '.jpg') || str_ends_with($lower, '.jpeg')) {
            return self::Jpeg;
        }
        if (str_ends_with($lower, '.png')) {
            return self::Png;
        }
        if (str_ends_with($lower, '.webp')) {
            return self::Webp;
        }
        if (str_ends_with($lower, '.gif')) {
            return self::Gif;
        }

        return null;
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
