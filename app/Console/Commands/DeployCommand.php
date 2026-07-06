<?php

namespace App\Console\Commands;

use App\Support\PublicStorageLink;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class DeployCommand extends Command
{
    protected $signature = 'app:deploy
                {--force : Force migrations in production}
                {--optimize : Cache config, routes, and views for production}
                {--no-optimize : Skip production caching even in production}
                {--skip-migrate : Skip database migrations}
                {--check : Run app:deploy-check before deploying}';

    protected $description = 'Run post-upload deployment steps (migrations, caches, storage link).';

    public function handle(): int
    {
        $this->info('Starting deployment...');

        if ($this->option('check')) {
            $checkExit = Artisan::call('app:deploy-check');
            $this->output->writeln(Artisan::output());

            if ($checkExit !== self::SUCCESS) {
                $this->error('Deploy check failed. Aborting.');

                return self::FAILURE;
            }
        }

        $this->ensureStorageDirectories();
        PublicStorageLink::ensure();
        $this->info('Storage link verified.');

        if (! $this->option('skip-migrate')) {
            $force = (bool) $this->option('force') || app()->environment('production');
            $migrateExit = Artisan::call('app:migrate-all', ['--force' => $force]);
            $this->output->writeln(Artisan::output());

            if ($migrateExit !== self::SUCCESS) {
                $this->error('Migrations failed. Deployment aborted.');

                return self::FAILURE;
            }
        }

        foreach (['config:clear', 'cache:clear', 'route:clear', 'view:clear'] as $command) {
            Artisan::call($command);
        }
        $this->info('Application caches cleared.');

        if ($this->shouldOptimize()) {
            foreach (['config:cache', 'route:cache', 'view:cache'] as $command) {
                Artisan::call($command);
            }
            $this->info('Production caches built.');
        }

        $this->info('Deployment completed successfully.');

        return self::SUCCESS;
    }

    protected function shouldOptimize(): bool
    {
        if ($this->option('no-optimize')) {
            return false;
        }

        if ($this->option('optimize')) {
            return true;
        }

        return app()->environment('production');
    }

    protected function ensureStorageDirectories(): void
    {
        foreach ([
            storage_path('app/public/company-logos'),
            storage_path('app/public/auth-branding'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('framework/cache/data'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ] as $directory) {
            if (! File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
        }
    }
}
