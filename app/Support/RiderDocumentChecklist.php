<?php

namespace App\Support;

use App\Models\Files;
use App\Models\RiderDocumentType;
use App\Models\RiderDocumentTypeSubtype;
use App\Models\Riders;
use Illuminate\Support\Collection;

class RiderDocumentChecklist
{
    /**
     * Build checklist rows for the rider Documents page (one row per document type).
     * Each file is claimed once so a new upload appears only under its own type.
     *
     * @return list<array<string, mixed>>
     */
    public static function forRider(Riders $rider): array
    {
        $types = RiderDocumentType::ordered()->with(['subtypes' => fn($q) => $q->ordered()])->get();
        $files = Files::query()
            ->where('type', 'rider')
            ->where('type_id', $rider->id)
            ->orderByDesc('id')
            ->get();

        $claimedIds = [];
        $rows = [];
        foreach ($types as $type) {
            $rows[] = self::buildRow($type, $files, $claimedIds);
        }

        return $rows;
    }

    /**
     * @param  Collection<int, Files>  $files
     * @param  array<int, true>  $claimedIds
     * @return array<string, mixed>
     */
    public static function buildRow(RiderDocumentType $type, Collection $files, array &$claimedIds = []): array
    {
        // Dual = configured as dual, or has front+back subtypes → always one checklist line.
        $isDual = self::isDualType($type);
        $label = self::cleanDocumentLabel(
            $type->label ?: '',
            $isDual ? ($type->front_label ?: $type->key) : ($type->key ?: '')
        );
        if ($label === '') {
            $label = ucfirst((string) $type->key);
        }

        if ($isDual) {
            $front = self::findFileForSide($files, $type, 'front', $claimedIds);
            if ($front) {
                $claimedIds[(int) $front->id] = true;
            }
            $back = self::findFileForSide($files, $type, 'back', $claimedIds);
            if ($back) {
                $claimedIds[(int) $back->id] = true;
            }
            $complete = $front && $back;
            $partial = ($front || $back) && !$complete;

            return [
                'document_type_id' => $type->id,
                'key' => $type->key,
                'label' => $label,
                'kind' => 'dual',
                'front_label' => self::sideDisplayLabel($label, $type->front_label, 'Front'),
                'back_label' => self::sideDisplayLabel($label, $type->back_label, 'Back'),
                'front_file' => $front,
                'back_file' => $back,
                'file' => null,
                'status' => $complete ? 'complete' : ($partial ? 'partial' : 'missing'),
                'uploaded' => (bool) $complete,
            ];
        }

        $file = self::findFileForSide($files, $type, 'main', $claimedIds)
            ?? self::findFileForType($files, $type, $claimedIds);
        if ($file) {
            $claimedIds[(int) $file->id] = true;
        }

        return [
            'document_type_id' => $type->id,
            'key' => $type->key,
            'label' => $label,
            'kind' => 'single',
            'front_label' => null,
            'back_label' => null,
            'front_file' => null,
            'back_file' => null,
            'file' => $file,
            'status' => $file ? 'complete' : 'missing',
            'uploaded' => (bool) $file,
        ];
    }

    /**
     * Find file already owned by this type/side (IDs only).
     * Used when uploading so the first save creates a new unique row instead of
     * overwriting an unrelated legacy file matched by name.
     *
     * @param  Collection<int, Files>  $files
     */
    public static function findOwnedFileForSide(
        Collection $files,
        RiderDocumentType $type,
        string $sideKey
    ): ?Files {
        return self::findFileForSide($files, $type, $sideKey, [], false);
    }

    /**
     * Find the file owned by this document type/side.
     * Prefer document_type_id / document_subtype_id so a first upload shows immediately
     * and stays unique (no loose name rematch onto other types).
     *
     * @param  Collection<int, Files>  $files
     * @param  array<int, true>  $claimedIds
     */
    public static function findFileForSide(
        Collection $files,
        RiderDocumentType $type,
        string $sideKey,
        array $claimedIds = [],
        bool $allowLegacyNameMatch = true
    ): ?Files {
        $available = $files->filter(fn($f) => !isset($claimedIds[(int) ($f->id ?? 0)]));

        $subtypeId = self::subtypeIdForSide($type, $sideKey);

        // 1) Exact subtype (+ type when present) — covers new Add Document / checklist uploads.
        if ($subtypeId) {
            $bySubtype = $available->first(function ($f) use ($subtypeId, $type) {
                if ((int) ($f->document_subtype_id ?? 0) !== (int) $subtypeId) {
                    return false;
                }
                $fileTypeId = (int) ($f->document_type_id ?? 0);

                return $fileTypeId === 0 || $fileTypeId === (int) $type->id;
            });
            if ($bySubtype) {
                return $bySubtype;
            }
        }

        // 2) Any file tagged with this document type for this side.
        $byType = $available->first(function ($f) use ($type, $sideKey) {
            if ((int) ($f->document_type_id ?? 0) !== (int) $type->id) {
                return false;
            }

            return self::typedFileMatchesSide($f, $type, $sideKey);
        });
        if ($byType) {
            return $byType;
        }

        if (!$allowLegacyNameMatch) {
            return null;
        }

        // 3) Legacy untyped files only — exact canonical name (never substring).
        return $available->first(function ($f) use ($type, $sideKey) {
            if ((int) ($f->document_type_id ?? 0) !== 0) {
                return false;
            }

            return self::exactCanonicalNameMatch($f, $type, $sideKey);
        });
    }

    /**
     * @param  Collection<int, Files>  $files
     * @param  array<int, true>  $claimedIds
     */
    public static function findFileForType(
        Collection $files,
        RiderDocumentType $type,
        array $claimedIds = []
    ): ?Files {
        $available = $files->filter(fn($f) => !isset($claimedIds[(int) ($f->id ?? 0)]));

        $byType = $available->first(
            fn($f) => (int) ($f->document_type_id ?? 0) === (int) $type->id
        );
        if ($byType) {
            return $byType;
        }

        return $available->first(function ($f) use ($type) {
            if ((int) ($f->document_type_id ?? 0) !== 0) {
                return false;
            }

            return self::exactCanonicalNameMatch($f, $type, 'main');
        });
    }

    /**
     * Resolve subtype id for a side. For single docs, fall back to the active "main"
     * or first subtype so first uploads are found even when key is not literally "main".
     */
    private static function subtypeIdForSide(RiderDocumentType $type, string $sideKey): ?int
    {
        $id = RiderDocumentTypeSubtype::query()
            ->where('document_type_id', $type->id)
            ->where('key', $sideKey)
            ->where('is_active', true)
            ->value('id');

        if ($id) {
            return (int) $id;
        }

        if ($sideKey !== 'main' || $type->type === 'dual') {
            return null;
        }

        $fallback = RiderDocumentTypeSubtype::query()
            ->where('document_type_id', $type->id)
            ->where('is_active', true)
            ->orderedForAdmin()
            ->value('id');

        return $fallback ? (int) $fallback : null;
    }

    /**
     * Typed file already belongs to this document type — match side by canonical name
     * or accept any non-side file for "main".
     */
    private static function typedFileMatchesSide(Files $file, RiderDocumentType $type, string $sideKey): bool
    {
        if (self::exactCanonicalNameMatch($file, $type, $sideKey)) {
            return true;
        }

        if ($sideKey !== 'main') {
            return false;
        }

        // Single/main: accept any file of this type that is not clearly a dual side.
        $name = strtolower(trim((string) ($file->name ?? '')));

        return $name === ''
            || (
                !str_ends_with($name, '_front')
                && !str_ends_with($name, '_back')
                && !str_ends_with($name, '_first')
                && !str_ends_with($name, '_second')
            );
    }

    /**
     * Exact stored names written by Add Document / typed upload
     * (e.g. labor_contract, labor_card_front) — never "labor" ⊂ "labor_contract".
     */
    private static function exactCanonicalNameMatch(Files $file, RiderDocumentType $type, string $sideKey): bool
    {
        $name = strtolower(trim((string) ($file->name ?? '')));
        $key = strtolower(trim((string) ($type->key ?? '')));
        if ($key === '' || $name === '') {
            return false;
        }

        if ($sideKey === 'front') {
            return $name === $key . '_front' || $name === $key . '_first';
        }
        if ($sideKey === 'back') {
            return $name === $key . '_back' || $name === $key . '_second';
        }

        return $name === $key || $name === $key . '_main';
    }

    /**
     * True when this document type should show as one Dual line (front · back).
     * Uses configured type, or auto-detects when both front and back subtypes exist.
     */
    public static function isDualType(RiderDocumentType $type): bool
    {
        if (($type->type ?? '') === 'dual') {
            return true;
        }

        $keys = self::activeSubtypeKeys($type);

        return in_array('front', $keys, true) && in_array('back', $keys, true);
    }

    /**
     * @return list<string>
     */
    private static function activeSubtypeKeys(RiderDocumentType $type): array
    {
        if ($type->relationLoaded('subtypes')) {
            return $type->subtypes
                ->filter(fn ($s) => (bool) ($s->is_active ?? true))
                ->pluck('key')
                ->map(fn ($k) => strtolower((string) $k))
                ->values()
                ->all();
        }

        return RiderDocumentTypeSubtype::query()
            ->where('document_type_id', $type->id)
            ->where('is_active', true)
            ->pluck('key')
            ->map(fn ($k) => strtolower((string) $k))
            ->values()
            ->all();
    }

    /**
     * Side caption like "Emirates ID (Front)" when only a generic side label is stored.
     */
    private static function sideDisplayLabel(string $documentLabel, ?string $sideLabel, string $defaultSide): string
    {
        $side = trim((string) ($sideLabel !== null && trim($sideLabel) !== '' ? $sideLabel : $defaultSide));
        if ($side === '') {
            $side = $defaultSide;
        }

        if ($documentLabel !== '' && preg_match('/^(front|back|first\s*page|second\s*page|1st\s*page|2nd\s*page)$/iu', $side)) {
            return $documentLabel . ' (' . ucwords(strtolower($side)) . ')';
        }

        return $side;
    }

    /**
     * Document display name without side suffixes like "( First Page )" / "( Front )".
     */
    public static function cleanDocumentLabel(?string $label, ?string $fallback = ''): string
    {
        $text = trim((string) ($label !== null && trim($label) !== '' ? $label : $fallback));
        if ($text === '') {
            return '';
        }

        $cleaned = preg_replace(
            '/\s*\(\s*(Front|Back|First\s*Page|Second\s*Page|Page\s*[12]|1st\s*Page|2nd\s*Page)\s*\)\s*$/iu',
            '',
            $text
        );

        return trim((string) ($cleaned !== null && $cleaned !== '' ? $cleaned : $text));
    }

    /**
     * Resolve front/back/main subtypes for a document type (create defaults if needed).
     *
     * @return array{front: ?RiderDocumentTypeSubtype, back: ?RiderDocumentTypeSubtype, main: ?RiderDocumentTypeSubtype}
     */
    public static function resolveSubtypes(RiderDocumentType $type): array
    {
        $subs = RiderDocumentTypeSubtype::query()
            ->where('document_type_id', $type->id)
            ->where('is_active', true)
            ->orderedForAdmin()
            ->get()
            ->keyBy('key');

        if (self::isDualType($type)) {
            foreach (
                [
                    ['key' => 'front', 'label' => trim((string) ($type->front_label ?: 'Front')), 'order' => 0],
                    ['key' => 'back', 'label' => trim((string) ($type->back_label ?: 'Back')), 'order' => 1],
                ] as $side
            ) {
                if ($subs->has($side['key'])) {
                    continue;
                }
                $created = RiderDocumentTypeSubtype::create([
                    'company_id' => $type->company_id,
                    'document_type_id' => $type->id,
                    'key' => $side['key'],
                    'label' => $side['label'] !== '' ? $side['label'] : ucfirst($side['key']),
                    'display_order' => $side['order'],
                    'is_active' => true,
                ]);
                $subs[$side['key']] = $created;
            }

            return [
                'front' => $subs->get('front'),
                'back' => $subs->get('back'),
                'main' => null,
            ];
        }

        $main = $subs->get('main') ?? $subs->first();
        if (!$main) {
            $main = RiderDocumentTypeSubtype::create([
                'company_id' => $type->company_id,
                'document_type_id' => $type->id,
                'key' => 'main',
                'label' => trim((string) ($type->label ?: $type->key)),
                'display_order' => 0,
                'is_active' => true,
            ]);
        }

        return [
            'front' => null,
            'back' => null,
            'main' => $main,
        ];
    }
}
