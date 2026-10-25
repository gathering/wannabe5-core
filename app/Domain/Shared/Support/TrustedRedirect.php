<?php

namespace App\Domain\Shared\Support;

class TrustedRedirect
{
    public static function sanitize(?string $url, string $fallback = '/'): string
    {
        if ($url === null || $url === '') {
            return $fallback;
        }

        $allowed = config('backend_compat.allowed_redirect_hosts', []);
        if ($allowed === []) {
            return $fallback;
        }

        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['host'])) {
            // Relative path redirects are allowed.
            if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
                return $url;
            }

            return $fallback;
        }

        $host = strtolower((string) $parts['host']);
        foreach ($allowed as $allowedHost) {
            if ($host === strtolower((string) $allowedHost)) {
                return $url;
            }
        }

        return $fallback;
    }
}
