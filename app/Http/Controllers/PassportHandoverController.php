<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PassportHandoverHistory;
use App\Models\Riders;
use App\Models\Settings;
use App\Services\Agreements\AgreementPdfBranding;
use App\Support\CompanyAuthRedirect;
use App\Support\CompanyContext;
use App\Traits\GlobalPagination;
use Carbon\Carbon;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PassportHandoverController extends AppBaseController
{
    use GlobalPagination;

    public function index(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->to(CompanyAuthRedirect::url($request))->with('error', 'Please log in to access this page.');
        }

        if (!user_can('passport_handover_view')) {
            abort(403, 'Unauthorized action.');
        }

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $statusFilter = $request->input('status_filter', '');

        $riders = $this->buildPersonList('rider', $request, $statusFilter);
        $employees = $this->buildPersonList('employee', $request, $statusFilter);

        $persons = collect($riders)->merge($employees)->sortBy('name')->values();

        $issuedCount = PassportHandoverHistory::where('status', PassportHandoverHistory::STATUS_ISSUED)->count();
        $returnedCount = PassportHandoverHistory::where('status', PassportHandoverHistory::STATUS_RETURNED)->count();

        $topEnabledRaw = (string) (Settings::query()
            ->where('name', 'passport_handover_top_enabled')
            ->value('value') ?? '1');
        $topEnabled = in_array(strtolower(trim($topEnabledRaw)), ['1', 'true', 'yes', 'on'], true);

        $paginated = $this->paginateCollection($persons, $paginationParams);

        if ($request->ajax()) {
            $tableData = view('passport_handover.person_table', [
                'persons' => $paginated,
            ])->render();
            $paginationLinks = $paginated->links('components.global-pagination')->render();

            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
                'stats' => [
                    'issued' => $issuedCount,
                    'returned' => $returnedCount,
                ],
            ]);
        }

        return view('passport_handover.index', [
            'persons' => $paginated,
            'issuedCount' => $issuedCount,
            'returnedCount' => $returnedCount,
            'topEnabled' => $topEnabled,
            'statusFilter' => $statusFilter,
            'riders' => Riders::orderBy('name')->get(['id', 'name', 'rider_id', 'passport']),
            'employees' => Employee::orderBy('name')->get(['id', 'name', 'employee_id', 'passport']),
        ]);
    }

    public function history(Request $request, string $company_slug, string $type, int $id)
    {
        if (!user_can('passport_handover_view')) {
            abort(403, 'Unauthorized action.');
        }

        $person = $this->resolvePerson($type, $id);
        if (!$person) {
            abort(404, 'Person not found.');
        }

        $histories = PassportHandoverHistory::query()
            ->with(['rider', 'employee', 'createdByUser', 'updatedByUser'])
            ->where('holder_type', $type)
            ->when($type === 'rider', fn ($q) => $q->where('rider_id', $id))
            ->when($type === 'employee', fn ($q) => $q->where('employee_id', $id))
            ->orderByDesc('note_date')
            ->orderByDesc('id')
            ->get();

        $hasOpenIssue = $histories->contains(fn ($h) => $h->isOpen());

        return view('passport_handover.history', [
            'person' => $person,
            'holderType' => $type,
            'holderId' => $id,
            'histories' => $histories,
            'hasOpenIssue' => $hasOpenIssue,
        ]);
    }

    public function issueForm(string $company_slug, string $type, int $id)
    {
        if (!user_can('passport_handover_issue')) {
            abort(403, 'Unauthorized action.');
        }

        $person = $this->resolvePerson($type, $id);
        if (!$person) {
            abort(404, 'Person not found.');
        }

        $openIssue = PassportHandoverHistory::query()
            ->where('holder_type', $type)
            ->where('status', PassportHandoverHistory::STATUS_ISSUED)
            ->when($type === 'rider', fn ($q) => $q->where('rider_id', $id))
            ->when($type === 'employee', fn ($q) => $q->where('employee_id', $id))
            ->exists();

        if ($openIssue) {
            return response()->json(['message' => 'This person already has an open passport issue. Return it before issuing again.'], 422);
        }

        return view('passport_handover.issue_modal', [
            'person' => $person,
            'holderType' => $type,
            'holderId' => $id,
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name', 'employee_id']),
            'defaultReceivedBy' => $type === 'employee' ? $person->name : '',
        ]);
    }

    public function issueStore(Request $request, string $company_slug, string $type, int $id)
    {
        if (!user_can('passport_handover_issue')) {
            abort(403, 'Unauthorized action.');
        }

        $person = $this->resolvePerson($type, $id);
        if (!$person) {
            abort(404, 'Person not found.');
        }

        $request->validate([
            'holder_name' => 'required|string|max:255',
            'passport_number' => 'nullable|string|max:100',
            'handed_over_by' => 'required|string|max:255',
            'received_by' => 'required|string|max:255',
            'note_date' => 'required|date',
            'remarks' => 'nullable|string|max:65535',
        ]);

        $openIssue = PassportHandoverHistory::query()
            ->where('holder_type', $type)
            ->where('status', PassportHandoverHistory::STATUS_ISSUED)
            ->when($type === 'rider', fn ($q) => $q->where('rider_id', $id))
            ->when($type === 'employee', fn ($q) => $q->where('employee_id', $id))
            ->exists();

        if ($openIssue) {
            Flash::error('This person already has an open passport issue.');

            return redirect()->route('passportHandover.history', ['type' => $type, 'id' => $id]);
        }

        $branchId = $type === 'rider' ? ($person->branch_id ?? null) : ($person->branch_id ?? null);

        PassportHandoverHistory::create([
            'branch_id' => $branchId,
            'rider_id' => $type === 'rider' ? $id : null,
            'employee_id' => $type === 'employee' ? $id : null,
            'holder_type' => $type,
            'holder_name' => $request->holder_name,
            'passport_number' => $request->passport_number ?: ($person->passport ?? null),
            'handed_over_by' => $request->handed_over_by,
            'received_by' => $request->received_by,
            'note_date' => Carbon::parse($request->note_date),
            'remarks' => $request->remarks,
            'status' => PassportHandoverHistory::STATUS_ISSUED,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        Flash::success('Passport issued successfully.');

        return redirect()->route('passportHandover.history', ['type' => $type, 'id' => $id]);
    }

    public function returnForm(string $company_slug, string $type, int $id)
    {
        if (!user_can('passport_handover_return')) {
            abort(403, 'Unauthorized action.');
        }

        $person = $this->resolvePerson($type, $id);
        if (!$person) {
            abort(404, 'Person not found.');
        }

        $openHistory = PassportHandoverHistory::query()
            ->where('holder_type', $type)
            ->where('status', PassportHandoverHistory::STATUS_ISSUED)
            ->when($type === 'rider', fn ($q) => $q->where('rider_id', $id))
            ->when($type === 'employee', fn ($q) => $q->where('employee_id', $id))
            ->orderByDesc('id')
            ->first();

        if (!$openHistory) {
            return response()->json(['message' => 'No open passport issue found for this person.'], 422);
        }

        return view('passport_handover.return_modal', [
            'person' => $person,
            'holderType' => $type,
            'holderId' => $id,
            'history' => $openHistory,
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name', 'employee_id']),
            'defaultReceivedBy' => optional(auth()->user()->employee)->name ?? auth()->user()->name ?? '',
        ]);
    }

    public function returnStore(Request $request, string $company_slug, string $type, int $id)
    {
        if (!user_can('passport_handover_return')) {
            abort(403, 'Unauthorized action.');
        }

        $person = $this->resolvePerson($type, $id);
        if (!$person) {
            abort(404, 'Person not found.');
        }

        $request->validate([
            'returned_by' => 'required|string|max:255',
            'return_received_by' => 'required|string|max:255',
            'return_date' => 'required|date',
            'remarks' => 'nullable|string|max:65535',
        ]);

        $openHistory = PassportHandoverHistory::query()
            ->where('holder_type', $type)
            ->where('status', PassportHandoverHistory::STATUS_ISSUED)
            ->when($type === 'rider', fn ($q) => $q->where('rider_id', $id))
            ->when($type === 'employee', fn ($q) => $q->where('employee_id', $id))
            ->orderByDesc('id')
            ->first();

        if (!$openHistory) {
            Flash::error('No open passport issue found for this person.');

            return redirect()->route('passportHandover.history', ['type' => $type, 'id' => $id]);
        }

        $returnDate = Carbon::parse($request->return_date);
        if ($openHistory->note_date && $returnDate->lt($openHistory->note_date)) {
            Flash::error('Return date cannot be before the issue date.');

            return redirect()->route('passportHandover.history', ['type' => $type, 'id' => $id]);
        }

        $remarks = trim((string) ($openHistory->remarks ?? ''));
        if ($request->filled('remarks')) {
            $remarks = $remarks !== '' ? $remarks . "\n\nReturn: " . $request->remarks : $request->remarks;
        }

        $openHistory->update([
            'returned_by' => $request->returned_by,
            'return_received_by' => $request->return_received_by,
            'return_date' => $returnDate,
            'remarks' => $remarks ?: null,
            'status' => PassportHandoverHistory::STATUS_RETURNED,
            'updated_by' => Auth::id(),
        ]);

        Flash::success('Passport returned successfully.');

        return redirect()->route('passportHandover.history', ['type' => $type, 'id' => $id]);
    }

    public function issueContract(string $company_slug, int $id)
    {
        if (!user_can('passport_handover_print')) {
            abort(403, 'Unauthorized action.');
        }

        $history = PassportHandoverHistory::with(['rider', 'employee'])->findOrFail($id);

        return view('passport_handover.issue_contract', [
            'history' => $history,
            'branding' => $this->contractBranding(),
        ]);
    }

    public function returnContract(string $company_slug, int $id)
    {
        if (!user_can('passport_handover_print')) {
            abort(403, 'Unauthorized action.');
        }

        $history = PassportHandoverHistory::with(['rider', 'employee'])->findOrFail($id);

        if (!$history->return_date) {
            abort(404, 'Return document is only available for returned passports.');
        }

        return view('passport_handover.return_contract', [
            'history' => $history,
            'branding' => $this->contractBranding(),
        ]);
    }

    private function contractBranding(): array
    {
        return app(AgreementPdfBranding::class)->forCompany(CompanyContext::id());
    }

    private function resolvePerson(string $type, int $id)
    {
        if ($type === 'rider') {
            return Riders::find($id);
        }

        if ($type === 'employee') {
            return Employee::find($id);
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildPersonList(string $type, Request $request, string $statusFilter): array
    {
        $term = trim((string) $request->input('quick_search', ''));
        $persons = [];

        if ($type === 'rider') {
            $query = Riders::query()->orderBy('name');
            if ($term !== '') {
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', '%' . $term . '%')
                        ->orWhere('rider_id', 'like', '%' . $term . '%')
                        ->orWhere('person_code', 'like', '%' . $term . '%')
                        ->orWhere('passport', 'like', '%' . $term . '%');
                });
            }
            $records = $query->get();
        } else {
            $query = Employee::query()->orderBy('name');
            if ($term !== '') {
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', '%' . $term . '%')
                        ->orWhere('employee_id', 'like', '%' . $term . '%')
                        ->orWhere('person_code', 'like', '%' . $term . '%')
                        ->orWhere('passport', 'like', '%' . $term . '%');
                });
            }
            $records = $query->get();
        }

        foreach ($records as $record) {
            $openIssue = PassportHandoverHistory::query()
                ->where('holder_type', $type)
                ->where('status', PassportHandoverHistory::STATUS_ISSUED)
                ->when($type === 'rider', fn ($q) => $q->where('rider_id', $record->id))
                ->when($type === 'employee', fn ($q) => $q->where('employee_id', $record->id))
                ->exists();

            $hasHistory = PassportHandoverHistory::query()
                ->where('holder_type', $type)
                ->when($type === 'rider', fn ($q) => $q->where('rider_id', $record->id))
                ->when($type === 'employee', fn ($q) => $q->where('employee_id', $record->id))
                ->exists();

            if ($term === '' && !$hasHistory && empty($record->passport) && !$openIssue) {
                continue;
            }

            $currentStatus = $openIssue ? 'issued' : ($hasHistory ? 'returned' : 'none');

            if ($statusFilter === 'issued' && !$openIssue) {
                continue;
            }
            if ($statusFilter === 'returned' && ($openIssue || !$hasHistory)) {
                continue;
            }

            $persons[] = [
                'type' => $type,
                'id' => $record->id,
                'name' => $record->name,
                'code' => $type === 'rider' ? ($record->rider_id ?? $record->id) : ($record->employee_id ?? $record->id),
                'passport' => $record->passport ?? '-',
                'current_status' => $currentStatus,
                'has_open_issue' => $openIssue,
                'history_count' => PassportHandoverHistory::query()
                    ->where('holder_type', $type)
                    ->when($type === 'rider', fn ($q) => $q->where('rider_id', $record->id))
                    ->when($type === 'employee', fn ($q) => $q->where('employee_id', $record->id))
                    ->count(),
            ];
        }

        return $persons;
    }

    private function paginateCollection($collection, array $paginationParams)
    {
        $page = $paginationParams['page'] ?? 1;
        $perPage = $paginationParams['per_page'] ?? 25;
        $items = $collection->forPage($page, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $collection->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
}
