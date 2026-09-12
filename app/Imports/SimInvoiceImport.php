<?php

namespace App\Imports;

use App\Models\Sims;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class SimInvoiceImport extends DefaultValueBinder implements ToCollection, WithCustomValueBinder
{
    protected const END_OF_DATA_THRESHOLD = 5;

    protected int $companyId;

    protected array $columnMap;

    /** @var array<int, array{item_id:int,col:int,rate:float}> */
    protected array $itemDefs;

    protected float $vatPercent;

    /** @var array<string, Sims> */
    protected array $simsByNumber = [];

    /** @var array<int, array{sim_id:int,item_id:int,qty:float,rate:float,discount:float,tax:float}> */
    public array $items = [];

    /** @var array<int, string> */
    public array $skippedLog = [];

    public int $importedCount = 0;

    /**
     * @param  array{sim_number:int}  $columnMap
     * @param  array<int, array{item_id:int,col:int,rate:float}>  $itemDefs
     */
    public function __construct(int $companyId, array $columnMap, array $itemDefs, float $vatPercent = 0.0)
    {
        $this->companyId = $companyId;
        $this->columnMap = $columnMap;
        $this->itemDefs = $itemDefs;
        $this->vatPercent = max(0.0, $vatPercent);
    }

    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) {
            $trimmed = trim($value);
            if (preg_match('/^\+?\d[\d\s\-]*$/', $trimmed)) {
                $cell->setValueExplicit($trimmed, DataType::TYPE_STRING);

                return true;
            }
        }

        return parent::bindValue($cell, $value);
    }

    public function collection(Collection $rows)
    {
        $this->loadSims();
        $seenSimIds = [];
        $consecutiveEmpty = 0;

        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue;
            }

            $excelRowNumber = (int) $index + 1;
            $simNumberRaw = $this->cell($row, 'sim_number');
            $simNumber = $this->normalizeSimNumber($simNumberRaw);

            if ($simNumber === '') {
                $consecutiveEmpty++;
                if ($consecutiveEmpty >= self::END_OF_DATA_THRESHOLD) {
                    break;
                }
                continue;
            }

            $consecutiveEmpty = 0;

            $sim = $this->findSim($simNumberRaw);
            if (! $sim) {
                $this->skippedLog[] = "Row {$excelRowNumber}: SIM {$this->displaySimNumber($simNumberRaw)} was not found.";
                continue;
            }

            if ($sim->trashed()) {
                $this->skippedLog[] = "Row {$excelRowNumber}: SIM {$sim->number} is deleted.";
                continue;
            }

            if ((string) $sim->company !== (string) $this->companyId) {
                $this->skippedLog[] = "Row {$excelRowNumber}: SIM {$sim->number} does not belong to the selected company.";
                continue;
            }

            if (isset($seenSimIds[$sim->id])) {
                $this->skippedLog[] = "Row {$excelRowNumber}: SIM {$sim->number} is duplicated in the file.";
                continue;
            }

            $rowHasLines = false;
            foreach ($this->itemDefs as $def) {
                $qty = $this->parseQty($row[((int) $def['col']) - 1] ?? null);
                if ($qty == 0.0) {
                    continue;
                }

                $rate = (float) $def['rate'];
                $discount = 0.0;
                $lineExcl = round(($qty * $rate) - $discount, 2);
                $tax = $this->vatPercent > 0 ? round($lineExcl * ($this->vatPercent / 100), 2) : 0.0;

                if (abs($lineExcl) < 0.00001 && abs($tax) < 0.00001) {
                    continue;
                }

                $this->items[] = [
                    'sim_id' => (int) $sim->id,
                    'item_id' => (int) $def['item_id'],
                    'qty' => $qty,
                    'rate' => $rate,
                    'discount' => $discount,
                    'tax' => $tax,
                ];
                $rowHasLines = true;
            }

            if (! $rowHasLines) {
                $this->skippedLog[] = "Row {$excelRowNumber}: SIM {$sim->number} has no quantity for any mapped item.";
                continue;
            }

            $seenSimIds[$sim->id] = true;
            $this->importedCount++;
        }
    }

    private function loadSims(): void
    {
        $sims = Sims::withTrashed()
            ->orderByRaw('company = ? desc', [$this->companyId])
            ->get(['id', 'number', 'company', 'deleted_at']);
        foreach ($sims as $sim) {
            $normalized = $this->normalizeSimNumber($sim->number);
            if ($normalized === '') {
                continue;
            }
            if (! isset($this->simsByNumber[$normalized])) {
                $this->simsByNumber[$normalized] = $sim;
            }
            $lastNine = strlen($normalized) > 9 ? substr($normalized, -9) : null;
            if ($lastNine && ! isset($this->simsByNumber[$lastNine])) {
                $this->simsByNumber[$lastNine] = $sim;
            }
        }
    }

    private function findSim($raw): ?Sims
    {
        $normalized = $this->normalizeSimNumber($raw);
        if ($normalized === '') {
            return null;
        }

        if (isset($this->simsByNumber[$normalized])) {
            return $this->simsByNumber[$normalized];
        }

        if (strlen($normalized) > 9) {
            $lastNine = substr($normalized, -9);
            if (isset($this->simsByNumber[$lastNine])) {
                return $this->simsByNumber[$lastNine];
            }
        }

        return $this->simsByNumber['0' . $normalized] ?? null;
    }

    private function cell($row, string $key)
    {
        $col = $this->columnMap[$key] ?? null;
        if ($col === null || $col === '') {
            return null;
        }

        $index = ((int) $col) - 1;

        return $row[$index] ?? null;
    }

    private function parseQty($value): float
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return 0.0;
        }

        if (is_numeric($value)) {
            return round((float) $value, 2);
        }

        $cleaned = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return ($cleaned !== '' && is_numeric($cleaned)) ? round((float) $cleaned, 2) : 0.0;
    }

    private function isBlank($value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    private function normalizeSimNumber($value): string
    {
        if ($this->isBlank($value)) {
            return '';
        }

        if (is_numeric($value) && ! is_string($value)) {
            $value = number_format((float) $value, 0, '', '');
        }

        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    private function displaySimNumber($value): string
    {
        if ($this->isBlank($value)) {
            return '';
        }

        if (is_numeric($value) && ! is_string($value)) {
            return number_format((float) $value, 0, '', '');
        }

        return trim((string) $value);
    }
}
