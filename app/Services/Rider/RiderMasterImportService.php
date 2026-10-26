<?php

namespace App\Services\Rider;

use App\Models\Accounts;
use App\Models\Branch;
use App\Models\Countries;
use App\Models\Riders;
use App\Support\ExcelDate;
use App\Support\GlobalAccounts;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Master rider Excel/CSV import with preview + commit (Rider List redesign).
 */
class RiderMasterImportService
{
    /** @var array<string, array{label: string, type: string}> */
    public const FIELDS = [
        'rider_id' => ['label' => 'Rider Id', 'type' => 'text'],
        'name' => ['label' => 'Name', 'type' => 'text'],
        'fleet_supervisor' => ['label' => 'Fleet Supervisor', 'type' => 'list:sup'],
        'nationality' => ['label' => 'Nationality', 'type' => 'list:nat'],
        'designation' => ['label' => 'Designation', 'type' => 'list:occ'],
        'person_code' => ['label' => 'Person Code', 'type' => 'text'],
        'emirate_id' => ['label' => 'Emirate Id', 'type' => 'text'],
        'doj' => ['label' => 'Date of Joining', 'type' => 'date'],
        'passport' => ['label' => 'Passport', 'type' => 'text'],
        'passport_expiry' => ['label' => 'Passport Expiry', 'type' => 'date'],
        'dob' => ['label' => 'Date Of Birth', 'type' => 'date'],
        'personal_contact' => ['label' => 'Personal Contact', 'type' => 'text'],
        'email' => ['label' => 'Email', 'type' => 'email'],
        'branch_id' => ['label' => 'Branch', 'type' => 'list:branch'],
        'license_expiry' => ['label' => 'License Expiry', 'type' => 'date'],
    ];

    /** Labels that appear in export/copy but are not written on import. */
    public const IGNORED_LABELS = [
        'project',
        'bike',
        'status',
        'status days',
        'balance',
        'company contact',
        'orders',
        'days',
        'actions',
    ];

    /**
     * @return array{rows: list<array>, counts: array{new:int,upd:int,err:int}, ignored: list<string>}
     */
    public function preview(UploadedFile $file): array
    {
        $grid = $this->readGrid($file);
        if (count($grid) < 2) {
            throw new \InvalidArgumentException('The file has no data rows');
        }

        $hdr = array_map(fn ($h) => strtolower(trim((string) $h)), $grid[0]);
        $label2k = [];
        foreach (self::FIELDS as $k => $meta) {
            $label2k[strtolower($meta['label'])] = $k;
        }
        $map = [];
        foreach ($hdr as $i => $h) {
            $map[$i] = ($label2k[$h] ?? null);
        }
        if (! in_array('rider_id', $map, true)) {
            throw new \InvalidArgumentException('Rider Id column is required');
        }

        $ignored = [];
        foreach ($hdr as $h) {
            if (in_array($h, self::IGNORED_LABELS, true) || (isset($label2k[$h]) === false && $h !== '')) {
                if (! isset($label2k[$h]) && $h !== '' && ! in_array($h, $ignored, true)) {
                    $ignored[] = $h;
                }
            }
        }

        $lists = $this->listValues();
        $pending = [];
        $counts = ['new' => 0, 'upd' => 0, 'err' => 0];

        foreach (array_slice($grid, 1) as $ri => $row) {
            $ch = [];
            $prob = [];
            $displayCh = [];

            foreach ($map as $ci => $k) {
                if (! $k || ! array_key_exists($ci, $row)) {
                    continue;
                }
                $v = $row[$ci];
                if ($v === null || $v === '') {
                    continue;
                }
                $meta = self::FIELDS[$k];
                $type = $meta['type'];

                if ($type === 'date') {
                    $d = $this->parseDate($v);
                    if (! $d) {
                        $prob[] = $meta['label'] . ': invalid date "' . $v . '"';
                        continue;
                    }
                    $ch[$k] = $d;
                    $displayCh[$meta['label']] = $this->fmtDate($d);
                } elseif (str_starts_with($type, 'list:')) {
                    $listKey = substr($type, 5);
                    $L = $lists[$listKey] ?? [];
                    $hit = null;
                    foreach ($L as $item) {
                        if (strcasecmp((string) $item['label'], trim((string) $v)) === 0) {
                            $hit = $item;
                            break;
                        }
                    }
                    if (! $hit) {
                        $prob[] = $meta['label'] . ': "' . $v . '" not in list';
                        continue;
                    }
                    $ch[$k] = $hit['value'];
                    $displayCh[$meta['label']] = $hit['label'];
                } elseif ($type === 'email') {
                    $email = trim((string) $v);
                    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $prob[] = 'Email: invalid "' . $v . '"';
                        continue;
                    }
                    $ch[$k] = $email;
                    $displayCh[$meta['label']] = $email;
                } else {
                    $ch[$k] = trim((string) $v);
                    $displayCh[$meta['label']] = $ch[$k];
                }
            }

            $id = $ch['rider_id'] ?? null;
            unset($ch['rider_id']);
            if (isset($displayCh['Rider Id'])) {
                // keep for display
            }

            if (! $id) {
                $prob[] = 'Rider Id missing';
            }

            $ex = $id ? Riders::query()->where('rider_id', $id)->first() : null;
            if ($id && ! $ex && empty($ch['name'])) {
                $prob[] = 'Name required for a new rider';
            }
            if ($ex && preg_match('/^sample/i', (string) ($ch['name'] ?? ''))) {
                $prob[] = 'SAMPLE row – delete it before importing';
            }

            // Only keep columns that exist on riders table
            $cols = array_flip(Schema::getColumnListing('riders'));
            foreach (array_keys($ch) as $field) {
                if (! isset($cols[$field])) {
                    unset($ch[$field]);
                    unset($displayCh[self::FIELDS[$field]['label'] ?? $field]);
                }
            }

            $act = $prob !== [] ? 'err' : ($ex ? 'upd' : 'new');
            $counts[$act]++;
            $pending[] = [
                'row' => $ri + 2,
                'id' => $id ?: '-',
                'act' => $act,
                'ch' => $displayCh,
                'payload' => $ch,
                'prob' => $prob,
            ];
        }

        return [
            'rows' => $pending,
            'counts' => $counts,
            'ignored' => $ignored,
        ];
    }

    /**
     * @param  list<array{id: string, act: string, payload: array}>  $rows
     * @return array{saved: int, skipped: int, errors: list<string>}
     */
    public function commit(array $rows): array
    {
        $saved = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                if (($row['act'] ?? '') === 'err') {
                    $skipped++;
                    continue;
                }
                $riderId = trim((string) ($row['id'] ?? ''));
                $payload = is_array($row['payload'] ?? null) ? $row['payload'] : [];
                if ($riderId === '' || $riderId === '-') {
                    $skipped++;
                    continue;
                }

                try {
                    $existing = Riders::query()->where('rider_id', $riderId)->first();
                    if ($existing) {
                        // Status / rider_status never imported
                        unset($payload['status'], $payload['rider_status'], $payload['customer_id'], $payload['company_contact']);
                        foreach ($payload as $k => $v) {
                            $existing->{$k} = $v;
                        }
                        $existing->save();
                    } else {
                        if (empty($payload['name'])) {
                            $skipped++;
                            $errors[] = "Rider {$riderId}: name required";
                            continue;
                        }
                        $data = $payload;
                        $data['rider_id'] = $riderId;
                        if (Schema::hasColumn('riders', 'company_id')) {
                            $data['company_id'] = Auth::user()->company_id;
                        }
                        if (! isset($data['status'])) {
                            $data['status'] = 3;
                        }
                        $rider = Riders::query()->create($data);
                        $this->ensureRiderAccount($rider);
                    }
                    $saved++;
                } catch (\Throwable $e) {
                    $skipped++;
                    $errors[] = "Rider {$riderId}: " . $e->getMessage();
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return compact('saved', 'skipped', 'errors');
    }

    public function downloadTemplate(bool $withSample = false): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $ss = new Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Riders');

        $keys = array_keys(self::FIELDS);
        foreach ($keys as $i => $k) {
            $ws->setCellValue([$i + 1, 1], self::FIELDS[$k]['label']);
            $ws->getStyle([$i + 1, 1])->getFont()->setBold(true)->getColor()->setRGB('1E3A8A');
            $ws->getStyle([$i + 1, 1])->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('DBEAFE');
            $ws->getColumnDimensionByColumn($i + 1)->setWidth(max(14, strlen(self::FIELDS[$k]['label']) + 4));
        }

        $lists = $this->listValues();
        $listSheet = $ss->createSheet();
        $listSheet->setTitle('Lists');
        $listOrder = ['nat' => 'Nationality', 'sup' => 'Fleet Supervisor', 'branch' => 'Branch', 'occ' => 'Occupation'];
        $col = 1;
        foreach ($listOrder as $lk => $title) {
            $listSheet->setCellValue([$col, 1], $title);
            $listSheet->getStyle([$col, 1])->getFont()->setBold(true);
            $row = 2;
            foreach ($lists[$lk] ?? [] as $item) {
                $listSheet->setCellValue([$col, $row], $item['label']);
                $row++;
            }
            $listSheet->getColumnDimensionByColumn($col)->setWidth(20);
            $col++;
        }

        // Data validations for list columns on first 200 rows
        $listColIndex = ['nat' => 1, 'sup' => 2, 'branch' => 3, 'occ' => 4];
        foreach ($keys as $ci => $k) {
            $type = self::FIELDS[$k]['type'];
            if (! str_starts_with($type, 'list:')) {
                continue;
            }
            $lk = substr($type, 5);
            $listCol = $listColIndex[$lk] ?? null;
            if (! $listCol) {
                continue;
            }
            $count = max(1, count($lists[$lk] ?? []));
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($listCol);
            $formula = "Lists!\${$colLetter}\$2:\${$colLetter}\$" . ($count + 1);
            for ($r = 2; $r <= 200; $r++) {
                $validation = $ws->getCell([$ci + 1, $r])->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setAllowBlank(true);
                $validation->setFormula1($formula);
                $validation->setShowDropDown(true);
            }
        }

        if ($withSample) {
            $sample = [
                '99001', 'SAMPLE Ahmed Khan', 'Mohsin', 'India', 'Rider', '10012345678901',
                '784-1995-1234567-1', '2026-09-01', 'AB1234567', '2028-01-15', '1995-05-20',
                '0501234567', 'ahmed@example.com', 'Dubai', '2027-03-10',
            ];
            foreach ($sample as $i => $v) {
                if ($i < count($keys)) {
                    $ws->setCellValue([$i + 1, 2], $v);
                }
            }
            $how = $ss->createSheet();
            $how->setTitle('How to use');
            $lines = [
                'How to use this file',
                '1. Rider Id is required. If it already exists, that rider is UPDATED (only filled cells change).',
                '2. If the Rider Id is new, a NEW rider is created (Name is required).',
                '3. Use the drop-downs for Nationality, Fleet Supervisor, Branch and Designation.',
                '4. Dates: 02 Oct 2026, 2026-10-02, 02/10/2026 or an Excel date.',
                '5. Project, Bike, Status, Balance and Company Contact are not importable.',
                '6. Delete the SAMPLE row before importing.',
            ];
            foreach ($lines as $i => $t) {
                $how->setCellValue([1, $i + 1], $t);
            }
            $how->getColumnDimension('A')->setWidth(110);
            $how->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        }

        $ss->setActiveSheetIndex(0);
        $filename = $withSample ? 'Riders-Import-Sample.xlsx' : 'Riders-Import-Template.xlsx';
        $tmp = tempnam(sys_get_temp_dir(), 'riders_imp_');
        (new Xlsx($ss))->save($tmp);

        return response()->download($tmp, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function ensureRiderAccount(Riders $rider): void
    {
        if ($rider->account_id) {
            return;
        }
        $parentAccount = GlobalAccounts::id('RIDERS');
        $account = new Accounts;
        $account->account_code = 'RD' . str_pad((string) $rider->rider_id, 4, '0', STR_PAD_LEFT);
        $account->name = $rider->name;
        $account->account_type = 'Liability';
        $account->ref_name = 'Rider';
        $account->company_id = Auth::user()->company_id;
        $account->parent_id = $parentAccount;
        $account->ref_id = $rider->id;
        $account->branch_id = $rider->branch_id;
        $account->is_locked = true;
        $account->save();
        $rider->account_id = $account->id;
        $rider->save();
    }

    /**
     * @return array{nat: list<array{label:string,value:mixed}>,sup: list,branch: list,occ: list}
     */
    private function listValues(): array
    {
        $nat = Countries::query()->orderBy('name')->get(['id', 'name'])
            ->map(fn ($c) => ['label' => (string) $c->name, 'value' => $c->id])->values()->all();

        $sup = Riders::query()->whereNotNull('fleet_supervisor')->where('fleet_supervisor', '!=', '')
            ->distinct()->orderBy('fleet_supervisor')->pluck('fleet_supervisor')
            ->map(fn ($s) => ['label' => (string) $s, 'value' => (string) $s])->values()->all();

        $branch = Branch::query()->orderBy('name')->get(['id', 'name'])
            ->map(fn ($b) => ['label' => (string) $b->name, 'value' => $b->id])->values()->all();

        $occRaw = Riders::query()->whereNotNull('designation')->where('designation', '!=', '')
            ->distinct()->orderBy('designation')->pluck('designation');
        $occ = $occRaw->map(fn ($s) => ['label' => (string) $s, 'value' => (string) $s])->values()->all();
        if ($occ === []) {
            $occ = [
                ['label' => 'Rider', 'value' => 'Rider'],
                ['label' => 'Driver', 'value' => 'Driver'],
                ['label' => 'Supervisor', 'value' => 'Supervisor'],
            ];
        }

        return compact('nat', 'sup', 'branch', 'occ');
    }

    /** @return list<list<mixed>> */
    private function readGrid(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if ($ext === 'csv') {
            return $this->parseCsv((string) file_get_contents($file->getRealPath()));
        }
        if (! in_array($ext, ['xlsx', 'xls'], true)) {
            throw new \InvalidArgumentException('Use an .xlsx or .csv file');
        }

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file->getRealPath());
        $reader->setReadDataOnly(true);
        $wb = $reader->load($file->getRealPath());
        $ws = null;
        foreach ($wb->getAllSheets() as $sheet) {
            $title = strtolower($sheet->getTitle());
            if (! in_array($title, ['lists', 'how to use'], true)) {
                $ws = $sheet;
                break;
            }
        }
        $ws = $ws ?: $wb->getSheet(0);
        $grid = [];
        foreach ($ws->toArray(null, true, true, false) as $row) {
            if (! is_array($row)) {
                continue;
            }
            if (! array_filter($row, fn ($v) => $v !== null && trim((string) $v) !== '')) {
                continue;
            }
            $grid[] = $row;
        }

        return $grid;
    }

    /** @return list<list<string>> */
    private function parseCsv(string $t): array
    {
        $out = [];
        $row = [];
        $cur = '';
        $q = false;
        $t = preg_replace('/^\xEF\xBB\xBF/', '', $t) ?? $t;
        $len = strlen($t);
        for ($i = 0; $i < $len; $i++) {
            $c = $t[$i];
            if ($q) {
                if ($c === '"') {
                    if (($t[$i + 1] ?? '') === '"') {
                        $cur .= '"';
                        $i++;
                    } else {
                        $q = false;
                    }
                } else {
                    $cur .= $c;
                }
            } elseif ($c === '"') {
                $q = true;
            } elseif ($c === ',') {
                $row[] = $cur;
                $cur = '';
            } elseif ($c === "\n" || $c === "\r") {
                if ($c === "\r" && ($t[$i + 1] ?? '') === "\n") {
                    $i++;
                }
                $row[] = $cur;
                $out[] = $row;
                $row = [];
                $cur = '';
            } else {
                $cur .= $c;
            }
        }
        if ($cur !== '' || $row !== []) {
            $row[] = $cur;
            $out[] = $row;
        }

        return array_values(array_filter($out, fn ($r) => array_filter($r, fn ($v) => trim((string) $v) !== '') !== []));
    }

    private function parseDate($v): ?string
    {
        $carbon = ExcelDate::parse($v);
        if ($carbon) {
            return $carbon->toDateString();
        }
        $s = trim((string) $v);
        $mon = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
        if (preg_match('/^(\d{1,2})[ \-]([A-Za-z]{3})[a-z]*[ \-](\d{4})$/', $s, $m)) {
            $mi = array_search(strtolower($m[2]), $mon, true);
            if ($mi === false) {
                return null;
            }

            return sprintf('%04d-%02d-%02d', (int) $m[3], $mi + 1, (int) $m[1]);
        }

        return null;
    }

    private function fmtDate(?string $iso): string
    {
        if (! $iso || str_starts_with($iso, '0001')) {
            return '-';
        }
        $parts = explode('-', $iso);
        if (count($parts) !== 3) {
            return $iso;
        }
        $mon = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        return $parts[2] . ' ' . ($mon[((int) $parts[1]) - 1] ?? $parts[1]) . ' ' . $parts[0];
    }
}
