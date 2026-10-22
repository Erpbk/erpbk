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
     * Each file is claimed by at most one type/side so one upload cannot fill multiple slots.
     *
     * @return list<array<string, mixed>>
     */
    public static function forRider(Riders $rider): array
    {
        $types = RiderDocumentType::ordered()->with(['subtypes' => fn ($q) => $q->ordered()])->get();
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
        $isDual = $type->type === 'dual';
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
                'front_label' => $type->front_label ?: 'Front',
                'back_label' => $type->back_label ?: 'Back',
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
     * @param  Collection<int, Files>  $files
     * @param  array<int, true>  $claimedIds
     */
    public static function findFileForSide(
        Collection $files,
        RiderDocumentType $type,
        string $sideKey,
        array $claimedIds = []
    ): ?Files {
        $available = $files->filter(fn ($f) => !isset($claimedIds[(int) ($f->id ?? 0)]));

        $subtypeId = RiderDocumentTypeSubtype::query()
            ->where('document_type_id', $type->id)
            ->where('key', $sideKey)
            ->value('id');

        // 1) Exact subtype (and type when present) — preferred for typed rider-file uploads.
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

        // 2) Files explicitly tagged with this document type + side name.
        $byTypeAndSide = $available->first(function ($f) use ($type, $sideKey) {
            if ((int) ($f->document_type_id ?? 0) !== (int) $type->id) {
                return false;
            }

            return self::fileNameMatchesSide($f, $type, $sideKey);
        });
        if ($byTypeAndSide) {
            return $byTypeAndSide;
        }

        // 3) Single/main: any file tagged with this document type (ignore loose name matches).
        if ($sideKey === 'main') {
            $byType = $available->first(function ($f) use ($type) {
                if ((int) ($f->document_type_id ?? 0) !== (int) $type->id) {
                    return false;
                }
                $name = strtolower((string) ($f->name ?? ''));

                return !str_ends_with($name, '_front')
                    && !str_ends_with($name, '_back')
                    && !str_contains($name, '_front')
                    && !str_contains($name, '_back');
            });
            if (!$byType) {
                $byType = $available->first(
                    fn ($f) => (int) ($f->document_type_id ?? 0) === (int) $type->id
                );
            }
            if ($byType) {
                return $byType;
            }
        }

        // 4) Legacy only: untyped files whose stored name exactly equals key / key_side.
        // Never steal a file already assigned to another document_type_id.
        return $available->first(function ($f) use ($type, $sideKey) {
            if ((int) ($f->document_type_id ?? 0) !== 0) {
                return false;
            }

            return self::exactLegacyNameMatch($f, $type, $sideKey);
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
        $available = $files->filter(fn ($f) => !isset($claimedIds[(int) ($f->id ?? 0)]));

        $byType = $available->first(
            fn ($f) => (int) ($f->document_type_id ?? 0) === (int) $type->id
        );
        if ($byType) {
            return $byType;
        }

        return $available->first(function ($f) use ($type) {
            if ((int) ($f->document_type_id ?? 0) !== 0) {
                return false;
            }

            return self::exactLegacyNameMatch($f, $type, 'main');
        });
    }

    /**
     * Exact stored-name match for a typed side (no substring / alias matching).
     */
    private static function fileNameMatchesSide(Files $file, RiderDocumentType $type, string $sideKey): bool
    {
        return self::exactLegacyNameMatch($file, $type, $sideKey);
    }

    /**
     * Match only the canonical names written by Add Document / typed upload
     * (e.g. labor_contract, labor_card_front) — never substring matches like "labor" ⊂ "labor_contract".
     */
    private static function exactLegacyNameMatch(Files $file, RiderDocumentType $type, string $sideKey): bool
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

        if ($type->type === 'dual') {
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
