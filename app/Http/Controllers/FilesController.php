<?php

namespace App\Http\Controllers;

use App\DataTables\FilesDataTable;
use App\Http\Requests\CreateFilesRequest;
use App\Http\Requests\UpdateFilesRequest;
use App\Http\Controllers\AppBaseController;
use App\Repositories\FilesRepository;
use App\Models\Banks;
use App\Models\BikeRentCompany;
use App\Models\Bikes;
use App\Models\Customers;
use App\Models\Employee;
use App\Models\LeasingCompanies;
use App\Models\RiderDocumentType;
use App\Models\RiderDocumentTypeField;
use App\Models\RiderDocumentTypeSubtype;
use App\Models\Riders;
use App\Support\DocumentExpiry;
use App\Support\DocumentExpiryDashboard;
use App\Support\RiderDocumentChecklist;
use App\Support\RiderDocumentFieldValues;
use App\Support\RiderDocumentUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use App\Traits\GlobalPagination;
use Flash;
use App\Models\Files;

class FilesController extends AppBaseController
{
  use GlobalPagination;
  /** @var FilesRepository $filesRepository*/
  private $filesRepository;

  public function __construct(FilesRepository $filesRepo)
  {
    $this->filesRepository = $filesRepo;
  }

  /**
   * Display a listing of the Files.
   * Without type/type_id: documents module (all files).
   * With type/type_id: entity-scoped listing.
   */
  public function index(FilesDataTable $filesDataTable, Request $request)
  {
    $expiry = (string) $request->query('expiry', '');
    if (in_array($expiry, ['expiring', 'expired'], true) && Schema::hasTable('files')) {
      abort_unless(auth()->user()?->can('documents_view'), 403);

      $days = DocumentExpiry::windowDays((int) $request->query('days', DocumentExpiry::DEFAULT_WINDOW_DAYS));
      $user = auth()->user();
      $section = DocumentExpiryDashboard::listSectionForUser($user, $expiry);

      $filterLabel = $expiry === 'expired'
        ? __('Expired documents')
        : __('Documents expiring within :days days', ['days' => $days]);

      return view('files.expiry', [
        'expiry' => $expiry,
        'days' => $days,
        'filterLabel' => $filterLabel,
        'items' => $section['items'] ?? [],
        'byModule' => $section['by_module'] ?? [],
        'total' => (int) ($section['total'] ?? 0),
      ]);
    }

    $type = trim((string) $request->query('type', ''));
    $typeId = (int) $request->query('type_id', 0);

    if ($type === '') {
      abort_unless(auth()->user()?->can('documents_view'), 403);

      return $filesDataTable
        ->with(['all_files' => true])
        ->render('files.index');
    }

    $bikes = null;
    if ($type === 'bike' && $typeId > 0) {
      $bikes = Bikes::find($typeId);
    }

    return $filesDataTable
      ->with(['type' => $type, 'type_id' => $typeId > 0 ? $typeId : null, 'all_files' => false])
      ->render('files.entity_index', compact('bikes', 'type', 'typeId'));
  }


  /**
   * Show the form for creating a new Files.
   */
  public function create()
  {
    $type = trim((string) request('type', ''));
    if ($type === '') {
      abort_unless(auth()->user()?->can('documents_create'), 403);
    }

    return view('files.create');
  }

  /**
   * Active subtypes for a rider document type (upload form).
   */
  public function riderDocumentTypeSubtypes($company_slug, $id)
  {
    abort_unless(
      auth()->user()?->can('riders_documents_view')
        || auth()->user()?->can('riders_documents_create')
        || auth()->user()?->can('documents_view')
        || auth()->user()?->can('documents_create'),
      403
    );

    $docType = RiderDocumentType::query()->where('is_active', true)->findOrFail($id);
    $subtypes = RiderDocumentTypeSubtype::query()
      ->where('document_type_id', $docType->id)
      ->where('is_active', true)
      ->orderedForAdmin()
      ->get()
      ->map(fn (RiderDocumentTypeSubtype $s) => [
        'id' => $s->id,
        'document_type_id' => $s->document_type_id,
        'key' => $s->key,
        'label' => $s->label,
        'display_order' => $s->display_order,
        'is_active' => (bool) $s->is_active,
      ]);

    return response()->json([
      'success' => true,
      'document_type' => [
        'id' => $docType->id,
        'key' => $docType->key,
        'label' => $docType->label ?: ($docType->front_label ?: $docType->key),
        'type' => $docType->type,
      ],
      'subtypes' => $subtypes,
    ]);
  }

  /**
   * Active fields for a rider document subtype (upload form).
   */
  public function riderDocumentSubtypeFields($company_slug, $id)
  {
    abort_unless(
      auth()->user()?->can('riders_documents_view')
        || auth()->user()?->can('riders_documents_create')
        || auth()->user()?->can('documents_view')
        || auth()->user()?->can('documents_create'),
      403
    );

    $subtype = RiderDocumentTypeSubtype::query()
      ->where('is_active', true)
      ->with('documentType')
      ->findOrFail($id);

    $fields = RiderDocumentTypeField::query()
      ->where('subtype_id', $subtype->id)
      ->where('is_active', true)
      ->orderedForAdmin()
      ->get()
      ->map(fn (RiderDocumentTypeField $f) => [
        'id' => $f->id,
        'document_type_id' => $f->document_type_id,
        'subtype_id' => $f->subtype_id,
        'key' => $f->key,
        'label' => $f->label,
        'input_type' => $f->input_type,
        'is_required' => (bool) $f->is_required,
        'display_order' => $f->display_order,
        'rider_column' => $f->rider_column,
        'options' => $f->options,
        'is_active' => (bool) $f->is_active,
      ]);

    return response()->json([
      'success' => true,
      'subtype' => [
        'id' => $subtype->id,
        'document_type_id' => $subtype->document_type_id,
        'key' => $subtype->key,
        'label' => $subtype->label,
      ],
      'fields' => $fields,
    ]);
  }

  /**
   * Prefill linked field values from the rider profile for a subtype.
   */
  public function riderDocumentSubtypePrefill($company_slug, $id, Request $request)
  {
    abort_unless(
      auth()->user()?->can('riders_documents_view')
        || auth()->user()?->can('riders_documents_create')
        || auth()->user()?->can('documents_view')
        || auth()->user()?->can('documents_create'),
      403
    );

    $subtype = RiderDocumentTypeSubtype::query()->where('is_active', true)->findOrFail($id);
    $riderId = (int) $request->query('rider_id', 0);
    $rider = $riderId > 0 ? Riders::find($riderId) : null;

    return response()->json([
      'success' => true,
      'values' => RiderDocumentFieldValues::prefillFromRider($rider, (int) $subtype->id),
    ]);
  }

  /**
   * Typed rider document upload modal (single or dual) — no type/subtype selectors.
   */
  public function riderDocumentUploadForm($company_slug, $riderId, $documentTypeId)
  {
    abort_unless(
      auth()->user()?->can('riders_documents_create')
        || auth()->user()?->can('riders_documents_edit')
        || auth()->user()?->can('documents_create')
        || auth()->user()?->can('documents_edit'),
      403
    );

    $riders = Riders::findOrFail((int) $riderId);
    if (!empty($riders->branch_id) && !in_array($riders->branch_id, app('user_branches'))) {
      abort(403);
    }

    $docType = RiderDocumentType::query()->where('is_active', true)->findOrFail((int) $documentTypeId);
    $subs = RiderDocumentChecklist::resolveSubtypes($docType);

    $existingFiles = Files::query()
      ->where('type', 'rider')
      ->where('type_id', $riders->id)
      ->orderByDesc('id')
      ->get();

    // Only show field values already stored on the document file(s) — never rider-profile prefill.
    $buildSide = function (?RiderDocumentTypeSubtype $subtype, string $sideKey) use ($docType, $existingFiles) {
      $file = RiderDocumentChecklist::findFileForSide($existingFiles, $docType, $sideKey);
      if ($sideKey === 'main' && !$file) {
        $file = RiderDocumentChecklist::findFileForType($existingFiles, $docType);
      }

      $fields = RiderDocumentUploadService::fieldsForSubtype($subtype);
      $values = ($file && is_array($file->field_values)) ? $file->field_values : [];

      return [
        'subtype' => $subtype,
        'file' => $file,
        'fields' => $fields,
        'values' => $values,
      ];
    };

    $front = $docType->type === 'dual' ? $buildSide($subs['front'], 'front') : null;
    $back = $docType->type === 'dual' ? $buildSide($subs['back'], 'back') : null;
    $main = $docType->type !== 'dual' ? $buildSide($subs['main'], 'main') : null;

    $shared = null;
    if ($docType->type === 'dual') {
      $sharedFields = RiderDocumentUploadService::sharedFieldsForDual(
        $subs['front'] ?? null,
        $subs['back'] ?? null
      );
      $sharedValues = [];
      if (!empty($front['values']) && is_array($front['values'])) {
        $sharedValues = $front['values'];
      }
      if (!empty($back['values']) && is_array($back['values'])) {
        foreach ($back['values'] as $k => $v) {
          if (!array_key_exists($k, $sharedValues) || $sharedValues[$k] === null || $sharedValues[$k] === '') {
            $sharedValues[$k] = $v;
          }
        }
      }
      $shared = [
        'fields' => $sharedFields,
        'values' => $sharedValues,
      ];
    }

    $existingAny = $docType->type === 'dual'
      ? (($front['file'] ?? null) || ($back['file'] ?? null))
      : (($main['file'] ?? null) !== null);

    return view('files.rider_document_upload', compact(
      'riders',
      'docType',
      'front',
      'back',
      'main',
      'shared',
      'existingAny'
    ));
  }

  /**
   * Persist typed rider document upload (single or dual sides).
   */
  public function riderDocumentUploadStore($company_slug, $riderId, $documentTypeId, Request $request)
  {
    abort_unless(
      auth()->user()?->can('riders_documents_create')
        || auth()->user()?->can('riders_documents_edit')
        || auth()->user()?->can('documents_create')
        || auth()->user()?->can('documents_edit'),
      403
    );

    $rider = Riders::findOrFail((int) $riderId);
    if (!empty($rider->branch_id) && !in_array($rider->branch_id, app('user_branches'))) {
      abort(403);
    }

    $docType = RiderDocumentType::query()->where('is_active', true)->findOrFail((int) $documentTypeId);

    try {
      $result = RiderDocumentUploadService::store($rider, $docType, $request);
    } catch (ValidationException $e) {
      return response()->json([
        'message' => 'Please fix the highlighted errors.',
        'errors' => $e->errors(),
      ], 422);
    }

    return response()->json($result);
  }

  /**
   * Store a newly created Files in storage.
   */
  public function store(CreateFilesRequest $request)
  {
    $input = $request->all();
    $type = trim((string) ($input['type'] ?? ''));
    $typeId = (int) ($input['type_id'] ?? 0);

    if ($type === '' || $typeId < 1) {
      abort_unless(auth()->user()?->can('documents_create'), 403);
      $input['type'] = null;
      $input['type_id'] = null;
      $type = '';
      $typeId = 0;
    } else {
      $input['type'] = $type;
      $input['type_id'] = $typeId;
    }

    $normalizedFields = [
      'document_type_id' => null,
      'document_subtype_id' => null,
      'field_values' => [],
    ];
    if ($type === 'rider' && $typeId > 0) {
      $normalizedFields = RiderDocumentFieldValues::validateAndNormalize(
        isset($input['document_type_id']) && $input['document_type_id'] !== '' ? (int) $input['document_type_id'] : null,
        isset($input['document_subtype_id']) && $input['document_subtype_id'] !== '' ? (int) $input['document_subtype_id'] : null,
        $input['field_values'] ?? []
      );
    }

    if (isset($input['file_name'])) {
      $extension = $input['file_name']->extension();
      $storageDir = ($type !== '' && $typeId > 0)
        ? $type . '/' . $typeId
        : 'documents';
      $namePrefix = ($type !== '' && $typeId > 0)
        ? $type . '-' . $typeId
        : 'documents';
      $name = $namePrefix . '-' . time() . '.' . $extension;
      $input['file_name']->storeAs($storageDir . '/', $name, 'public');

      if (empty($input['suggested_name'])) {
        $input['name'] = $input['file_name']->getClientOriginalName();
      } else {
        $input['name'] = $input['suggested_name'];
      }

      $input['file_name'] = $name;
      $input['file_type'] = $extension;
    }

    // Rider typed uploads: always store the canonical type/subtype key as name so
    // each document type stays unique (never share one file across checklist slots).
    if ($type === 'rider' && !empty($normalizedFields['document_type_id'])) {
      $docType = RiderDocumentType::query()->find((int) $normalizedFields['document_type_id']);
      $subtype = !empty($normalizedFields['document_subtype_id'])
        ? RiderDocumentTypeSubtype::query()->find((int) $normalizedFields['document_subtype_id'])
        : null;
      $canonicalName = RiderDocumentFieldValues::suggestedNameFragment($docType, $subtype);
      if ($canonicalName) {
        $input['name'] = $canonicalName;
      }
    }

    if (array_key_exists('expiry_date', $input) && $input['expiry_date'] === '') {
      $input['expiry_date'] = null;
    }

    $input['branch_id'] = $this->resolveBranchIdForFile($type, $typeId);
    $input['document_type_id'] = $normalizedFields['document_type_id'];
    $input['document_subtype_id'] = $normalizedFields['document_subtype_id'];
    $input['field_values'] = $normalizedFields['field_values'];

    if ($type === 'rider' && !empty($normalizedFields['document_subtype_id'])) {
      $fromFields = RiderDocumentFieldValues::resolveExpiryDateFromFields(
        (int) $normalizedFields['document_subtype_id'],
        $normalizedFields['field_values']
      );
      if ($fromFields !== null) {
        $input['expiry_date'] = $fromFields;
      }
    }

    DB::transaction(function () use ($input, $type, $typeId, $normalizedFields) {
      // One file per rider document type/subtype: replace instead of creating duplicates.
      $existingTyped = null;
      if (
        $type === 'rider'
        && $typeId > 0
        && !empty($normalizedFields['document_type_id'])
      ) {
        $existingQuery = Files::query()
          ->where('type', 'rider')
          ->where('type_id', $typeId)
          ->where('document_type_id', (int) $normalizedFields['document_type_id'])
          ->orderByDesc('id');
        if (!empty($normalizedFields['document_subtype_id'])) {
          $existingQuery->where('document_subtype_id', (int) $normalizedFields['document_subtype_id']);
        } else {
          $existingQuery->where(function ($q) {
            $q->whereNull('document_subtype_id')->orWhere('document_subtype_id', 0);
          });
        }
        $existingTyped = $existingQuery->first();
      }

      if ($existingTyped) {
        // Drop previous disk file when replacing with a new upload.
        if (!empty($input['file_name']) && $existingTyped->file_name && $existingTyped->file_name !== $input['file_name']) {
          $relativeDir = self::storageRelativePath($existingTyped);
          foreach (
            [
              storage_path('app/' . $relativeDir),
              storage_path('app/public/' . $relativeDir),
            ] as $filePath
          ) {
            if (is_file($filePath)) {
              @unlink($filePath);
            }
          }
        }
        $this->filesRepository->update($input, $existingTyped->id);
      } else {
        $this->filesRepository->create($input);
      }

      if ($type === 'rider' && $typeId > 0 && !empty($normalizedFields['document_subtype_id'])) {
        $rider = Riders::find($typeId);
        if ($rider) {
          RiderDocumentFieldValues::syncLinkedRiderColumns(
            $rider,
            (int) $normalizedFields['document_subtype_id'],
            $normalizedFields['field_values']
          );
        }
      }
    });

    if (!empty($type) && !empty($typeId)) {
      return response()->json(['message' => 'File uploaded successfully.', 'reload' => true]);
    }
    return response()->json(['message' => 'File uploaded successfully.', 'reload' => false]);
  }

  /**
   * Display the specified Files.
   */
  public function show($company_slug, $id)
  {
    $files = $this->filesRepository->find($id);

    if (empty($files)) {
      Flash::error('Files not found');

      return redirect(route('files.index'));
    }

    return view('files.show')->with('files', $files);
  }

  /**
   * Show the form for editing the specified Files.
   */
  public function edit($company_slug, $id)
  {
    $files = $this->filesRepository->find($id);

    if (empty($files)) {
      Flash::error('Files not found');

      return redirect(route('files.index'));
    }

    return view('files.edit')->with('files', $files);
  }

  /**
   * Update the specified Files in storage.
   */
  public function update($company_slug, $id, UpdateFilesRequest $request)
  {
    $files = $this->filesRepository->find($id);

    if (empty($files)) {
      if ($request->ajax() || $request->wantsJson()) {
        return response()->json(['message' => 'Files not found'], 404);
      }
      Flash::error('Files not found');

      return redirect(route('files.index'));
    }

    $input = $request->all();
    $type = trim((string) ($files->type ?? $input['type'] ?? ''));
    $typeId = (int) ($files->type_id ?? $input['type_id'] ?? 0);

    $normalizedFields = [
      'document_type_id' => $files->document_type_id,
      'document_subtype_id' => $files->document_subtype_id,
      'field_values' => is_array($files->field_values) ? $files->field_values : [],
    ];

    if ($type === 'rider' && $typeId > 0) {
      $normalizedFields = RiderDocumentFieldValues::validateAndNormalize(
        isset($input['document_type_id']) && $input['document_type_id'] !== '' ? (int) $input['document_type_id'] : null,
        isset($input['document_subtype_id']) && $input['document_subtype_id'] !== '' ? (int) $input['document_subtype_id'] : null,
        $input['field_values'] ?? []
      );
      $input['document_type_id'] = $normalizedFields['document_type_id'];
      $input['document_subtype_id'] = $normalizedFields['document_subtype_id'];
      $input['field_values'] = $normalizedFields['field_values'];
    }

    if ($request->hasFile('file_name')) {
      $uploaded = $request->file('file_name');
      $extension = $uploaded->extension();
      $storageDir = ($type !== '' && $typeId > 0)
        ? $type . '/' . $typeId
        : 'documents';
      $namePrefix = ($type !== '' && $typeId > 0)
        ? $type . '-' . $typeId
        : 'documents';
      $name = $namePrefix . '-' . time() . '.' . $extension;
      $uploaded->storeAs($storageDir . '/', $name, 'public');
      $input['file_name'] = $name;
      $input['file_type'] = $extension;
      if (!empty($input['suggested_name'])) {
        $input['name'] = $input['suggested_name'];
      }
    } else {
      unset($input['file_name']);
      if (!empty($input['suggested_name'])) {
        $input['name'] = $input['suggested_name'];
      }
    }

    if ($type === 'rider' && !empty($normalizedFields['document_type_id'])) {
      $docType = RiderDocumentType::query()->find((int) $normalizedFields['document_type_id']);
      $subtype = !empty($normalizedFields['document_subtype_id'])
        ? RiderDocumentTypeSubtype::query()->find((int) $normalizedFields['document_subtype_id'])
        : null;
      $canonicalName = RiderDocumentFieldValues::suggestedNameFragment($docType, $subtype);
      if ($canonicalName) {
        $input['name'] = $canonicalName;
      }
    }

    if (array_key_exists('expiry_date', $input) && $input['expiry_date'] === '') {
      $input['expiry_date'] = null;
    }

    if ($type === 'rider' && !empty($normalizedFields['document_subtype_id'])) {
      $fromFields = RiderDocumentFieldValues::resolveExpiryDateFromFields(
        (int) $normalizedFields['document_subtype_id'],
        $normalizedFields['field_values']
      );
      if ($fromFields !== null) {
        $input['expiry_date'] = $fromFields;
      }
    }

    DB::transaction(function () use ($input, $id, $type, $typeId, $normalizedFields) {
      $this->filesRepository->update($input, $id);

      if ($type === 'rider' && $typeId > 0 && !empty($normalizedFields['document_subtype_id'])) {
        $rider = Riders::find($typeId);
        if ($rider) {
          RiderDocumentFieldValues::syncLinkedRiderColumns(
            $rider,
            (int) $normalizedFields['document_subtype_id'],
            $normalizedFields['field_values']
          );
        }
      }
    });

    if ($request->ajax() || $request->wantsJson()) {
      return response()->json(['message' => 'File updated successfully.', 'reload' => true]);
    }

    Flash::success('Files updated successfully.');

    return redirect(route('files.index'));
  }

  /**
   * Remove the specified Files from storage.
   *
   * @throws \Exception
   */
  public function destroy($company_slug, $id)
  {
    $files = $this->filesRepository->find($id);
    if (!empty($files)) {
      $relativeDir = $this->storageRelativePath($files);
      foreach (
        [
          storage_path('app/' . $relativeDir),
          storage_path('app/public/' . $relativeDir),
        ] as $filePath
      ) {
        if (file_exists($filePath)) {
          unlink($filePath);
        }
      }
    }

    if (empty($files)) {

      if (request()->ajax()) {
        return response()->json([
          'success' => false,
          'message' => 'Files not found'
        ], 404);
      }

      Flash::error('Files not found');
      return redirect(route('files.index'));
    }

    $this->filesRepository->delete($id);

    if (request()->ajax()) {
      return response()->json([
        'success' => true,
        'message' => 'Files deleted successfully.'
      ], 200);
    }

    Flash::success('Files deleted successfully.');
    return redirect(route('files.index'));
  }

  public static function storageRelativePath(object $files): string
  {
    $type = trim((string) ($files->type ?? ''));
    $typeId = (int) ($files->type_id ?? 0);
    $fileName = (string) ($files->file_name ?? '');

    if ($type !== '' && $typeId > 0) {
      return $type . '/' . $typeId . '/' . $fileName;
    }

    return 'documents/' . $fileName;
  }

  private function resolveBranchIdForFile(string $type, int $typeId): ?int
  {
    if ($typeId <= 0 || $type === '') {
      return $this->resolveUserBranchId();
    }

    $branchId = match ($type) {
      'bike' => Bikes::whereKey($typeId)->value('branch_id'),
      'rider' => Riders::whereKey($typeId)->value('branch_id'),
      'customer' => Customers::whereKey($typeId)->value('branch_id'),
      'rentCompany' => BikeRentCompany::whereKey($typeId)->value('branch_id'),
      'employee' => Employee::whereKey($typeId)->value('branch_id'),
      'leasing_company' => LeasingCompanies::whereKey($typeId)->value('branch_id'),
      'bank' => Banks::whereKey($typeId)->value('branch_id'),
      default => null,
    };

    if ($branchId !== null) {
      return (int) $branchId;
    }

    return $this->resolveUserBranchId();
  }

  private function resolveUserBranchId(): ?int
  {
    $user = Auth::user();
    if ($user?->employee_id) {
      $branchId = Employee::whereKey($user->employee_id)->value('branch_id');
      if ($branchId !== null) {
        return (int) $branchId;
      }
    }

    $userBranches = app('user_branches');
    if (count($userBranches) === 1) {
      return (int) $userBranches[0];
    }

    return null;
  }
}
