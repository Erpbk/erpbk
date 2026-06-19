<?php

namespace App\Console\Commands;

use App\Services\FixedAssets\FixedAssetDepreciationPostingService;
use Illuminate\Console\Command;

class PostFixedAssetDepreciation extends Command
{
    protected $signature = 'fixed-assets:post-depreciation {--date= : Post schedules due on or before this date (Y-m-d)}';

    protected $description = 'Post due fixed asset depreciation vouchers for pending schedule entries';

    public function handle(FixedAssetDepreciationPostingService $postingService): int
    {
        $asOfDate = $this->option('date');

        $this->info('Posting fixed asset depreciation entries' . ($asOfDate ? " due on or before {$asOfDate}" : ' due today') . '...');

        $postedCount = $postingService->postDueEntries($asOfDate);

        if ($postedCount > 0) {
            $message = "Posted {$postedCount} depreciation voucher(s).";
            $this->info($message);
            \Log::info($message);
        } else {
            $this->info('No depreciation entries were due for posting.');
        }

        return self::SUCCESS;
    }
}
