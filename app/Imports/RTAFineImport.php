<?php

namespace App\Imports;

use App\Helpers\Account;
use App\Models\Salik;
use App\Models\Bikes;
use App\Models\Riders;
use App\Models\BikeHistory;
use App\Models\Vouchers;
use App\Models\RtaFines;
use App\Models\FailedSalikImport;
use App\Services\TransactionService;
use Illuminate\Support\Collection;
use App\Repositories\RtaFinesRepository;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

class RTAFineImport implements ToCollection
{
    protected $AccountId;
    protected $adminChargePerFine;
    protected $importBatchId;
    protected $results;

    public function __construct($AccountId, $adminChargePerFine = 0)
    {
        $this->AccountId = $AccountId;
        $this->importBatchId = 'batch_' . time() . '_' . Auth::id();
    }

    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            
            $adminAccount = DB::Table('accounts')->where('id', 1004)->first();
            $serviceAccount = DB::table('accounts')->where('id', 1368)->first();
            
            $stats = [
                'total' => 0,
                'imported' => 0,
                'failed' => 0,
                'duplicate_excel' => 0,
                'duplicate_db' => 0,
                'missing_data' => 0,
                'no_bike' => 0,
                'no_rider' => 0,
            ];
            
            $failedFines = [];
            $processedTickets = [];
            $importedFineIds = [];

            // Pre-fetch all ticket numbers for bulk duplicate check
            $allTicketNumbers = $rows->skip(1)->pluck(1)->filter()->toArray();
            $existingTickets = RtaFines::whereIn('ticket_no', $allTicketNumbers)
                ->pluck('ticket_no')
                ->toArray();
            $existingTickets = array_flip($existingTickets);

            foreach ($rows->skip(1) as $rowIndex => $row) {
                $stats['total']++;

                if (collect($row)->take(8)->every(fn($cell) => trim($cell ?? '') === '')) {
                   break;
                }
                
                try {
                    // Extract data with validation
                    $bikePlate      = trim($row[0] ?? '');
                    $ticketNumber   = trim($row[1] ?? '');
                    $tripDate       = $row[2] ? Carbon::parse($row[2])->format('Y-m-d') : null;
                    $tripTime       = $row[3] ? Carbon::parse($row[3])->format('H:i:s') : null;
                    $fineAmount     = (float)($row[4] ?? 0);
                    $fineDetails    = trim($row[5] ?? '');
                    $adminFee       = (float)($row[6] ?? 25);
                    $serviceFee     = (float)($row[7] ?? 20);


                    // Validation
                    if (empty($bikePlate) || empty($ticketNumber) || !$tripDate || 
                        $fineAmount <= 0 || empty($fineDetails) ) {
                        $failedFines[] = $this->createFailureEntry($rowIndex, $ticketNumber, $bikePlate, 'Missing or invalid required data');
                        $stats['missing_data']++;
                        continue;
                    }


                    // Check duplicate in current file
                    if (in_array($ticketNumber, $processedTickets)) {
                        $stats['duplicate_excel']++;
                        continue;
                    }
                    $processedTickets[] = $ticketNumber;

                    // Check duplicate in database (bulk check)
                    if (isset($existingTickets[$ticketNumber])) {
                        $failedFines[] = $this->createFailureEntry($rowIndex, $ticketNumber, $bikePlate, 'Ticket already exists in database');
                        $stats['duplicate_db']++;
                        continue;
                    }

                    // Find bike
                    $bike = Bikes::where('plate', $bikePlate)->first();
                    if (!$bike) {
                        $failedFines[] = $this->createFailureEntry($rowIndex, $ticketNumber, $bikePlate, 'Bike not registered');
                        $stats['no_bike']++;
                        continue;
                    }

                    // Find rider for trip date
                    $rider = $this->findRiderForTripDate($bike->id, $tripDate, $bikePlate);
                    if (!$rider) {
                        $failedFines[] = $this->createFailureEntry($rowIndex, $ticketNumber, $bikePlate, 'No rider assigned for trip date');
                        $stats['no_rider']++;
                        continue;
                    }

                    // Get rider account
                    $riderAccountId = $this->getRiderAccountId($rider->id);
                    if (!$riderAccountId) {
                        $failedFines[] = $this->createFailureEntry($rowIndex, $ticketNumber, $bikePlate, 'Rider account not found');
                        $stats['failed']++;
                        continue;
                    }

                    // Calculate billing month 
                    $billingMonth = Carbon::parse($tripDate)->firstOfMonth();

                    // Create fine record
                    $fineData = [
                        'trans_date' => Carbon::today(),
                        'trans_code' => Account::trans_code(),
                        'trip_date' => $tripDate,
                        'trip_time' => $tripTime,
                        'rider_id' => $rider->id,
                        'billing_month' => $billingMonth,
                        'ticket_no' => $ticketNumber,
                        'bike_id' => $bike->id,
                        'plate_no' => $bike->plate,
                        'detail' => $fineDetails,
                        'amount' => $fineAmount,
                        'service_charges' => $serviceFee,
                        'admin_fee' => $adminFee,
                        'total_amount' => $fineAmount + $serviceFee + $adminFee,
                        'rta_account_id' => $this->AccountId,
                        'status' => 'unpaid',
                        'created_at' => now(),
                    ];

                    $rtaFine = RtaFines::create($fineData);
                    $importedFineIds[] = $this->createSuccessEntry($rtaFine->id, $ticketNumber, $bikePlate, $rtaFine->total_amount, $rtaFine->detail);

                    // Process  transactions
                    $this->processTransactions($rtaFine, $riderAccountId, $adminAccount, $serviceAccount
                    );

                    // Create voucher
                    $this->createVoucher($rtaFine, $riderAccountId);

                    $stats['imported']++;

                } catch (\Exception $e) {
                    $stats['failed']++;
                    $failedFines[] = $this->createFailureEntry(
                        $rowIndex, 
                        $ticketNumber ?? $row[1], 
                        $bikePlate ?? $row[0], 
                        $e->getMessage(),
                        ['exception' => $e->getTraceAsString()]
                    );
                    \Log::error("Row {$rowIndex} error: " . $e->getMessage());
                    continue;
                }
            }

            // Store results
            $this->results = [
                'stats' => $stats,
                'failed_fines' => $failedFines,
                'imported_ids' => $importedFineIds,
            ];

            DB::commit();

            \Log::info("Import completed", $stats);
            return $importedFineIds;

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Import failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function getResults(){
        return ($this->results);
    }
    private function createFailureEntry($rowIndex, $ticket, $plate, $reason, $extra = [])
    {
        return array_merge([
            'excel_row' => $rowIndex + 1, 
            'ticket_number' => $ticket ?? 'Missing',
            'plate_number' => $plate ?? 'Missing',
            'reason' => $reason,
        ], $extra);
    }

    private function createSuccessEntry($id, $ticket, $plate, $amount, $detail)
    {
        return [
            'id' => $id, 
            'ticket_number' => $ticket,
            'plate_number' => $plate,
            'amount' => $amount,
            'detail' => $detail,
        ];
    }

    private function processTransactions($fine, $riderAccountId, $adminAccount, $serviceAccount,)
    {
        $transactionService = new TransactionService();
        $transCode = $fine->trans_code;
        $transDate = $fine->trans_date;
        $billingMonth = $fine->billing_month;

        // 1. Debit rider for total amount
        $transactionService->recordTransaction([
            'account_id' => $riderAccountId,
            'reference_id' => $fine->id,
            'reference_type' => 'RTA_FINE',
            'trans_code' => $transCode,
            'trans_date' => $transDate,
            'narration' => $fine->detail,
            'debit' => $fine->total_amount,
            'billing_month' => $billingMonth,
        ]);

        // 2. Credit admin account for admin fee
        if ($fine->admin_fee > 0) {
            $transactionService->recordTransaction([
                'account_id' => $adminAccount->id,
                'reference_id' => $fine->id,
                'reference_type' => 'RTA_FINE',
                'trans_code' => $transCode,
                'trans_date' => $transDate,
                'narration' => $adminAccount->name,
                'credit' => $fine->admin_fee,
                'billing_month' => $billingMonth,
            ]);
        }

        // 3. Credit service account for service charges
        if ($fine->service_charges > 0) {
            $transactionService->recordTransaction([
                'account_id' => $serviceAccount->id,
                'reference_id' => $fine->id,
                'reference_type' => 'RTA_FINE',
                'trans_code' => $transCode,
                'trans_date' => $transDate,
                'narration' => $serviceAccount->name,
                'credit' => $fine->service_charges,
                'billing_month' => $billingMonth,
            ]);
        }

        // 4. Credit RTA account for fine amount
        $transactionService->recordTransaction([
            'account_id' => $fine->rta_account_id,
            'reference_id' => $fine->id,
            'reference_type' => 'RTA_FINE',
            'trans_code' => $transCode,
            'trans_date' => $transDate,
            'narration' => $fine->detail,
            'credit' => $fine->amount,
            'billing_month' => $billingMonth,
        ]);
    }

    private function createVoucher($fine, $riderAccountId)
    {
        return Vouchers::create([
            'rider_id' => $fine->rider_id,
            'trans_date' => $fine->trans_date,
            'trans_code' => $fine->trans_code,
            'trip_date' => $fine->trip_date,
            'billing_month' => $fine->billing_month,
            'payment_type' => 1,
            'voucher_type' => 'RFV',
            'remarks' => "RTA Fine Voucher",
            'amount' => $fine->total_amount,
            'Created_By' => auth()->id(),
            'pay_account' => $riderAccountId,
            'ref_id' => $fine->id,
        ]);
    }

    private function findRiderForTripDate($bikeId, $tripDate, $plateNumber)
    {
        $bike = Bikes::find($bikeId);
        if (!$bike) return null;

        // 1. History check karo - rider who was assigned on or before trip date
        $history = BikeHistory::where('bike_id', $bikeId)
            ->whereDate('note_date', '<=', $tripDate)
            ->where(function ($q) use ($tripDate) {
                $q->whereNull('return_date')
                    ->orWhereDate('return_date', '>=', $tripDate);
            })
            ->orderBy('note_date', 'desc')
            ->first();

        if ($history && $history->rider_id) {
            return Riders::find($history->rider_id); // Rider return karega
        }

        // 2. Agar history mein rider nahi mila to bike ka current rider_id use karo
        if ($bike->rider_id) {
            return Riders::find($bike->rider_id);
        }

        // 3. NEW: If bike has no current rider, find the last rider from bike history
        $lastRiderHistory = BikeHistory::where('bike_id', $bikeId)
            ->whereNotNull('rider_id')
            ->orderBy('note_date', 'desc')
            ->orderBy('id', 'desc') // In case same note_date, get the latest entry
            ->first();

        if ($lastRiderHistory && $lastRiderHistory->rider_id) {
            \Log::info("No current rider for bike {$plateNumber}. Using last rider from history: Rider ID {$lastRiderHistory->rider_id} (History Date: {$lastRiderHistory->note_date})");
            return Riders::find($lastRiderHistory->rider_id);
        }

        // 4. Fallback: Plate number se rider find karo (if somehow different bike record exists)
        $bikeByPlate = Bikes::where('plate', $plateNumber)->first();
        return $bikeByPlate ? Riders::find($bikeByPlate->rider_id) : null;
    }


    private function getRiderAccountId($riderId)
    {
        $account = \App\Models\Accounts::where('ref_id', $riderId)->first();
        return $account ? $account->id : null;
    }

}
