<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Console\Migrations\MigrateCommand as FrameworkMigrateCommand;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Overrides the default `migrate` command so Laravel Cloud deploys migrate both DBs.
 */
class DeployMigrateCommand extends Command
{
  protected $signature = 'migrate
                {--database= : The database connection to use}
                {--force : Force the operation to run when in production}
                {--path=* : The path(s) to the migrations files to be executed}
                {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
                {--schema-path= : The path to a schema dump file}
                {--pretend : Dump the SQL queries that would be run}
                {--seed : Indicates if the seed task should be re-run}
                {--seeder= : The class name of the root seeder}
                {--step : Force the migrations to be run so they can be rolled back individually}';

  protected $description = 'Run central and admin database migrations';

  public function handle()
  {
    if ($this->hasExplicitTarget()) {
      return $this->runFrameworkMigrate();
    }

    $exitCode = Artisan::call('app:migrate-all', [
      '--force' => (bool) $this->option('force'),
    ]);
    $this->output->writeln(Artisan::output());

    return $exitCode;
  }

  protected function runFrameworkMigrate(): int
  {
    $command = new FrameworkMigrateCommand(app('migrator'), app('events'));
    $command->setLaravel($this->getLaravel());

    return $command->run($this->buildFrameworkInput(), $this->output);
  }

  protected function buildFrameworkInput(): ArrayInput
  {
    $options = [];

    foreach ([
      'database',
      'force',
      'path',
      'realpath',
      'schema-path',
      'pretend',
      'seed',
      'seeder',
      'step',
    ] as $name) {
      $value = $this->option($name);

      if ($value === null || $value === false || $value === []) {
        continue;
      }

      $options['--' . $name] = $value;
    }

    return new ArrayInput($options);
  }

  protected function hasExplicitTarget(): bool
  {
    if ($this->option('database')) {
      return true;
    }

    if ($this->input->getParameterOption('--path', false) !== false) {
      return true;
    }

    if (
      $this->option('realpath')
      || $this->option('schema-path')
      || $this->option('pretend')
      || $this->option('seed')
    ) {
      return true;
    }

    return false;
  }
}
