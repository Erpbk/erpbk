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

        $rows = [];
        foreach ($types as $type) {
            $rows[] = self::buildRow($type, $files);
        }

        return $rows;
    }

    /**
     * @param  Collection<int, Files>  $files
     * @return array<string, mixed>
     */
    public static function buildRow(RiderDocumentType $type, Collection $files): array
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
            $front = self::findFileForSide($files, $type, 'front');
            $back = self::findFileForSide($files, $type, 'back');
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

        $file = self::findFileForSide($files, $type, 'main')
            ?? self::findFileForType($files, $type);

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
     */
    public static function findFileForSide(Collection $files, RiderDocumentType $type, string $sideKey): ?Files
    {
        $subtypeId = RiderDocumentTypeSubtype::query()
            ->where('document_type_id', $type->id)
            ->where('key', $sideKey)
            ->value('id');

        if ($subtypeId) {
            $bySubtype = $files->first(fn ($f) => (int) ($f->document_subtype_id ?? 0) === (int) $subtypeId);
            if ($bySubtype) {
                return $bySubtype;
            }
        }

        $byTypeAndName = $files->first(function ($f) use ($type, $sideKey) {
            if ((int) ($f->document_type_id ?? 0) !== (int) $type->id) {
                return false;
            }
            $name = strtolower((string) ($f->name ?? ''));
            if ($sideKey === 'front') {
                return str_contains($name, 'front') || str_contains($name, 'first') || str_ends_with($name, '_front');
            }
            if ($sideKey === 'back') {
                return str_contains($name, 'back') || str_contains($name, 'second') || str_ends_with($name, '_back');
            }

            return !str_contains($name, 'front') && !str_contains($name, 'back');
        });
        if ($byTypeAndName) {
            return $byTypeAndName;
        }

        return $files->first(function ($f) use ($type, $sideKey) {
            $name = strtolower((string) ($f->name ?? '') . ' ' . (string) ($f->file_name ?? ''));
            if (!str_contains($name, strtolower($type->key))) {
                return false;
            }
            if ($sideKey === 'front') {
                return str_contains($name, 'front') || str_contains($name, 'first') || str_ends_with(strtolower((string) $f->name), '_front');
            }
            if ($sideKey === 'back') {
                return str_contains($name, 'back') || str_contains($name, 'second') || str_ends_with(strtolower((string) $f->name), '_back');
            }

            return !str_contains($name, 'front')
                && !str_contains($name, 'back')
                && !str_contains($name, 'first')
                && !str_contains($name, 'second');
        });
    }

    /**
     * @param  Collection<int, Files>  $files
     */
    public static function findFileForType(Collection $files, RiderDocumentType $type): ?Files
    {
        $byType = $files->first(fn ($f) => (int) ($f->document_type_id ?? 0) === (int) $type->id);
        if ($byType) {
            return $byType;
        }

        return $files->first(function ($f) use ($type) {
            $name = strtolower((string) ($f->name ?? '') . ' ' . (string) ($f->file_name ?? ''));

            return str_contains($name, strtolower($type->key));
        });
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
