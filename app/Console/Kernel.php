<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('fixed-assets:post-depreciation')->dailyAt('00:30');

        if (config('deploy.marker_enabled')) {
            $schedule->call(function () {
                $marker = storage_path('framework/deploy.pending');

                if (! is_file($marker)) {
                    return;
                }

                @unlink($marker);

                \Illuminate\Support\Facades\Artisan::call('app:deploy', [
                    '--force' => true,
                    '--optimize' => app()->environment('production'),
                ]);
            })->everyMinute()->name('deploy-marker')->withoutOverlapping();
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
