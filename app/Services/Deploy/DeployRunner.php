<?php

namespace App\Services\Deploy;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class DeployRunner
{
    public function runInBackground(): void
    {
        $php = (new PhpExecutableFinder())->find(false) ?: 'php';
        $artisan = base_path('artisan');
        $logFile = storage_path('logs/deploy.log');

        $command = sprintf(
            '%s %s app:deploy --force --optimize >> %s 2>&1',
            escapeshellarg($php),
            escapeshellarg($artisan),
            escapeshellarg($logFile)
        );

        if (PHP_OS_FAMILY === 'Windows') {
            $process = Process::fromShellCommandline('start /B "" ' . $command, base_path());
        } else {
            $process = Process::fromShellCommandline($command . ' &', base_path());
        }

        $process->run();

        Log::info('Deploy queued in background.', ['log' => $logFile]);
    }

    public function runSynchronously(): int
    {
        $exitCode = Artisan::call('app:deploy', [
            '--force' => true,
            '--optimize' => app()->environment('production'),
        ]);

        Log::info('Deploy finished.', [
            'exit_code' => $exitCode,
            'output' => Artisan::output(),
        ]);

        return $exitCode;
    }
}
