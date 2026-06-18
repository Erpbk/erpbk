<?php

namespace App\Support;

use Illuminate\Database\Console\Migrations\MigrateCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateConnectionRunner
{
    public static function run(
        string $connection,
        string $path,
        bool $force,
        ?OutputInterface $output = null
    ): int {
        // Migrations (especially company_id repair) can exceed the default 512M web/CLI limit.
        if (self::parseMemoryLimitBytes(ini_get('memory_limit')) < 1024 * 1024 * 1024) {
            @ini_set('memory_limit', '1024M');
        }

        DeployDatabaseConfig::refreshFromEnvironment();

        if (! DeployDatabaseWaiter::waitForConnection($connection)) {
            $message = DeployDatabaseWaiter::connectionFailureMessage($connection);
            if ($output !== null) {
                $output->writeln('<error>' . $message . '</error>');
            }

            return 1;
        }

        $command = new MigrateCommand(app('migrator'), app('events'));
        $command->setLaravel(app());

        $options = [
            '--database' => $connection,
            '--path' => $path,
        ];

        if ($force) {
            $options['--force'] = true;
        }

        return $command->run(new ArrayInput($options), $output ?? new \Symfony\Component\Console\Output\BufferedOutput());
    }

    private static function parseMemoryLimitBytes(string|false $limit): int
    {
        if ($limit === false || $limit === '' || $limit === '-1') {
            return PHP_INT_MAX;
        }

        $limit = trim((string) $limit);
        $unit = strtolower(substr($limit, -1));
        $value = (int) $limit;

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }
}
