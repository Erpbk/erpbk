<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\VisaexpenseController;
use Carbon\Carbon;

class AutoMarkInstallments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'installments:auto-mark {riderId?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically mark installments as paid when their date equals today';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $riderId = $this->argument('riderId');

        $this->info('Starting auto-mark installments process...');

        $controller = new VisaexpenseController(app(\App\Repositories\VisaExpensesRepository::class));
        $updatedCount = $controller->autoMarkInstallmentsAsPaid($riderId);

        if ($updatedCount > 0) {
            $message = $riderId
                ? "Auto-marked {$updatedCount} installment(s) as paid for rider ID {$riderId}"
                : "Auto-marked {$updatedCount} installment(s) as paid across all riders";

            $this->info($message);
            \Log::info($message);
        } else {
            $message = $riderId
                ? "No installments to auto-mark for rider ID {$riderId}"
                : "No installments to auto-mark across all riders";

            $this->info($message);
        }

        return 0;
    }
}
