<?php

namespace App\Http\Controllers;

use App\DataTables\LedgerDataTable;
use App\Exports\LedgerExport;
use App\Models\LedgerEntry;
use App\Models\Transactions;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use Maatwebsite\Excel\Facades\Excel;

use Yajra\DataTables\DataTables;

class LedgerController extends Controller
{


  public function index(LedgerDataTable $LedgerDataTable)
  {
    /* $summary = null;
    if (request('account')) {

      if (request('billing_month')) {
        $summary = LedgerEntry::where('account_id', request('account_id'));
        $summary = $summary->where('billing_month', request('billing_month'));
        $summary = $summary->first();
      }

    } */
    if (!auth()->user()->hasPermissionTo('gn_ledger')) {
      abort(403, 'Unauthorized action.');
    }
    return $LedgerDataTable->render('ledger.index');
  }

  public function ledger()
  {

    $transactions = Transactions::paginate(5);
    return view('ledger.ledger', compact('transactions'));
  }

  public function export(Request $request)
  {
    if (!auth()->user()->hasPermissionTo('gn_ledger')) {
      abort(403, 'Unauthorized action.');
    }

    $account_id = $request->get('account');
    $month = $request->get('month');

    $filename = 'ledger_' . ($account_id ? 'account_' . $account_id . '_' : '') . ($month ? $month . '_' : '') . date('Y-m-d_H-i-s') . '.xlsx';

    return Excel::download(new LedgerExport($account_id, $month), $filename);
  }
}
