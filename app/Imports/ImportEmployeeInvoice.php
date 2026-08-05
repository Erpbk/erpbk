<?php

namespace App\Imports;

use App\Helpers\Account;
use App\Support\GlobalAccounts;
use App\Models\Employee;
use App\Models\EmployeeInvoiceItem;
use App\Models\EmployeeInvoices;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportEmployeeInvoice extends DefaultValueBinder implements ToCollection, WithCustomValueBinder
{
    protected array $columnMap;

    /** @var array<int, array{item_id:int,col:int,rate:float,vat:float,discount:float}> */
    protected array $itemDefs;

    public int $importedCount = 0;

    /** @var array<int, string> */
    public array $skippedLog = [];

    public function __construct(array $columnMap, array $itemDefs)
    {
        $this->columnMap = $columnMap;
        $this->itemDefs = $itemDefs;
    }

    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) {
            $trimmed = trim($value);
            if (preg_match('/^\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}(?:\s+\d{1,2}:\d{2}(?::\d{2})?(?:\s*[AaPp][Mm])?)?$/', $trimmed)) {
                $cell->setValueExplicit($trimmed, DataType::TYPE_STRING);

                return true;
            }
        }

        return parent::bindValue($cell, $value);
    }

    public function collection(Collection $rows)
    {
        $employeeCodes = $rows->map(function ($row) {
            $code = $this->cell($row, 'employee_id');

            return $code !== null ? trim((string) $code) : null;
        })->filter()->unique()->values();

        $employees = Employee::whereIn('employee_id', $employeeCodes)
            ->get()
            ->keyBy(function ($employee) {
                return (string) $employee->employee_id;
            });

        $transactionService = new TransactionService;

        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue;
            }

            $rowNumber = $index + 1;
            $employeeCode = trim((string) ($this->cell($row, 'employee_id') ?? ''));
            if ($employeeCode === '' || strcasecmp($employeeCode, 'employee_id') === 0 || strcasecmp($employeeCode, 'id') === 0) {
                continue;
            }

            try {
                $employee = $employees->get($employeeCode);
                if (! $employee) {
                    throw new \RuntimeException("Employee ID {$employeeCode} not found.");
                }

                DB::transaction(function () use ($row, $rowNumber, $employee, $employeeCode, $transactionService) {
                    $invoiceDate = $this->parseDate($this->cell($row, 'inv_date'), $rowNumber, 'Invoice Date');
                    $billingMonth = $this->parseBillingMonth($this->cell($row, 'billing_month'), $rowNumber);

                    $exists = EmployeeInvoices::where('employee_id', $employee->id)
                        ->where('billing_month', $billingMonth)
                        ->exists();

                    if ($exists) {
                        throw new \RuntimeException("Invoice already exists for Employee {$employeeCode}.");
                    }

                    $descriptions = trim((string) ($this->cell($row, 'descriptions') ?? ''));
                    $notes = null;
                    if ($this->isMapped('notes')) {
                        $notesRaw = trim((string) ($this->cell($row, 'notes') ?? ''));
                        $notes = $notesRaw !== '' ? $notesRaw : null;
                    }

                    $invoice = EmployeeInvoices::create([
                        'inv_date' => $invoiceDate,
                        'employee_id' => $employee->id,
                        'billing_month' => $billingMonth,
                        'descriptions' => $descriptions !== '' ? $descriptions : null,
                        'notes' => $notes,
                        'status' => 0,
                        'subtotal' => 0,
                        'vat' => 0,
                        'total_amount' => 0,
                    ]);

                    $subtotal = 0.0;
                    $vatTotal = 0.0;
                    $hasLines = false;

                    foreach ($this->itemDefs as $def) {
                        $qty = $this->parseQty($row[((int) $def['col']) - 1] ?? null);
                        if ($qty == 0.0) {
                            continue;
                        }

                        $rate = (float) $def['rate'];
                        $discount = (float) $def['discount'];
                        $vatPercent = (float) $def['vat'];

                        $lineSubtotal = round(($qty * $rate) - $discount, 2);
                        $lineVat = $vatPercent > 0 ? round($lineSubtotal * ($vatPercent / 100), 2) : 0.0;
                        $lineAmount = round($lineSubtotal + $lineVat, 2);

                        if ($lineAmount == 0.0) {
                            continue;
                        }

                        EmployeeInvoiceItem::create([
                            'inv_id' => $invoice->id,
                            'item_id' => $def['item_id'],
                            'qty' => $qty,
                            'rate' => $rate,
                            'discount' => $discount,
                            'tax' => $vatPercent,
                            'amount' => $lineAmount,
                        ]);

                        $subtotal += $lineSubtotal;
                        $vatTotal += $lineVat;
                        $hasLines = true;
                    }

                    if (! $hasLines) {
                        throw new \RuntimeException("No invoice items with quantity for Employee {$employeeCode}.");
                    }

                    $total = round($subtotal + $vatTotal, 2);
                    $invoice->update([
                        'subtotal' => round($subtotal, 2),
                        'vat' => round($vatTotal, 2),
                        'total_amount' => $total,
                    ]);

                    $invoice->load('employee');
                    $transCode = Account::trans_code();

                    if ($invoice->vat > 0) {
                        $transactionService->recordTransaction([
                            'account_id' => GlobalAccounts::id('VAT_PURCHASE_ACCOUNT'),
                            'reference_id' => $invoice->id,
                            'reference_type' => 'EmployeeInvoice',
                            'trans_code' => $transCode,
                            'trans_date' => $invoice->inv_date,
                            'narration' => 'Vat on Employee Invoice #'.$invoice->id,
                            'debit' => $invoice->vat,
                            'billing_month' => $invoice->billing_month,
                        ]);
                    }

                    $transactionService->recordTransaction([
                        'account_id' => $invoice->employee->account_id ?? null,
                        'reference_id' => $invoice->id,
                        'reference_type' => 'EmployeeInvoice',
                        'trans_code' => $transCode,
                        'trans_date' => $invoice->inv_date,
                        'narration' => $invoice->descriptions ?? ('Employee Invoice #'.$invoice->id),
                        'credit' => $total,
                        'billing_month' => $invoice->billing_month,
                    ]);

                    $transactionService->recordTransaction([
                        'account_id' => GlobalAccounts::id('STAFF_ACCOUNT'),
                        'reference_id' => $invoice->id,
                        'reference_type' => 'EmployeeInvoice',
                        'trans_code' => $transCode,
                        'trans_date' => $invoice->inv_date,
                        'narration' => 'Employee Invoice #'.$invoice->id.' - '.$invoice->descriptions,
                        'debit' => $total,
                        'billing_month' => $invoice->billing_month,
                    ]);
                });

                $this->importedCount++;
            } catch (ValidationException $e) {
                $detail = collect($e->errors())->flatten()->first() ?: $e->getMessage();
                $this->skippedLog[] = str_starts_with((string) $detail, 'Row(')
                    ? (string) $detail
                    : 'Row('.$rowNumber.') - '.$detail;
            } catch (\Throwable $e) {
                $this->skippedLog[] = 'Row('.$rowNumber.') - '.$e->getMessage();
            }
        }
    }

    private function cell($row, string $key)
    {
        $col = $this->columnMap[$key] ?? null;
        if ($col === null || $col === '') {
            return null;
        }

        $index = ((int) $col) - 1;
        $rowArray = is_array($row) ? $row : $row->toArray();

        return $rowArray[$index] ?? null;
    }

    private function isMapped(string $key): bool
    {
        $col = $this->columnMap[$key] ?? null;

        return $col !== null && $col !== '';
    }

    private function parseQty($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '', (string) $value);
    }

    private function parseDate($value, int $rowNumber, string $label): string
    {
        if ($value === null || $value === '') {
            throw new \RuntimeException("{$label} is required.");
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('Y-m-d');
        }

        $raw = trim((string) $value);
        try {
            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})/', $raw, $m)) {
                $day = (int) $m[1];
                $month = (int) $m[2];
                $year = (int) $m[3];
                if ($year < 100) {
                    $year += 2000;
                }

                return Carbon::createFromDate($year, $month, $day)->format('Y-m-d');
            }

            return Carbon::parse($raw)->format('Y-m-d');
        } catch (\Exception $e) {
            throw new \RuntimeException("Invalid {$label}: {$raw}");
        }
    }

    private function parseBillingMonth($value, int $rowNumber): string
    {
        if ($value === null || $value === '') {
            throw new \RuntimeException('Billing Month is required.');
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('Y-m-01');
        }

        $raw = trim((string) $value);

        $fromStrtotime = date('Y-m-01', strtotime($raw));
        if ($fromStrtotime !== '1970-01-01') {
            return $fromStrtotime;
        }

        try {
            if (preg_match('/^\d{4}-\d{2}$/', $raw)) {
                return $raw.'-01';
            }
            if (preg_match('/^\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/', $raw)) {
                return Carbon::createFromFormat('d/m/Y', preg_replace('/[.\-]/', '/', substr($raw, 0, 10)))->format('Y-m-01');
            }

            return Carbon::parse($raw)->format('Y-m-01');
        } catch (\Exception $e) {
            throw new \RuntimeException("Invalid Billing Month: {$raw}");
        }
    }
}
