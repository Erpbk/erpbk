<?php

namespace App\Support;

use App\Http\Controllers\FilesController;
use App\Models\Files;
use App\Models\RiderDocumentType;
use App\Models\Riders;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class RiderDocumentReplacement
{
    public const CHANGE_MESSAGE = 'You have changed the document information. Please upload the required document(s) before the change can be saved.';

    /** @var array<int, array<string, mixed>> */
    private static array $existingCache = [];

    /** @var array<int, array<string, mixed>> */
    private static array $filesMetaCache = [];

    /**
     * Rider profile fields that belong to a document type (number + expiry).
     *
     * @return array<string, array{key: string, label: string, type: string, number: ?string, expiry: ?string, front_label: ?string, back_label: ?string, single_label: ?string}>
     */
    public static function definitions(): array
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $fieldMap = self::fieldMap();
        $dualKeys = ['passport', 'emirates', 'license', 'labor', 'nic'];

        $riderColumns = Schema::hasTable('riders')
            ? array_flip(Schema::getColumnListing('riders'))
            : [];

        $dbTypes = [];
        if (Schema::hasTable('rider_document_types')) {
            $types = RiderDocumentType::query()->where('is_active', true)->orderBy('display_order')->orderBy('id')->get();
            foreach ($types as $type) {
                $dbTypes[$type->key] = $type;
            }
        }

        $definitions = [];
        foreach ($fieldMap as $key => $map) {
            $number = ($map['number'] && isset($riderColumns[$map['number']])) ? $map['number'] : null;
            $expiry = ($map['expiry'] && isset($riderColumns[$map['expiry']])) ? $map['expiry'] : null;
            if ($number === null && $expiry === null) {
                continue;
            }

            $type = $dbTypes[$key] ?? null;
            $isDual = $type
                ? $type->type === 'dual'
                : in_array($key, $dualKeys, true);

            if ($isDual) {
                $label = trim((string) ($type?->front_label ?: $type?->back_label ?: $type?->label ?: $key));
            } else {
                $label = trim((string) ($type?->label ?: $key));
            }

            $definitions[$key] = [
                'key' => $key,
                'label' => $label !== '' ? $label : ucfirst($key),
                'type' => $isDual ? 'dual' : 'single',
                'number' => $number,
                'expiry' => $expiry,
                'front_label' => $type?->front_label,
                'back_label' => $type?->back_label,
                'single_label' => $type?->label,
            ];
        }

        $cached = $definitions;

        return $cached;
    }

    /**
     * @return array<string, array{number: ?string, expiry: ?string}>
     */
    public static function fieldMap(): array
    {
        return [
            'passport' => ['number' => 'passport', 'expiry' => 'passport_expiry'],
            'emirates' => ['number' => 'emirate_id', 'expiry' => 'emirate_exp'],
            'license' => ['number' => 'license_no', 'expiry' => 'license_expiry'],
            'labor' => ['number' => 'labor_card_number', 'expiry' => 'labor_card_expiry'],
            'road' => ['number' => 'road_permit', 'expiry' => 'road_permit_expiry'],
            'nic' => ['number' => 'cnic', 'expiry' => 'cnic_expiry'],
            'health' => ['number' => 'policy_no', 'expiry' => 'insurance_expiry'],
        ];
    }

    /**
     * Status badge for an expiry date field. Always based on the date value;
     * clickable when a matching uploaded document exists.
     *
     * @return array{status: string, label: string, class: string, url: ?string, expiry: ?string, text: string, name: string}|null
     */
    public static function expiryBadgeForField(?Riders $rider, string $fieldKey, mixed $dateValue): ?array
    {
        $meta = self::definitionForField($fieldKey);
        if ($meta === null || $meta['role'] !== 'expiry') {
            return null;
        }

        $normalized = self::normalizeValue($dateValue, true);
        if ($normalized === '') {
            return null;
        }

        $status = self::expiryStatus($normalized);
        $def = self::definitions()[$meta['key']] ?? null;
        $name = (string) ($def['label'] ?? ucfirst($meta['key']));
        $url = null;

        if ($rider instanceof Riders) {
            foreach (self::filesMetaForRider($rider)[$meta['key']]['files'] ?? [] as $file) {
                if (! empty($file['url'])) {
                    $url = $file['url'];
                    $name = (string) ($file['name'] ?: $name);
                    break;
                }
            }
        }

        return [
            'status' => $status['status'],
            'label' => $status['label'],
            'class' => $status['class'],
            'url' => $url,
            'expiry' => $normalized,
            'text' => Carbon::parse($normalized)->format('d M Y'),
            'name' => $name,
        ];
    }

    /**
     * How many mapped rider documents have an expired date.
     */
    public static function expiredCountForRider(Riders $rider): int
    {
        $count = 0;
        foreach (self::definitions() as $def) {
            if (! $def['expiry']) {
                continue;
            }
            $status = self::expiryStatus($rider->{$def['expiry']} ?? null);
            if ($status['status'] === 'expired') {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Count expired files from Files tab for a rider.
     */
    public static function expiredFilesCountForRider(Riders $rider): int
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('files')) {
            return 0;
        }

        $today = now()->startOfDay();
        
        try {
            $count = (int) CompanyQuery::table('files')
                ->where('type', 'rider')
                ->where('type_id', $rider->id)
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '<', $today)
                ->count();
            
            return $count;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error counting expired files', [
                'rider_id' => $rider->id,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Combined count of expired documents from both Rider Information and Files tab.
     */
    public static function totalExpiredCountForRider(Riders $rider): int
    {
        return self::expiredCountForRider($rider) + self::expiredFilesCountForRider($rider);
    }

    /**
     * Count expiring soon fields from Rider Information (within specified days).
     */
    public static function expiringCountForRider(Riders $rider, int $days = 30): int
    {
        $count = 0;
        $today = now()->startOfDay();
        $endDate = now()->addDays($days)->endOfDay();

        foreach (self::definitions() as $def) {
            if (! $def['expiry']) {
                continue;
            }
            $expiryValue = $rider->{$def['expiry']} ?? null;
            if (empty($expiryValue)) {
                continue;
            }

            try {
                $expiryDate = \Carbon\Carbon::parse($expiryValue);
                if ($expiryDate->between($today, $endDate)) {
                    $count++;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $count;
    }

    /**
     * Count expiring soon files from Files tab (within specified days).
     */
    public static function expiringFilesCountForRider(Riders $rider, int $days = 30): int
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('files')) {
            return 0;
        }

        $today = now()->startOfDay();
        $endDate = now()->addDays($days)->endOfDay();
        
        try {
            $count = (int) CompanyQuery::table('files')
                ->where('type', 'rider')
                ->where('type_id', $rider->id)
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '>=', $today)
                ->where('expiry_date', '<=', $endDate)
                ->count();
            
            return $count;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error counting expiring files', [
                'rider_id' => $rider->id,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Combined count of expiring soon documents from both sources.
     */
    public static function totalExpiringCountForRider(Riders $rider, int $days = 30): int
    {
        return self::expiringCountForRider($rider, $days) + self::expiringFilesCountForRider($rider, $days);
    }

    /**
     * @return array{key: string, role: string}|null
     */
    public static function definitionForField(string $fieldKey): ?array
    {
        if ($fieldKey === '') {
            return null;
        }

        foreach (self::definitions() as $def) {
            if ($def['number'] === $fieldKey) {
                return ['key' => $def['key'], 'role' => 'number'];
            }
            if ($def['expiry'] === $fieldKey) {
                return ['key' => $def['key'], 'role' => 'expiry'];
            }
        }

        return null;
    }

    /**
     * Whether this form field should render the replacement-upload slot (one per document type).
     */
    public static function fieldIsUploadSlot(string $fieldKey): bool
    {
        $meta = self::definitionForField($fieldKey);
        if ($meta === null) {
            return false;
        }

        $def = self::definitions()[$meta['key']] ?? null;
        if ($def === null) {
            return false;
        }

        $expiryVisible = $def['expiry'] && field_visible('rider', $def['expiry']);
        if ($expiryVisible) {
            return $fieldKey === $def['expiry'];
        }

        return $def['number'] && $fieldKey === $def['number'] && field_visible('rider', $def['number']);
    }

    /**
     * @return array<string, bool>
     */
    public static function existingTypesForRider(Riders $rider): array
    {
        return self::existingCache($rider)['types'];
    }

    /**
     * @return array<string, mixed>
     */
    public static function frontendConfig(Riders $rider): array
    {
        $fields = [];
        foreach (self::definitions() as $def) {
            if ($def['number']) {
                $fields[$def['number']] = ['key' => $def['key'], 'role' => 'number'];
            }
            if ($def['expiry']) {
                $fields[$def['expiry']] = ['key' => $def['key'], 'role' => 'expiry'];
            }
        }

        return [
            'message' => self::CHANGE_MESSAGE,
            'existing' => self::existingTypesForRider($rider),
            'fields' => $fields,
            'definitions' => array_values(self::definitions()),
            'files' => self::filesMetaForRider($rider),
            'replaceUrl' => route('riders.replaceDocument', $rider->id),
            'riderId' => $rider->id,
        ];
    }

    /**
     * Files grouped by document type, with view URL and expiry badge status.
     *
     * @return array<string, array{key: string, files: list<array<string, mixed>>}>
     */
    public static function filesMetaForRider(Riders $rider): array
    {
        if (isset(self::$filesMetaCache[$rider->id])) {
            return self::$filesMetaCache[$rider->id];
        }

        $grouped = [];
        foreach (self::definitions() as $key => $def) {
            $grouped[$key] = ['key' => $key, 'files' => []];
        }

        $files = Files::query()
            ->where('type', 'rider')
            ->where('type_id', $rider->id)
            ->get();

        foreach ($files as $file) {
            $matchedKey = null;
            $side = null;
            $haystack = trim((string) $file->name.' '.(string) $file->file_name);
            foreach (self::definitions() as $key => $def) {
                if (! self::fileMatchesType($haystack, $key)) {
                    continue;
                }
                $matchedKey = $key;
                if (($def['type'] ?? '') === 'dual') {
                    $name = strtolower((string) $file->name);
                    if (str_contains($name, 'back') || str_contains($name, 'second')) {
                        $side = 'back';
                    } elseif (str_contains($name, 'front') || str_contains($name, 'first')) {
                        $side = 'front';
                    }
                }
                break;
            }

            if ($matchedKey === null) {
                continue;
            }

            $grouped[$matchedKey]['files'][] = self::fileBadgePayload($file, $rider, $matchedKey, $side);
        }

        self::$filesMetaCache[$rider->id] = $grouped;

        return $grouped;
    }

    /**
     * @param  object  $file
     * @return array{status: string, label: string, class: string, url: ?string, expiry: ?string, name: string}
     */
    public static function expiryMetaForFile(object $file, ?Riders $rider = null): array
    {
        $matchedKey = null;
        $side = null;
        foreach (self::definitions() as $key => $def) {
            if (! self::fileMatchesType(trim((string) $file->name.' '.(string) $file->file_name), $key)) {
                continue;
            }
            $matchedKey = $key;
            if (($def['type'] ?? '') === 'dual') {
                $name = strtolower((string) $file->name);
                if (str_contains($name, 'back') || str_contains($name, 'second')) {
                    $side = 'back';
                } elseif (str_contains($name, 'front') || str_contains($name, 'first')) {
                    $side = 'front';
                }
            }
            break;
        }

        return self::fileBadgePayload($file, $rider, $matchedKey, $side);
    }

    /**
     * @return array{status: string, label: string, class: string}
     */
    public static function expiryStatus(?string $date): array
    {
        $normalized = self::normalizeValue($date, true);
        if ($normalized === '') {
            return ['status' => 'none', 'label' => 'No expiry', 'class' => 'bg-secondary'];
        }

        $expiry = Carbon::parse($normalized)->startOfDay();
        $today = now()->startOfDay();
        if ($expiry->lt($today)) {
            return ['status' => 'expired', 'label' => 'Expired', 'class' => 'bg-danger'];
        }

        $windowEnd = $today->copy()->addDays(DocumentExpiry::windowDays());
        if ($expiry->lte($windowEnd)) {
            $days = (int) $today->diffInDays($expiry);

            return [
                'status' => 'expiring',
                'label' => $days === 0 ? 'Expiring today' : 'Expiring soon',
                'class' => 'bg-warning text-dark',
            ];
        }

        return ['status' => 'valid', 'label' => 'Valid', 'class' => 'bg-success'];
    }

    /**
     * @param  array<string, mixed>  $submitted
     * @return array<string, list<string>>
     */
    public static function validationErrors(Riders $rider, Request $request, array $submitted): array
    {
        $changed = self::changedTypes($rider, $submitted);
        if ($changed === []) {
            return [];
        }

        $errors = [];
        foreach ($changed as $def) {
            $errors = array_merge($errors, self::uploadErrorsForType($request, $def));
        }

        return $errors;
    }

    /**
     * Store replacement files for document types whose details changed.
     *
     * @param  array<string, mixed>  $submitted
     */
    public static function storeUploadedFiles(Riders $rider, Request $request, array $submitted): void
    {
        $changed = self::changedTypes($rider, $submitted);
        if ($changed === []) {
            return;
        }

        foreach ($changed as $key => $def) {

            $expiryDate = null;
            if ($def['expiry']) {
                $expiry = array_key_exists($def['expiry'], $submitted)
                    ? self::normalizeValue($submitted[$def['expiry']] ?? null, true)
                    : self::normalizeValue($rider->{$def['expiry']} ?? null, true);
                $expiryDate = $expiry !== '' ? $expiry : null;
            }

            if ($def['type'] === 'dual') {
                $front = self::uploadedFile($request, $key, 'front');
                $back = self::uploadedFile($request, $key, 'back');
                if ($front instanceof UploadedFile) {
                    self::replaceFile($rider, $key, $front, $expiryDate, 'front', $def);
                }
                if ($back instanceof UploadedFile) {
                    self::replaceFile($rider, $key, $back, $expiryDate, 'back', $def);
                }
                continue;
            }

            $file = self::uploadedFile($request, $key);
            if ($file instanceof UploadedFile) {
                self::replaceFile($rider, $key, $file, $expiryDate, null, $def);
            }
        }

        unset(self::$existingCache[$rider->id], self::$filesMetaCache[$rider->id]);
    }

    /**
     * Validate uploads, then save document fields and replacement files together.
     *
     * @return array{saved: array<string, mixed>, badge: ?array, badge_html: string, expiry_field: ?string}
     */
    public static function commitTypeChange(Riders $rider, Request $request, string $key): array
    {
        $def = self::definitions()[$key] ?? null;
        if ($def === null) {
            throw ValidationException::withMessages([
                'document_key' => ['Unknown document type.'],
            ]);
        }

        $submitted = [];
        if ($def['number'] && $request->exists($def['number'])) {
            $submitted[$def['number']] = $request->input($def['number']);
        }
        if ($def['expiry'] && $request->exists($def['expiry'])) {
            $submitted[$def['expiry']] = $request->input($def['expiry']);
        }

        $submitted = \App\Support\RoleFieldAccess::stripNonEditableInput(
            $submitted,
            'rider',
            is_array($rider->custom_field_values ?? null) ? $rider->custom_field_values : []
        );

        if (! isset(self::changedTypes($rider, $submitted)[$key])) {
            throw ValidationException::withMessages([
                'document_key' => ['No document information was changed.'],
            ]);
        }

        $errors = self::uploadErrorsForType($request, $def);
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        try {
            DB::transaction(function () use ($rider, $request, $submitted) {
                self::storeUploadedFiles($rider, $request, $submitted);
                $rider->fill($submitted);
                $rider->save();
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'document_files' => ['The document could not be saved. Please try again.'],
            ]);
        }

        $fresh = $rider->fresh();
        $badge = null;
        if ($fresh && $def['expiry']) {
            $badge = self::expiryBadgeForField($fresh, $def['expiry'], $fresh->{$def['expiry']} ?? null);
        }

        return [
            'saved' => $submitted,
            'badge' => $badge,
            'badge_html' => $badge
                ? view('riders._document_expiry_badge', ['badge' => $badge])->render()
                : '',
            'expiry_field' => $def['expiry'],
        ];
    }

    /**
     * @param  array{key: string, label: string, type: string, number: ?string, expiry: ?string, front_label: ?string, back_label: ?string, single_label: ?string}  $def
     * @return array<string, list<string>>
     */
    private static function uploadErrorsForType(Request $request, array $def): array
    {
        $key = $def['key'];

        if ($def['type'] === 'dual') {
            $errors = [];
            $front = self::uploadedFile($request, $key, 'front');
            $back = self::uploadedFile($request, $key, 'back');
            $frontLabel = trim((string) ($def['front_label'] ?: 'first page'));
            $backLabel = trim((string) ($def['back_label'] ?: 'second page'));

            if (! ($front instanceof UploadedFile && $front->isValid())) {
                $errors['document_files.'.$key.'.front'] = ['Please upload '.$frontLabel.'.'];
            }
            if (! ($back instanceof UploadedFile && $back->isValid())) {
                $errors['document_files.'.$key.'.back'] = ['Please upload '.$backLabel.'.'];
            }
            if ($errors !== []) {
                return $errors;
            }

            return self::uploadedFileRuleErrors($request, $key, $def['type']);
        }

        $file = self::uploadedFile($request, $key);
        if (! ($file instanceof UploadedFile && $file->isValid())) {
            return ['document_files.'.$key => [self::CHANGE_MESSAGE]];
        }

        return self::uploadedFileRuleErrors($request, $key, $def['type']);
    }

    /**
     * @param  array<string, mixed>  $submitted
     * @return array<string, array{key: string, label: string, type: string, number: ?string, expiry: ?string, front_label: ?string, back_label: ?string, single_label: ?string}>
     */
    public static function changedTypes(Riders $rider, array $submitted): array
    {
        $changed = [];

        foreach (self::definitions() as $key => $def) {
            $numberChanged = $def['number']
                && array_key_exists($def['number'], $submitted)
                && self::valuesDiffer($rider->{$def['number']} ?? null, $submitted[$def['number']] ?? null, false);

            $expiryChanged = $def['expiry']
                && array_key_exists($def['expiry'], $submitted)
                && self::valuesDiffer($rider->{$def['expiry']} ?? null, $submitted[$def['expiry']] ?? null, true);

            if ($numberChanged || $expiryChanged) {
                $changed[$key] = $def;
            }
        }

        return $changed;
    }

    public static function fileMatchesType(string $fileName, string $key, ?string $side = null): bool
    {
        $name = strtolower($fileName);
        $matched = false;
        foreach (self::matchNeedlesForKey($key) as $needle) {
            if ($needle !== '' && str_contains($name, $needle)) {
                $matched = true;
                break;
            }
        }
        if (! $matched) {
            return false;
        }

        if ($side === 'back') {
            return str_contains($name, 'back') || str_contains($name, 'second');
        }
        if ($side === 'front') {
            return str_contains($name, 'front') || str_contains($name, 'first');
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private static function matchNeedlesForKey(string $key): array
    {
        $needles = [strtolower($key)];
        $aliases = [
            'emirates' => ['emirates', 'emirate', 'eid'],
            'license' => ['license', 'licence'],
            'labor' => ['labor', 'labour'],
            'passport' => ['passport'],
            'road' => ['road permit', 'road_permit'],
            'nic' => ['nic', 'cnic'],
            'health' => ['health', 'insurance'],
        ];
        foreach ($aliases[$key] ?? [] as $alias) {
            $needles[] = strtolower($alias);
        }
        $def = self::definitions()[$key] ?? null;
        if ($def) {
            foreach (['label', 'front_label', 'back_label', 'single_label'] as $labelKey) {
                $label = strtolower(trim((string) ($def[$labelKey] ?? '')));
                if ($label !== '') {
                    $needles[] = $label;
                }
            }
        }

        return array_values(array_unique($needles));
    }

    /**
     * @return array{types: array<string, bool>, sides: array<string, array{front: bool, back: bool, single: bool}>}
     */
    private static function existingCache(Riders $rider): array
    {
        if (isset(self::$existingCache[$rider->id])) {
            return self::$existingCache[$rider->id];
        }

        $files = Files::query()
            ->where('type', 'rider')
            ->where('type_id', $rider->id)
            ->get(['id', 'name', 'file_name']);

        $types = [];
        $sides = [];
        foreach (self::definitions() as $key => $def) {
            $front = false;
            $back = false;
            $single = false;
            foreach ($files as $file) {
                $haystack = trim((string) $file->name.' '.(string) $file->file_name);
                if (! self::fileMatchesType($haystack, $key)) {
                    continue;
                }
                $single = true;
                $name = strtolower((string) $file->name);
                if (str_contains($name, 'back') || str_contains($name, 'second')) {
                    $back = true;
                } elseif (str_contains($name, 'front') || str_contains($name, 'first')) {
                    $front = true;
                }
            }
            $types[$key] = $single;
            $sides[$key] = ['front' => $front, 'back' => $back, 'single' => $single];
        }

        self::$existingCache[$rider->id] = ['types' => $types, 'sides' => $sides];

        return self::$existingCache[$rider->id];
    }

    /**
     * @param  array{key: string, label: string, type: string, number: ?string, expiry: ?string, front_label: ?string, back_label: ?string, single_label: ?string}  $def
     */
    private static function replaceFile(
        Riders $rider,
        string $key,
        UploadedFile $upload,
        ?string $expiryDate,
        ?string $side,
        array $def
    ): void {
        $extension = $upload->extension() ?: $upload->getClientOriginalExtension() ?: 'bin';
        $storedName = 'rider-'.$rider->id.'-'.$key.($side ? '-'.$side : '').'-'.time().'.'.$extension;
        $directory = 'rider/'.$rider->id;

        PublicStorageDisk::storeUploadedFile($upload, $directory, $storedName);

        $displayName = self::displayNameForSide($def, $side);

        $existing = Files::query()
            ->where('type', 'rider')
            ->where('type_id', $rider->id)
            ->get();

        $match = $existing->first(function ($file) use ($key, $side) {
            return self::fileMatchesType((string) $file->name, $key, $side);
        });
        if (! $match && $side !== null) {
            $match = $existing->first(function ($file) use ($key, $side) {
                if (! self::fileMatchesType((string) $file->name, $key)) {
                    return false;
                }
                $name = strtolower((string) $file->name);
                if ($side === 'front' && (str_contains($name, 'back') || str_contains($name, 'second'))) {
                    return false;
                }
                if ($side === 'back' && (str_contains($name, 'front') || str_contains($name, 'first'))) {
                    return false;
                }

                return true;
            });
        }

        if ($match) {
            if (! empty($match->file_name)) {
                PublicStorageDisk::delete($directory.'/'.$match->file_name);
            }
            $match->file_name = $storedName;
            $match->file_type = $extension;
            $match->expiry_date = $expiryDate;
            if (empty($match->name)) {
                $match->name = $displayName;
            }
            $match->save();

            return;
        }

        $payload = [
            'name' => $displayName,
            'type' => 'rider',
            'type_id' => $rider->id,
            'file_name' => $storedName,
            'file_type' => $extension,
            'expiry_date' => $expiryDate,
            'status' => 1,
            'branch_id' => $rider->branch_id,
        ];
        if (Schema::hasColumn('files', 'company_id') && Schema::hasColumn('riders', 'company_id')) {
            $payload['company_id'] = $rider->company_id;
        }

        Files::create($payload);
    }

    /**
     * @param  array{front_label: ?string, back_label: ?string, single_label: ?string, label: string}  $def
     */
    private static function displayNameForSide(array $def, ?string $side): string
    {
        if ($side === 'back') {
            return trim((string) ($def['back_label'] ?: $def['label']));
        }
        if ($side === 'front') {
            return trim((string) ($def['front_label'] ?: $def['label']));
        }

        return trim((string) ($def['single_label'] ?: $def['label']));
    }

    private static function uploadedFile(Request $request, string $key, ?string $side = null): ?UploadedFile
    {
        $files = $request->file('document_files');
        if ($files instanceof UploadedFile) {
            return $side === null ? $files : null;
        }
        if (! is_array($files) || ! isset($files[$key])) {
            $dotted = $side ? 'document_files.'.$key.'.'.$side : 'document_files.'.$key;
            $file = $request->file($dotted);

            return $file instanceof UploadedFile ? $file : null;
        }

        $entry = $files[$key];
        if ($entry instanceof UploadedFile) {
            return $side === null ? $entry : null;
        }
        if (! is_array($entry) || $side === null) {
            return null;
        }
        $file = $entry[$side] ?? null;

        return $file instanceof UploadedFile ? $file : null;
    }

    private static function hasUploadedFileForType(Request $request, string $key, string $type): bool
    {
        if ($type === 'dual') {
            $front = self::uploadedFile($request, $key, 'front');
            $back = self::uploadedFile($request, $key, 'back');

            return ($front instanceof UploadedFile && $front->isValid())
                && ($back instanceof UploadedFile && $back->isValid());
        }

        $file = self::uploadedFile($request, $key);

        return $file instanceof UploadedFile && $file->isValid();
    }

    /**
     * @return array<string, list<string>>
     */
    private static function uploadedFileRuleErrors(Request $request, string $key, string $type): array
    {
        $errors = [];
        $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        $maxKb = 20480;

        $check = function (?UploadedFile $file, string $inputName) use (&$errors, $allowed, $maxKb) {
            if (! $file instanceof UploadedFile) {
                return;
            }
            if (! $file->isValid()) {
                $errors[$inputName] = ['The uploaded document is invalid.'];

                return;
            }
            $ext = strtolower((string) ($file->extension() ?: $file->getClientOriginalExtension()));
            if (! in_array($ext, $allowed, true)) {
                $errors[$inputName] = ['The document must be a file of type: jpg, jpeg, png, pdf, doc, docx.'];

                return;
            }
            if ($file->getSize() > $maxKb * 1024) {
                $errors[$inputName] = ['The document may not be greater than 20 MB.'];
            }
        };

        if ($type === 'dual') {
            $check(self::uploadedFile($request, $key, 'front'), 'document_files.'.$key.'.front');
            $check(self::uploadedFile($request, $key, 'back'), 'document_files.'.$key.'.back');
        } else {
            $check(self::uploadedFile($request, $key), 'document_files.'.$key);
        }

        return $errors;
    }

    /**
     * @return array{id: ?int, name: string, url: ?string, expiry: ?string, status: string, label: string, class: string, side: ?string}
     */
    private static function fileBadgePayload(object $file, ?Riders $rider, ?string $typeKey, ?string $side): array
    {
        $path = FilesController::storageRelativePath($file);
        $url = storage_url($path);

        $expiry = $file->expiry_date
            ? self::normalizeValue($file->expiry_date, true)
            : '';
        if ($expiry === '' && $rider && $typeKey) {
            $def = self::definitions()[$typeKey] ?? null;
            if ($def && $def['expiry']) {
                $expiry = self::normalizeValue($rider->{$def['expiry']} ?? null, true);
            }
        }

        $status = self::expiryStatus($expiry !== '' ? $expiry : null);

        return [
            'id' => $file->id,
            'name' => (string) ($file->name ?: 'Document'),
            'url' => $url,
            'expiry' => $expiry !== '' ? $expiry : null,
            'text' => $expiry !== '' ? Carbon::parse($expiry)->format('d M Y') : $status['label'],
            'status' => $status['status'],
            'label' => $status['label'],
            'class' => $status['class'],
            'side' => $side,
        ];
    }

    private static function valuesDiffer(mixed $current, mixed $incoming, bool $isDate): bool
    {
        return self::normalizeValue($current, $isDate) !== self::normalizeValue($incoming, $isDate);
    }

    private static function normalizeValue(mixed $value, bool $isDate): string
    {
        if ($value === null) {
            return '';
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $value = trim((string) $value);
        if ($value === '' || $value === '0000-00-00' || str_starts_with($value, '0000-00-00')) {
            return '';
        }

        if ($isDate) {
            try {
                return Carbon::parse($value)->toDateString();
            } catch (\Throwable $e) {
                return $value;
            }
        }

        return $value;
    }
}
