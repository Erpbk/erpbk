<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class StorageUrl
{
    /**
     * Build a browser URL for a file stored on the public or local disk.
     */
    public static function resolve(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $path = self::normalizePath($path);

        if (Storage::disk('public')->exists($path)) {
            return PublicStorageDisk::url($path) ?? asset('storage/' . $path);
        }

        if (Storage::disk('local')->exists($path)) {
            return url('storage2/' . $path);
        }

        if (Storage::disk('local')->exists('public/' . $path)) {
            return url('storage2/' . $path);
        }

        if (str_starts_with($path, 'public/')) {
            $trimmed = substr($path, 7);
            if (Storage::disk('public')->exists($trimmed)) {
                return PublicStorageDisk::url($trimmed) ?? asset('storage/' . $trimmed);
            }
        }

        if (! str_contains($path, '/')) {
            foreach (self::bareFilenamePrefixes() as $prefix) {
                $publicPath = $prefix . $path;
                if (Storage::disk('public')->exists($publicPath)) {
                    return PublicStorageDisk::url($publicPath) ?? asset('storage/' . $publicPath);
                }
                $localPath = 'public/' . $publicPath;
                if (Storage::disk('local')->exists($localPath)) {
                    return url('storage2/' . $publicPath);
                }
            }
        }

        return asset('storage/' . $path);
    }

    /**
     * @return list<string>
     */
    private static function bareFilenamePrefixes(): array
    {
        return [
            'uploads/',
            'uploads/thumbnail/',
            'vouchers/',
            'fines/',
            'fines/files/',
            'salik/files/',
            'profile/',
            'contract/',
            'rider/',
            'supplier/',
            'bike/',
            'fuelcards/',
        ];
    }

    public static function normalizePath(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, 8);
        }

        if (str_starts_with($path, 'app/')) {
            $path = substr($path, 4);
        }

        if (str_starts_with($path, 'public/')) {
            $path = substr($path, 7);
        }

        return $path;
    }
}
