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
use App\Models\Riders;
use App\Support\DocumentExpiry;
use App\Support\DocumentExpiryDashboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Traits\GlobalPagination;
use Flash;

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

    if (array_key_exists('expiry_date', $input) && $input['expiry_date'] === '') {
      $input['expiry_date'] = null;
    }

    $input['branch_id'] = $this->resolveBranchIdForFile($type, $typeId);

    $this->filesRepository->create($input);
    if(!empty($type) && !empty($typeId)) {
      
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
      Flash::error('Files not found');

      return redirect(route('files.index'));
    }

    $input = $request->all();
    if (array_key_exists('expiry_date', $input) && $input['expiry_date'] === '') {
      $input['expiry_date'] = null;
    }

    $files = $this->filesRepository->update($input, $id);

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
