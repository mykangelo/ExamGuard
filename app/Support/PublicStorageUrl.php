<?php

namespace App\Support;

class PublicStorageUrl
{
    /**
     * URL for a file on the public disk (avatars, violation snapshots).
     * Uses /storage/... which is served via public/storage/.htaccess on hosts without symlinks.
     */
    public static function for(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $normalized = str_replace('\\', '/', ltrim($path, '/'));

        return asset('storage/'.$normalized);
    }
}
