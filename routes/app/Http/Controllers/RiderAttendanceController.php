<?php

namespace App\Http\Controllers;

use App\DataTables\RiderAttendanceDataTable;
use App\Http\Requests\CreateRiderAttendanceRequest;
use App\Http\Requests\UpdateRiderAttendanceRequest;
use App\Http\Controllers\AppBaseController;
use App\Imports\ImportRiderAttendance;
use App\Imports\RiderAttendance;
use App\Repositories\RiderAttendanceRepository;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use Maatwebsite\Excel\Facades\Excel;
use Flash;

class RiderAttendanceController extends AppBaseController
{
    use GlobalPagination;
  /** @var RiderAttendanceRepository $riderAttendanceRepository*/
  private $riderAttendanceRepository;

  public function __construct(RiderAttendanceRepository $riderAttendanceRepo)
  {
    $this->riderAttendanceRepository = $riderAttendanceRepo;
  }

  /**
   * Display a listing of the RiderAttendance.
   */
  public function index(RiderAttendanceDataTable $riderAttendanceDataTable)
  {
    return $riderAttendanceDataTable->render('rider_attendances.index');
  }


  /**
   * Show the form for creating a new RiderAttendance.
   */
  public function create()
  {
    return view('rider_attendances.create');
  }

  /**
   * Store a newly created RiderAttendance in storage.
   */
  public function store(CreateRiderAttendanceRequest $request)
  {
    $input = $request->all();

    $riderAttendance = $this->riderAttendanceRepository->create($input);

    Flash::success('Rider Attendance saved successfully.');

    return redirect(route('riderAttendances.index'));
  }

  /**
   * Display the specified RiderAttendance.
   */
  public function show($id)
  {
    $riderAttendance = $this->riderAttendanceRepository->find($id);

    if (empty($riderAttendance)) {
      Flash::error('Rider Attendance not found');

      return redirect(route('riderAttendances.index'));
    }

    return view('rider_attendances.show')->with('riderAttendance', $riderAttendance);
  }

  /**
   * Show the form for editing the specified RiderAttendance.
   */
  public function edit($id)
  {
    $riderAttendance = $this->riderAttendanceRepository->find($id);

    if (empty($riderAttendance)) {
      Flash::error('Rider Attendance not found');

      return redirect(route('riderAttendances.index'));
    }

    return view('rider_attendances.edit')->with('riderAttendance', $riderAttendance);
  }

  /**
   * Update the specified RiderAttendance in storage.
   */
  public function update($id, UpdateRiderAttendanceRequest $request)
  {
    $riderAttendance = $this->riderAttendanceRepository->find($id);

    if (empty($riderAttendance)) {
      Flash::error('Rider Attendance not found');

      return redirect(route('riderAttendances.index'));
    }

    $riderAttendance = $this->riderAttendanceRepository->update($request->all(), $id);

    Flash::success('Rider Attendance updated successfully.');

    return redirect(route('riderAttendances.index'));
  }

  /**
   * Remove the specified RiderAttendance from storage.
   *
   * @throws \Exception
   */
  public function destroy($id)
  {
    $riderAttendance = $this->riderAttendanceRepository->find($id);

    if (empty($riderAttendance)) {
      Flash::error('Rider Attendance not found');

      return redirect(route('riderAttendances.index'));
    }

    $this->riderAttendanceRepository->delete($id);

    Flash::success('Rider Attendance deleted successfully.');

    return redirect(route('riderAttendances.index'));
  }

  public function import(Request $request)
  {
    if ($request->isMethod('post')) {
      $rules = [
        'file' => 'required|max:50000|mimes:xlsx'
      ];
      $message = [
        'file.required' => 'Excel File Required'
      ];
      $this->validate($request, $rules, $message);
      Excel::import(new ImportRiderAttendance(), $request->file('file'));
    }

    return view('rider_attendances.import');
  }
}
