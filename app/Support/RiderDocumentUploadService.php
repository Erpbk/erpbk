<?php

namespace App\Support;

use App\Models\Files;
use App\Models\RiderDocumentType;
use App\Models\RiderDocumentTypeField;
use App\Models\RiderDocumentTypeSubtype;
use App\Models\Riders;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RiderDocumentUploadService
{
    /**
     * Persist single or dual rider document upload from the typed modal.
     *
     * @return array{message: string, reload: bool}
     */
    public static function store(Riders $rider, RiderDocumentType $docType, Request $request): array
    {
        $subs = RiderDocumentChecklist::resolveSubtypes($docType);
        $branchId = $rider->branch_id ? (int) $rider->branch_id : null;

        if ($docType->type === 'dual') {
            return self::storeDual($rider, $docType, $subs, $request, $branchId);
        }

        return self::storeSingle($rider, $docType, $subs['main'], $request, $branchId);
    }

    /**
     * @param  array{front: ?RiderDocumentTypeSubtype, back: ?RiderDocumentTypeSubtype, main: ?RiderDocumentTypeSubtype}  $subs
     */
    private static function storeDual(
        Riders $rider,
        RiderDocumentType $docType,
        array $subs,
        Request $request,
        ?int $branchId
    ): array {
        $frontSub = $subs['front'];
        $backSub = $subs['back'];
        if (!$frontSub || !$backSub) {
            throw ValidationException::withMessages(['document' => 'Front/back subtypes are not configured for this document type.']);
        }

        $existingFiles = Files::query()
            ->where('type', 'rider')
            ->where('type_id', $rider->id)
            ->orderByDesc('id')
            ->get();

        $existingFront = RiderDocumentChecklist::findFileForSide($existingFiles, $docType, 'front');
        $existingBack = RiderDocumentChecklist::findFileForSide($existingFiles, $docType, 'back');

        $frontUpload = $request->file('sides.front.file');
        $backUpload = $request->file('sides.back.file');

        if (!$existingFront && !$frontUpload instanceof UploadedFile) {
            throw ValidationException::withMessages(['sides.front.file' => 'Front side document is required.']);
        }
        if (!$existingBack && !$backUpload instanceof UploadedFile) {
            throw ValidationException::withMessages(['sides.back.file' => 'Back side document is required.']);
        }

        // Shared form fields once for the document; apply to both sides by key.
        $sharedValues = $request->input('field_values', []);
        if (!is_array($sharedValues) || $sharedValues === []) {
            $sharedValues = $request->input('sides.front.field_values', []);
        }
        if (!is_array($sharedValues)) {
            $sharedValues = [];
        }

        $frontFields = self::validateSideFields(
            (int) $docType->id,
            (int) $frontSub->id,
            $sharedValues,
            'field_values'
        );
        $backFields = self::validateSideFields(
            (int) $docType->id,
            (int) $backSub->id,
            $sharedValues,
            'field_values'
        );

        DB::transaction(function () use (
            $rider,
            $docType,
            $frontSub,
            $backSub,
            $frontUpload,
            $backUpload,
            $frontFields,
            $backFields,
            $existingFront,
            $existingBack,
            $branchId
        ) {
            self::upsertSideFile(
                $rider,
                $docType,
                $frontSub,
                'front',
                $frontUpload instanceof UploadedFile ? $frontUpload : null,
                $frontFields['field_values'],
                $existingFront,
                $branchId
            );
            self::upsertSideFile(
                $rider,
                $docType,
                $backSub,
                'back',
                $backUpload instanceof UploadedFile ? $backUpload : null,
                $backFields['field_values'],
                $existingBack,
                $branchId
            );

            RiderDocumentFieldValues::syncLinkedRiderColumns($rider, (int) $frontSub->id, $frontFields['field_values']);
            RiderDocumentFieldValues::syncLinkedRiderColumns($rider, (int) $backSub->id, $backFields['field_values']);
        });

        return ['message' => 'Document uploaded successfully.', 'reload' => true];
    }

    private static function storeSingle(
        Riders $rider,
        RiderDocumentType $docType,
        ?RiderDocumentTypeSubtype $mainSub,
        Request $request,
        ?int $branchId
    ): array {
        if (!$mainSub) {
            throw ValidationException::withMessages(['document' => 'Document subtype is not configured.']);
        }

        $existingFiles = Files::query()
            ->where('type', 'rider')
            ->where('type_id', $rider->id)
            ->orderByDesc('id')
            ->get();
        $existing = RiderDocumentChecklist::findFileForSide($existingFiles, $docType, 'main')
            ?? RiderDocumentChecklist::findFileForType($existingFiles, $docType);

        $upload = $request->file('sides.main.file');
        if (!$existing && !$upload instanceof UploadedFile) {
            throw ValidationException::withMessages(['sides.main.file' => 'Document file is required.']);
        }

        $fields = self::validateSideFields(
            (int) $docType->id,
            (int) $mainSub->id,
            $request->input('sides.main.field_values', []),
            'sides.main'
        );

        DB::transaction(function () use ($rider, $docType, $mainSub, $upload, $fields, $existing, $branchId) {
            self::upsertSideFile(
                $rider,
                $docType,
                $mainSub,
                'main',
                $upload instanceof UploadedFile ? $upload : null,
                $fields['field_values'],
                $existing,
                $branchId
            );
            RiderDocumentFieldValues::syncLinkedRiderColumns($rider, (int) $mainSub->id, $fields['field_values']);
        });

        return ['message' => 'Document uploaded successfully.', 'reload' => true];
    }

    /**
     * @param  array<string, mixed>  $fieldValues
     */
    private static function upsertSideFile(
        Riders $rider,
        RiderDocumentType $docType,
        RiderDocumentTypeSubtype $subtype,
        string $sideKey,
        ?UploadedFile $upload,
        array $fieldValues,
        ?Files $existing,
        ?int $branchId
    ): Files {
        $expiry = RiderDocumentFieldValues::resolveExpiryDateFromFields((int) $subtype->id, $fieldValues);
        $storedName = $sideKey === 'main' ? $docType->key : ($docType->key . '_' . $sideKey);

        $payload = [
            'type' => 'rider',
            'type_id' => $rider->id,
            'branch_id' => $branchId,
            'document_type_id' => $docType->id,
            'document_subtype_id' => $subtype->id,
            'field_values' => $fieldValues,
            'name' => $storedName,
            'status' => 1,
        ];
        if ($expiry !== null) {
            $payload['expiry_date'] = $expiry;
        }

        if ($upload instanceof UploadedFile) {
            $extension = $upload->extension() ?: $upload->getClientOriginalExtension() ?: 'bin';
            $diskName = 'rider-' . $rider->id . '-' . $sideKey . '-' . time() . '.' . $extension;
            $upload->storeAs('rider/' . $rider->id . '/', $diskName, 'public');
            $payload['file_name'] = $diskName;
            $payload['file_type'] = $extension;
        } elseif ($existing) {
            $payload['file_name'] = $existing->file_name;
            $payload['file_type'] = $existing->file_type;
            if ($expiry === null && $existing->expiry_date) {
                $payload['expiry_date'] = $existing->expiry_date;
            }
        }

        if ($existing) {
            $existing->fill($payload);
            $existing->save();

            return $existing;
        }

        return Files::create($payload);
    }

    /**
     * Active fields for a subtype (ordered).
     *
     * @return \Illuminate\Support\Collection<int, RiderDocumentTypeField>
     */
    public static function fieldsForSubtype(?RiderDocumentTypeSubtype $subtype)
    {
        if (!$subtype) {
            return collect();
        }

        return RiderDocumentTypeField::query()
            ->where('subtype_id', $subtype->id)
            ->where('is_active', true)
            ->orderedForAdmin()
            ->get();
    }

    /**
     * Unique fields for a dual document (front first, then back-only keys).
     *
     * @return \Illuminate\Support\Collection<int, RiderDocumentTypeField>
     */
    public static function sharedFieldsForDual(
        ?RiderDocumentTypeSubtype $front,
        ?RiderDocumentTypeSubtype $back
    ) {
        $merged = collect();
        foreach (self::fieldsForSubtype($front) as $field) {
            $merged->put($field->key, $field);
        }
        foreach (self::fieldsForSubtype($back) as $field) {
            if (!$merged->has($field->key)) {
                $merged->put($field->key, $field);
            }
        }

        return $merged->values();
    }

    /**
     * @param  array<string, mixed>|mixed  $raw
     * @return array{document_type_id: ?int, document_subtype_id: ?int, field_values: array<string, mixed>}
     */
    private static function validateSideFields(int $documentTypeId, int $subtypeId, mixed $raw, string $errorPrefix): array
    {
        try {
            return RiderDocumentFieldValues::validateAndNormalize($documentTypeId, $subtypeId, $raw);
        } catch (ValidationException $e) {
            $mapped = [];
            foreach ($e->errors() as $key => $messages) {
                // Keep field_values.* as-is when prefix is field_values (shared dual form).
                if ($errorPrefix === '' || ($errorPrefix === 'field_values' && str_starts_with($key, 'field_values.'))) {
                    $mapped[$key] = $messages;
                } else {
                    $mapped[$errorPrefix . '.' . $key] = $messages;
                }
            }

            throw ValidationException::withMessages($mapped);
        }
    }
}
