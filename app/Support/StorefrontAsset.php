<?php

namespace App\Support;

class StorefrontAsset
{
    public static function directUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $candidate = html_entity_decode($path, ENT_QUOTES | ENT_HTML5);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            if (preg_match('#https://igicanada\.ca(?=[:/?\#]|$)#i', $candidate, $match, PREG_OFFSET_CAPTURE)) {
                return self::browserSafeAbsoluteUrl(substr($candidate, $match[0][1]));
            }

            $decoded = rawurldecode($candidate);
            if ($decoded === $candidate) {
                break;
            }

            $candidate = $decoded;
        }

        return preg_match('#^https?://#i', $path) ? self::browserSafeAbsoluteUrl($path) : null;
    }

    public static function uploaded(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if ($directUrl = self::directUrl($path)) {
            return $directUrl;
        }

        if (str_starts_with($path, '/')) {
            return $path;
        }

        return '/storage/'.ltrim($path, '/');
    }

    public static function legacy(?string $path, string $folder = 'post'): ?string
    {
        if (blank($path)) {
            return null;
        }

        if ($directUrl = self::directUrl($path)) {
            return $directUrl;
        }

        $normalized = str_starts_with($path, '/') ? $path : "/upload/{$folder}/{$path}";

        return self::browserSafeAbsoluteUrl('https://igicanada.ca'.$normalized);
    }

    private static function browserSafeAbsoluteUrl(string $url): string
    {
        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return $url;
        }

        $path = implode('/', array_map(
            fn (string $segment): string => rawurlencode(rawurldecode($segment)),
            explode('/', $parts['path'] ?? ''),
        ));

        return $parts['scheme'].'://'.$parts['host']
            .(isset($parts['port']) ? ':'.$parts['port'] : '')
            .$path
            .(isset($parts['query']) ? '?'.$parts['query'] : '')
            .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
    }
}
