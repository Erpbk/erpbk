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
        DeployDatabaseConfig::refreshFromEnvironment();

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
}
