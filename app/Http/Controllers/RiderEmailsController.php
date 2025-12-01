<?php

namespace App\Http\Controllers;

use App\DataTables\RiderEmailsDataTable;
use App\Http\Requests\CreateRiderEmailsRequest;
use App\Http\Requests\UpdateRiderEmailsRequest;
use App\Http\Controllers\AppBaseController;
use App\Repositories\RiderEmailsRepository;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use Flash;

class RiderEmailsController extends AppBaseController
{
    use GlobalPagination;
    /** @var RiderEmailsRepository $riderEmailsRepository*/
    private $riderEmailsRepository;

    public function __construct(RiderEmailsRepository $riderEmailsRepo)
    {
        $this->riderEmailsRepository = $riderEmailsRepo;
    }

    /**
     * Display a listing of the RiderEmails.
     */
    public function index(RiderEmailsDataTable $riderEmailsDataTable)
    {
    return $riderEmailsDataTable->render('rider_emails.index');
    }


    /**
     * Show the form for creating a new RiderEmails.
     */
    public function create()
    {
        return view('rider_emails.create');
    }

    /**
     * Store a newly created RiderEmails in storage.
     */
    public function store(CreateRiderEmailsRequest $request)
    {
        $input = $request->all();

        $riderEmails = $this->riderEmailsRepository->create($input);

        Flash::success('Rider Emails saved successfully.');

        return redirect(route('riderEmails.index'));
    }

    /**
     * Display the specified RiderEmails.
     */
    public function show($id)
    {
        $riderEmails = $this->riderEmailsRepository->find($id);

        if (empty($riderEmails)) {
            Flash::error('Rider Emails not found');

            return redirect(route('riderEmails.index'));
        }

        return view('rider_emails.show')->with('riderEmails', $riderEmails);
    }

    /**
     * Show the form for editing the specified RiderEmails.
     */
    public function edit($id)
    {
        $riderEmails = $this->riderEmailsRepository->find($id);

        if (empty($riderEmails)) {
            Flash::error('Rider Emails not found');

            return redirect(route('riderEmails.index'));
        }

        return view('rider_emails.edit')->with('riderEmails', $riderEmails);
    }

    /**
     * Update the specified RiderEmails in storage.
     */
    public function update($id, UpdateRiderEmailsRequest $request)
    {
        $riderEmails = $this->riderEmailsRepository->find($id);

        if (empty($riderEmails)) {
            Flash::error('Rider Emails not found');

            return redirect(route('riderEmails.index'));
        }

        $riderEmails = $this->riderEmailsRepository->update($request->all(), $id);

        Flash::success('Rider Emails updated successfully.');

        return redirect(route('riderEmails.index'));
    }

    /**
     * Remove the specified RiderEmails from storage.
     *
     * @throws \Exception
     */
    public function destroy($id)
    {
        $riderEmails = $this->riderEmailsRepository->find($id);

        if (empty($riderEmails)) {
            Flash::error('Rider Emails not found');

            return redirect(route('riderEmails.index'));
        }

        $this->riderEmailsRepository->delete($id);

        Flash::success('Rider Emails deleted successfully.');

        return redirect(route('riderEmails.index'));
    }
}
