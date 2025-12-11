<?php

namespace App\Http\Controllers;

use App\DataTables\FilesDataTable;
use App\Http\Requests\CreateFilesRequest;
use App\Http\Requests\UpdateFilesRequest;
use App\Http\Controllers\AppBaseController;
use App\Repositories\FilesRepository;
use App\Models\Bikes;
use Illuminate\Http\Request;
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
   */
  public function index(FilesDataTable $filesDataTable)
  {

    if (!request('type')) {
      abort(404);
    }
    $bikeId = request()->input('type_id'); // or request('bike_id')
    $bikes  = Bikes::find($bikeId);
    if (empty($bikes)) {
      Flash::error('Bikes not found');
      return redirect(route('bikes.index'));
    }
    return $filesDataTable->with(['type' => request('type') ?? 1, 'type_id' => request('type_id') ?? 1,])->render('files.index' , compact('bikes'));
  }


  /**
   * Show the form for creating a new Files.
   */
  public function create()
  {
    return view('files.create');
  }

  /**
   * Store a newly created Files in storage.
   */
  public function store(CreateFilesRequest $request)
  {
    
    $input = $request->all();

    if (isset($input['file_name'])) {

      $extension = $input['file_name']->extension();
      $name = $input['type'] . '-' . $input['type_id'] . '-' . time() . '.' . $extension;
      $input['file_name']->storeAs($input['type'] . '/' . $input['type_id'] . '/', $name);

      $input['name'] = $input['file_name']->getClientOriginalName();
      $input['file_name'] = $name;
      $input['file_type'] = $extension;
    }



    $files = $this->filesRepository->create($input);

    return response()->json(['message' => 'File uploaded successfully.']);

  }

  /**
   * Display the specified Files.
   */
  public function show($id)
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
  public function edit($id)
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
  public function update($id, UpdateFilesRequest $request)
  {
    $files = $this->filesRepository->find($id);

    if (empty($files)) {
      Flash::error('Files not found');

      return redirect(route('files.index'));
    }

    $files = $this->filesRepository->update($request->all(), $id);

    Flash::success('Files updated successfully.');

    return redirect(route('files.index'));
  }

  /**
   * Remove the specified Files from storage.
   *
   * @throws \Exception
   */
  public function destroy($id)
  {
    $files = $this->filesRepository->find($id);
    if (file_exists(storage_path('app/' . $files->type . '/' . $files->type_id . '/' . $files->file_name))) {
      unlink(storage_path('app/' . $files->type . '/' . $files->type_id . '/' . $files->file_name));

    }

    if (empty($files)) {
      Flash::error('Files not found');

      return redirect(route('files.index'));
    }

    $this->filesRepository->delete($id);

    Flash::success('Files deleted successfully.');

    return redirect(route('files.index'));
  }
}
