<?php

namespace App\Support;

use App\Models\Files;
use App\Models\RiderDocumentType;
use App\Models\RiderDocumentTypeSubtype;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Re-link legacy separately-uploaded front/back rider files onto one dual document type
 * (e.g. Emirates ID Front + Back → emirates dual), without deleting any files.
 */
class RiderDocumentDualConsolidation
{
    /**
     * Canonical dual document keys and name needles used to detect legacy uploads.
     *
     * @var array<string, list<string>>
     */
    private const DUAL_NEEDLES = [
        'passport' => ['passport'],
        'emirates' => ['emirates', 'emirate', 'eid'],
        'license' => ['license', 'licence', 'bike license', 'bike_license'],
        'labor' => ['labor card', 'labour card', 'labor_card', 'labour_card', 'labor', 'labour'],
        'nic' => ['nic', 'cnic', 'home country nic'],
    ];

    /**
     * @return array{
     *   types_ensured: int,
     *   subtypes_ensured: int,
     *   files_updated: int,
     *   side_types_folded: int,
     *   skipped: int
     * }
     */
    public static function run(bool $dryRun = false): array
    {
        $stats = [
            'types_ensured' => 0,
            'subtypes_ensured' => 0,
            'files_updated' => 0,
            'side_types_folded' => 0,
            'skipped' => 0,
        ];

        if (!Schema::hasTable('rider_document_types') || !Schema::hasTable('files')) {
            return $stats;
        }

        $run = function () use (&$stats, $dryRun) {
            $dualTypes = self::ensureDualTypes($stats, $dryRun);
            self::foldSideSpecificTypes($dualTypes, $stats, $dryRun);
            self::retagRiderFiles($dualTypes, $stats, $dryRun);
        };

        if ($dryRun) {
            $run();
        } else {
            DB::transaction($run);
        }

        return $stats;
    }

    /**
     * @param  array<string, mixed>  $stats
     * @return Collection<string, RiderDocumentType> keyed by document type key
     */
    private static function ensureDualTypes(array &$stats, bool $dryRun): Collection
    {
        $byKey = collect();

        foreach (array_keys(self::DUAL_NEEDLES) as $key) {
            $type = RiderDocumentType::query()->where('key', $key)->first();
            if (!$type) {
                continue;
            }

            $changed = false;
            if (($type->type ?? '') !== 'dual') {
                $type->type = 'dual';
                $changed = true;
            }
            if (empty($type->front_label)) {
                $type->front_label = self::defaultFrontLabel($key);
                $changed = true;
            }
            if (empty($type->back_label)) {
                $type->back_label = self::defaultBackLabel($key);
                $changed = true;
            }
            if ($changed) {
                if (!$dryRun) {
                    $type->save();
                }
                $stats['types_ensured']++;
            }

            $before = RiderDocumentTypeSubtype::query()
                ->where('document_type_id', $type->id)
                ->whereIn('key', ['front', 'back'])
                ->count();

            if (!$dryRun) {
                RiderDocumentChecklist::resolveSubtypes($type);
            } elseif ($before < 2) {
                $stats['subtypes_ensured'] += 2 - $before;
            }

            $after = RiderDocumentTypeSubtype::query()
                ->where('document_type_id', $type->id)
                ->whereIn('key', ['front', 'back'])
                ->count();
            if (!$dryRun && $after > $before) {
                $stats['subtypes_ensured'] += $after - $before;
            }

            $type->load(['subtypes' => fn ($q) => $q->ordered()]);
            $byKey[$key] = $type;
        }

        // Also include any other DB types already marked dual.
        RiderDocumentType::query()
            ->where('type', 'dual')
            ->where('is_active', true)
            ->get()
            ->each(function (RiderDocumentType $type) use ($byKey, &$stats, $dryRun) {
                if ($byKey->has($type->key)) {
                    return;
                }
                if (!$dryRun) {
                    RiderDocumentChecklist::resolveSubtypes($type);
                }
                $type->load(['subtypes' => fn ($q) => $q->ordered()]);
                $byKey[$type->key] = $type;
            });

        return $byKey;
    }

    /**
     * Fold separately-created single types like emirates_front into the dual parent.
     *
     * @param  Collection<string, RiderDocumentType>  $dualTypes
     * @param  array<string, mixed>  $stats
     */
    private static function foldSideSpecificTypes(Collection $dualTypes, array &$stats, bool $dryRun): void
    {
        if (!Schema::hasTable('rider_document_type_subtypes')) {
            return;
        }

        $candidates = RiderDocumentType::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('type', 'single')
                    ->orWhereNull('type');
            })
            ->get();

        foreach ($candidates as $sideType) {
            $parsed = self::parseSideSpecificType($sideType, $dualTypes);
            if ($parsed === null) {
                continue;
            }

            /** @var RiderDocumentType $dual */
            $dual = $parsed['dual'];
            $sideKey = $parsed['side'];
            $subtype = $dual->subtypes->firstWhere('key', $sideKey);
            if (!$subtype && !$dryRun) {
                RiderDocumentChecklist::resolveSubtypes($dual);
                $dual->load(['subtypes' => fn ($q) => $q->ordered()]);
                $subtype = $dual->subtypes->firstWhere('key', $sideKey);
            }
            if (!$subtype) {
                continue;
            }

            $files = Files::query()
                ->where('type', 'rider')
                ->where(function ($q) use ($sideType) {
                    $q->where('document_type_id', $sideType->id);
                })
                ->get();

            foreach ($files as $file) {
                $payload = [
                    'document_type_id' => $dual->id,
                    'document_subtype_id' => $subtype->id,
                    'name' => $dual->key . '_' . $sideKey,
                ];
                if (!$dryRun) {
                    $file->fill($payload);
                    $file->save();
                }
                $stats['files_updated']++;
            }

            if (!$dryRun) {
                $sideType->is_active = false;
                $sideType->save();
            }
            $stats['side_types_folded']++;
        }
    }

    /**
     * @param  Collection<string, RiderDocumentType>  $dualTypes
     * @return array{dual: RiderDocumentType, side: string}|null
     */
    private static function parseSideSpecificType(RiderDocumentType $sideType, Collection $dualTypes): ?array
    {
        $key = strtolower(trim((string) $sideType->key));
        $label = strtolower(trim((string) ($sideType->label ?: $sideType->front_label ?: '')));
        $haystack = $key . ' ' . $label;

        $side = null;
        if (
            str_ends_with($key, '_front')
            || str_ends_with($key, '_first')
            || str_contains($haystack, 'front')
            || str_contains($haystack, 'first page')
            || str_contains($haystack, 'first_page')
        ) {
            $side = 'front';
        } elseif (
            str_ends_with($key, '_back')
            || str_ends_with($key, '_second')
            || str_contains($haystack, 'back')
            || str_contains($haystack, 'second page')
            || str_contains($haystack, 'second_page')
        ) {
            $side = 'back';
        }

        if ($side === null) {
            return null;
        }

        foreach ($dualTypes as $dualKey => $dual) {
            if ($key === $dualKey || $key === $dualKey . '_' . $side) {
                return ['dual' => $dual, 'side' => $side];
            }
            foreach (self::needlesForKey($dualKey, $dual) as $needle) {
                if ($needle !== '' && str_contains($haystack, $needle)) {
                    return ['dual' => $dual, 'side' => $side];
                }
            }
        }

        return null;
    }

    /**
     * @param  Collection<string, RiderDocumentType>  $dualTypes
     * @param  array<string, mixed>  $stats
     */
    private static function retagRiderFiles(Collection $dualTypes, array &$stats, bool $dryRun): void
    {
        if ($dualTypes->isEmpty()) {
            return;
        }

        Files::query()
            ->where('type', 'rider')
            ->orderBy('id')
            ->chunkById(200, function ($files) use ($dualTypes, &$stats, $dryRun) {
                foreach ($files as $file) {
                    $match = self::matchFileToDualSide($file, $dualTypes);
                    if ($match === null) {
                        $stats['skipped']++;
                        continue;
                    }

                    $dual = $match['dual'];
                    $side = $match['side'];
                    $subtype = $dual->subtypes->firstWhere('key', $side);
                    if (!$subtype) {
                        $stats['skipped']++;
                        continue;
                    }

                    $canonical = $dual->key . '_' . $side;
                    $needsUpdate = (int) ($file->document_type_id ?? 0) !== (int) $dual->id
                        || (int) ($file->document_subtype_id ?? 0) !== (int) $subtype->id
                        || strtolower((string) ($file->name ?? '')) !== strtolower($canonical);

                    if (!$needsUpdate) {
                        $stats['skipped']++;
                        continue;
                    }

                    if (!$dryRun) {
                        $file->document_type_id = $dual->id;
                        $file->document_subtype_id = $subtype->id;
                        $file->name = $canonical;
                        $file->save();
                    }
                    $stats['files_updated']++;
                }
            });
    }

    /**
     * @param  Collection<string, RiderDocumentType>  $dualTypes
     * @return array{dual: RiderDocumentType, side: string}|null
     */
    private static function matchFileToDualSide(Files $file, Collection $dualTypes): ?array
    {
        $haystack = strtolower(trim(
            (string) ($file->name ?? '') . ' ' . (string) ($file->file_name ?? '')
        ));
        if ($haystack === '') {
            return null;
        }

        // Already linked to a dual type: only fix subtype/name if side is clear.
        $currentTypeId = (int) ($file->document_type_id ?? 0);
        if ($currentTypeId > 0) {
            $linked = $dualTypes->first(
                fn (RiderDocumentType $t) => (int) $t->id === $currentTypeId
            );
            if ($linked) {
                $side = self::detectSide($haystack);
                if ($side === null) {
                    // Keep existing subtype if already front/back.
                    $subKey = strtolower((string) (
                        $linked->subtypes->firstWhere('id', (int) ($file->document_subtype_id ?? 0))?->key ?? ''
                    ));
                    if (in_array($subKey, ['front', 'back'], true)) {
                        return ['dual' => $linked, 'side' => $subKey];
                    }

                    return null;
                }

                return ['dual' => $linked, 'side' => $side];
            }
        }

        $side = self::detectSide($haystack);
        if ($side === null) {
            return null;
        }

        $best = null;
        $bestLen = 0;
        foreach ($dualTypes as $key => $dual) {
            foreach (self::needlesForKey((string) $key, $dual) as $needle) {
                if ($needle === '' || !str_contains($haystack, $needle)) {
                    continue;
                }
                $len = strlen($needle);
                if ($len > $bestLen) {
                    $bestLen = $len;
                    $best = $dual;
                }
            }
        }

        if ($best === null) {
            return null;
        }

        return ['dual' => $best, 'side' => $side];
    }

    private static function detectSide(string $haystack): ?string
    {
        if (
            str_contains($haystack, '_front')
            || str_contains($haystack, ' front')
            || str_contains($haystack, '(front')
            || str_contains($haystack, 'first page')
            || str_contains($haystack, 'first_page')
            || str_contains($haystack, '_first')
            || preg_match('/\bfront\b/', $haystack)
            || preg_match('/\bfirst\b/', $haystack)
        ) {
            return 'front';
        }

        if (
            str_contains($haystack, '_back')
            || str_contains($haystack, ' back')
            || str_contains($haystack, '(back')
            || str_contains($haystack, 'second page')
            || str_contains($haystack, 'second_page')
            || str_contains($haystack, '_second')
            || preg_match('/\bback\b/', $haystack)
            || preg_match('/\bsecond\b/', $haystack)
        ) {
            return 'back';
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private static function needlesForKey(string $key, RiderDocumentType $type): array
    {
        $needles = self::DUAL_NEEDLES[$key] ?? [strtolower($key)];
        foreach (['label', 'front_label', 'back_label'] as $field) {
            $label = strtolower(trim((string) ($type->{$field} ?? '')));
            $label = RiderDocumentChecklist::cleanDocumentLabel($label);
            if ($label !== '') {
                $needles[] = $label;
            }
        }

        $needles = array_map(static fn ($n) => strtolower(trim((string) $n)), $needles);
        $needles = array_values(array_unique(array_filter($needles)));
        usort($needles, static fn ($a, $b) => strlen($b) <=> strlen($a));

        return $needles;
    }

    private static function defaultFrontLabel(string $key): string
    {
        return match ($key) {
            'passport' => 'Passport ( First Page )',
            'nic' => 'Home Country NIC ( Front )',
            'labor' => 'Labor Card ( Front )',
            'emirates' => 'Emirates ID ( Front )',
            'license' => 'Bike License ( Front )',
            default => ucfirst($key) . ' ( Front )',
        };
    }

    private static function defaultBackLabel(string $key): string
    {
        return match ($key) {
            'passport' => 'Passport ( Second Page )',
            'nic' => 'Home Country NIC ( Back )',
            'labor' => 'Labor Card ( Back )',
            'emirates' => 'Emirates ID ( Back )',
            'license' => 'Bike License ( Back )',
            default => ucfirst($key) . ' ( Back )',
        };
    }
}
