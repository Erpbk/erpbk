<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DeletionCascade;
use App\Models\Banks;
use App\Models\Accounts;

class TestCascadeDisplay extends Command
{
    protected $signature = 'test:cascade';
    protected $description = 'Test cascade deletion data and display';

    public function handle()
    {
        $this->info('=== TESTING CASCADE DELETION TRACKING ===');
        $this->newLine();

        // Test 1: Check deletion_cascades table
        $this->info('1. Checking deletion_cascades table...');
        $totalCascades = DeletionCascade::count();
        $this->line("   Total cascade records: {$totalCascades}");

        if ($totalCascades > 0) {
            $this->newLine();
            $this->info('   Latest 5 cascade records:');
            $cascades = DeletionCascade::orderBy('created_at', 'desc')->limit(5)->get();

            foreach ($cascades as $cascade) {
                $this->line("   - ID: {$cascade->id}");
                $this->line("     Primary: {$cascade->primary_model} #{$cascade->primary_id} ({$cascade->primary_name})");
                $this->line("     Related: {$cascade->related_model} #{$cascade->related_id} ({$cascade->related_name})");
                $this->line("     Relationship: {$cascade->relationship_type} -> {$cascade->relationship_name}");
                $this->line("     Created: {$cascade->created_at}");
                $this->newLine();
            }
        } else {
            $this->warn('   No cascade records found!');
            $this->line('   Try deleting a bank to create cascade records.');
        }

        // Test 2: Check soft deleted banks
        $this->info('2. Checking soft deleted banks...');
        $trashedBanks = Banks::onlyTrashed()->get();
        $this->line("   Total trashed banks: {$trashedBanks->count()}");

        if ($trashedBanks->count() > 0) {
            foreach ($trashedBanks as $bank) {
                $this->line("   - Bank #{$bank->id}: {$bank->name}");

                // Check cascades FOR this bank
                $cascadedTo = DeletionCascade::getCascadedDeletions('App\Models\Banks', $bank->id);
                $this->line("     Cascaded to: {$cascadedTo->count()} record(s)");

                if ($cascadedTo->count() > 0) {
                    foreach ($cascadedTo as $cascade) {
                        $this->line("       └─ {$cascade->related_model} #{$cascade->related_id} ({$cascade->related_name})");
                    }
                }
            }
        }

        $this->newLine();

        // Test 3: Check soft deleted accounts
        $this->info('3. Checking soft deleted accounts...');
        $trashedAccounts = Accounts::onlyTrashed()->get();
        $this->line("   Total trashed accounts: {$trashedAccounts->count()}");

        if ($trashedAccounts->count() > 0) {
            foreach ($trashedAccounts as $account) {
                $this->line("   - Account #{$account->id}: {$account->name}");

                // Check what CAUSED this account deletion
                $causedBy = DeletionCascade::getPrimaryDeletion('App\Models\Accounts', $account->id);

                if ($causedBy) {
                    $this->line("     Caused by: {$causedBy->primary_model} #{$causedBy->primary_id} ({$causedBy->primary_name})");
                } else {
                    $this->line("     Caused by: NONE (deleted directly)");
                }
            }
        }

        $this->newLine();

        // Test 4: Simulate what TrashController does
        $this->info('4. Simulating TrashController query...');
        $this->line("   Testing Banks::class = " . Banks::class);
        $this->line("   Testing Accounts::class = " . Accounts::class);

        if ($trashedBanks->count() > 0) {
            $testBank = $trashedBanks->first();
            $this->newLine();
            $this->line("   Test Bank: #{$testBank->id} - {$testBank->name}");

            $causedBy = DeletionCascade::getPrimaryDeletion(Banks::class, $testBank->id);
            $cascadedTo = DeletionCascade::getCascadedDeletions(Banks::class, $testBank->id);

            $this->line("   - caused_by: " . ($causedBy ? "YES (from {$causedBy->primary_model})" : "NO"));
            $this->line("   - cascaded_to: {$cascadedTo->count()} records");

            if ($cascadedTo->count() > 0) {
                foreach ($cascadedTo as $c) {
                    $this->line("     └─ {$c->related_model} #{$c->related_id}");
                }
            }
        }

        $this->newLine();
        $this->info('=== TEST COMPLETE ===');
        $this->line('If you see cascade records but they\'re not showing in the Recycle Bin:');
        $this->line('1. Make sure APP_DEBUG=true in .env');
        $this->line('2. Visit /trash page and check the debug section');
        $this->line('3. Check browser console for JavaScript errors');

        return 0;
    }
}
