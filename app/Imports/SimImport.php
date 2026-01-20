<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Sims;
use App\Models\SimHistory;
use App\Models\Riders;

class SimImport implements ToCollection
{
    protected $results = [];

    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        try {

            $stats = [
                'total' => 0,
                'imported' => 0,
                'failed' => 0,
                'duplicate_excel' => 0,
                'duplicate_db' => 0,
                'missing_data' => 0,
            ];

            $failedSims = [];
            $processedSims = [];
            $importedSimIds = [];

            $newSims = $rows->skip(1)->pluck(0)->filter()->toArray();
            $existingSims = Sims::whereIn('number', $newSims)->pluck('number')->toArray();
            $existingSims = array_flip($existingSims);

            foreach ($rows->skip(1) as $row) {

                if($row->every(function ($value) { return empty($value) || $value === '';})) {
                    break;
                }

                try {
                    $stats['total']++;
                    $simNumber  = trim($row[0] ?? '');
                    $company    = trim($row[1] ?? '');
                    $emi        = trim($row[2] ?? '');
                    $vendor     = trim($row[3] ?? '');

                    if (empty($simNumber) || empty($company) || empty($vendor) || empty($emi)) {
                        $stats['missing_data']++;
                        $failedSims[] = $this->createFailureEntry($stats['total'], $simNumber, $company, $vendor, $emi, 'Missing required fields');
                        continue;
                    }

                    if (in_array($simNumber, $processedSims)) {
                        $stats['duplicate_excel']++;
                        $failedSims[] = $this->createFailureEntry($stats['total'], $simNumber, $company, $vendor, $emi, 'Duplicate SIM number in Excel file');
                        continue;
                    }

                    if (isset($existingSims[$simNumber])) {
                        $stats['duplicate_db']++;
                        $failedSims[] = $this->createFailureEntry($stats['total'], $simNumber, $company, $vendor, $emi, 'Sim number already exists in Database');
                        continue;
                    }

                    $rider = Riders::where('company_contact', $simNumber)->first();
                    if(!$rider) {
                        $sim = Sims::create([
                        'number' => $simNumber,
                        'company' => $company,
                        'emi' => $emi,
                        'vendor' => $vendor,
                        'status' => 0,
                        'created_by' => Auth::id(),
                        ]);

                        $processedSims[] = "Sim:{$simNumber} Imported (Unassigned)";
                        $stats['imported']++;
                    }
                    else {
                        $sim = Sims::create([
                        'number' => $simNumber,
                        'company' => $company,
                        'emi' => $emi,
                        'vendor' => $vendor,
                        'assign_to' => $rider->id,
                        'status' => 1,
                        'created_by' => Auth::id(),
                        ]);

                        SimHistory::create([
                            'sim_id' => $sim->id,
                            'rider_id' => $rider->id,
                            'note_date' => now()->format('Y-m-d'),
                            'assigned_by' => Auth::id(),
                        ]);

                        $processedSims[] = "Sim:{$simNumber} Imported And Assigned to Rider: {$rider->name}";
                        $stats['imported']++;
                    }
                } catch (\Exception $e) {
                    $stats['failed']++;
                    $failedSims[] = $this->createFailureEntry($stats['total'], $simNumber ?? null, $company ?? null, $vendor ?? null, $emi ?? null, 'Exception: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
                    \Log::error("Import error: " . $e->getMessage());
                    continue;
                }           
            } 

            $this->results = [
                'stats' => $stats,
                'failed' => $failedSims,
                'processed' => $processedSims,
            ];
            DB::commit();
            \Log::info("SIM Import completed. Stats: " . json_encode($stats));
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Transaction error: " . $e->getMessage());
            throw $e;
        }
    }

    private function createFailureEntry($rowIndex, $number, $company, $vendor, $emi,$reason, $extra = [])
    {
        return array_merge([
            'excel_row' => $rowIndex + 1, 
            'number' => $number ?? 'Missing',
            'company' => $company ?? 'Missing',
            'vendor' => $vendor ?? 'Missing',
            'emi' => $emi ?? 'Missing',
            'reason' => $reason,
        ], $extra);
    }

    public function getResults()
    {
        return $this->results;
    }
}
