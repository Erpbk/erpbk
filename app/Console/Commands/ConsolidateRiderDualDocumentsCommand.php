<?php

namespace App\Console\Commands;

use App\Support\RiderDocumentDualConsolidation;
use Illuminate\Console\Command;

class ConsolidateRiderDualDocumentsCommand extends Command
{
    protected $signature = 'riders:consolidate-dual-documents
                            {--dry-run : Report changes without writing}';

    protected $description = 'Combine legacy separate front/back rider files under one dual document type (keeps all files)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->info('Dry run — no rows will be updated.');
        }

        $stats = RiderDocumentDualConsolidation::run($dryRun);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Dual types ensured', $stats['types_ensured']],
                ['Front/back subtypes ensured', $stats['subtypes_ensured']],
                ['Side-only types folded', $stats['side_types_folded']],
                ['Files retagged (kept on disk)', $stats['files_updated']],
                ['Files skipped', $stats['skipped']],
            ]
        );

        $this->info($dryRun
            ? 'Dry run complete. Re-run without --dry-run to apply.'
            : 'Existing front/back uploads are now linked under their dual document types.');

        return self::SUCCESS;
    }
}
