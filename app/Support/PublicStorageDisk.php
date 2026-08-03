<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class PublicStorageDisk
{
    /**
     * Whether uploads on the public disk are stored on object storage (S3 / R2).
     */
    public static function isCloud(): bool
    {
        return config('filesystems.disks.public.driver') === 's3';
    }

    /**
     * Whether the current public disk uses ephemeral local storage (typical on Laravel Cloud).
     */
    public static function isEphemeralLocal(): bool
    {
        return ! self::isCloud() && app()->environment('production');
    }

    public static function disk()
    {
        return Storage::disk('public');
    }

    public static function exists(?string $path): bool
    {
        $path = self::normalize($path);

        return $path !== '' && self::disk()->exists($path);
    }

    /**
     * Browser URL for a file on the public disk (local route or cloud CDN URL).
     */
    public static function url(?string $path): ?string
    {
        $path = self::normalize($path);
        if ($path === '' || ! self::disk()->exists($path)) {
            return null;
        }

        if (self::isCloud()) {
            return self::disk()->url($path);
        }

        return url('/storage/' . $path);
    }

    /**
     * Readable filesystem path for inline email embedding (downloads cloud files to a temp file).
     */
    public static function readablePath(?string $path): ?string
    {
        $path = self::normalize($path);
        if ($path === '' || ! self::disk()->exists($path)) {
            return null;
        }

        if (! self::isCloud()) {
            $fullPath = self::disk()->path($path);

            return is_readable($fullPath) ? $fullPath : null;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'public_upload_');
        if ($tmp === false) {
            return null;
        }

        $written = file_put_contents($tmp, self::disk()->get($path));
        if ($written === false) {
            @unlink($tmp);

            return null;
        }

        return $tmp;
    }

    public static function normalize(?string $path): string
    {
        if ($path === null || trim($path) === '') {
            return '';
        }

        return StorageUrl::normalizePath($path);
    }

    /**
     * @return array<string, mixed>
     */
    public static function putOptions(): array
    {
        return self::isCloud() ? ['visibility' => 'public'] : [];
    }

    public static function put(string $path, string $contents): bool
    {
        $path = self::normalize($path);
        if ($path === '') {
            return false;
        }

        return self::disk()->put($path, $contents, self::putOptions());
    }

    public static function storeUploadedFile(
        \Illuminate\Http\UploadedFile $file,
        string $directory,
        ?string $name = null
    ): string {
        $directory = trim($directory, '/');
        $storedName = $name ?? $file->hashName();
        $options = array_merge(['disk' => 'public'], self::putOptions());

        return $file->storeAs($directory, $storedName, $options);
    }

    public static function delete(?string $path): void
    {
        $path = self::normalize($path);
        if ($path !== '' && self::disk()->exists($path)) {
            self::disk()->delete($path);
        }
    }
}
