<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUrlResolver
{
    /**
     * Resolve any image value into a usable URL.
     *
     * Handles:
     *  - Full URLs        (https://example.com/a.png)   → passthrough
     *  - Base64 data URIs (data:image/png;base64,...)   → passthrough
     *  - Absolute paths   (/storage/foo.png)            → passthrough
     *  - Storage paths    (logos/foo.png)               → Storage::url()
     *  - Bare filenames   (foo.png)                     → asset('storage/foo.png')
     *  - null / empty                                   → returns $fallback
     */
    public static function resolve(?string $path, ?string $fallback = null, string $disk = 'public'): ?string
    {
        if (empty($path)) {
            return $fallback;
        }

        $path = trim($path);

        // 1. Full URLs
        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        // 2. Base64 data URIs
        if (Str::startsWith($path, 'data:')) {
            return $path;
        }

        // 3. Absolute paths (already rooted at /)
        if (Str::startsWith($path, '/')) {
            return $path;
        }

        // 4. Storage path — try to resolve via the disk
        try {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->url($path);
            }
        } catch (\Throwable $e) {
            // Disk not configured — fall through to asset()
        }

        // 5. Fallback: treat as a public asset path
        return asset('storage/' . ltrim($path, '/'));
    }

    /**
     * Resolve with a "has a real image?" check.
     */
    public static function resolveOrNull(?string $path, string $disk = 'public'): ?string
    {
        if (empty($path)) {
            return null;
        }

        return self::resolve($path, null, $disk);
    }

    /**
     * Build a ui-avatars placeholder from a name/code.
     */
    public static function avatar(string $name, string $bg = 'f59e0b', string $fg = '0c0a09', int $size = 256): string
    {
        return 'https://ui-avatars.com/api/?name=' . urlencode($name)
            . "&size={$size}&background={$bg}&color={$fg}&bold=true&font-size=0.35";
    }

    /**
     * Inline SVG placeholder (no network needed).
     */
    public static function svgPlaceholder(string $stroke = '#d2964b'): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="' . $stroke . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
            . '<path d="M3 9l1-5h16l1 5"/><path d="M4 9v11h16V9"/><path d="M9 22V12h6v10"/></svg>';

        return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
    }
}