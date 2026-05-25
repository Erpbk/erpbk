<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class PublicStorageLink
{
    /**
     * Create public/storage when missing (required on Laravel Cloud deploys).
     */
    public static function ensure(): void
    {
        $link = public_path('storage');

        if (is_link($link) || file_exists($link)) {
            return;
        }

        if (! File::isDirectory(storage_path('app/public'))) {
            File::makeDirectory(storage_path('app/public'), 0755, true);
        }

        try {
            Artisan::call('storage:link');
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
