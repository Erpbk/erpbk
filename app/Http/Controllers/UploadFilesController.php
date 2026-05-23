<?php

namespace App\Http\Controllers;

use App\Models\UploadFile;
use App\Support\DocumentExpiry;
use App\Support\DocumentExpiryDashboard;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use App\DataTables\UploadFilesDataTable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class UploadFilesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index($company_slug, UploadFilesDataTable $dataTable, Request $request)
    {
        $expiry = (string) $request->query('expiry', '');
        if (in_array($expiry, ['expiring', 'expired'], true) && Schema::hasTable('files')) {
            $days = DocumentExpiry::windowDays((int) $request->query('days', DocumentExpiry::DEFAULT_WINDOW_DAYS));
            $user = auth()->user();
            $section = DocumentExpiryDashboard::listSectionForUser($user, $expiry);

            $filterLabel = $expiry === 'expired'
                ? __('Expired documents')
                : __('Documents expiring within :days days', ['days' => $days]);

            return view('upload_files.expiry', [
                'expiry' => $expiry,
                'days' => $days,
                'filterLabel' => $filterLabel,
                'items' => $section['items'] ?? [],
                'byModule' => $section['by_module'] ?? [],
                'total' => (int) ($section['total'] ?? 0),
            ]);
        }

        return $dataTable->render('upload_files.index');
    }

    public function create()
    {
        return view('upload_files.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
            'detail' => 'nullable|string',
        ]);

        $path = $request->file('file')->store('uploads/files', 'public');

        $upload = UploadFile::create([
            'name' => $request->file('file')->getClientOriginalName(),
            'detail' => $request->detail,
            'path' => $path,
            'uploaded_by' => Auth::id(),
        ]);

        return response()->json(['success' => true, 'message' => 'File uploaded successfully.']);
    }

    public function show($company_slug, $id)
    {
        $file = UploadFile::findOrFail($id);
        return view('upload_files.show', compact('file'));
    }

    public function edit($company_slug, $id)
    {
        $file = UploadFile::findOrFail($id);
        return view('upload_files.edit', compact('file'));
    }

    public function update(Request $request, $company_slug, $id)
    {
        $file = UploadFile::findOrFail($id);

        $request->validate(['detail' => 'nullable|string']);

        $file->update(['detail' => $request->detail]);

        return response()->json(['success' => true, 'message' => 'File updated successfully.']);
    }

    public function destroy($company_slug, $id)
    {
        $file = UploadFile::findOrFail($id);
        Storage::disk('public')->delete($file->path);
        $file->delete();

        return response()->json(['success' => true, 'message' => 'File deleted successfully.']);
    }
}
