<?php

namespace App\Http\Controllers;

use App\Models\UploadFile;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use App\DataTables\UploadFilesDataTable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UploadFilesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(UploadFilesDataTable $dataTable)
    {
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

    public function show($id)
    {
        $file = UploadFile::findOrFail($id);
        return view('upload_files.show', compact('file'));
    }

    public function edit($id)
    {
        $file = UploadFile::findOrFail($id);
        return view('upload_files.edit', compact('file'));
    }

    public function update(Request $request, $id)
    {
        $file = UploadFile::findOrFail($id);

        $request->validate(['detail' => 'nullable|string']);

        $file->update(['detail' => $request->detail]);

        return response()->json(['success' => true, 'message' => 'File updated successfully.']);
    }

    public function destroy($id)
    {
        $file = UploadFile::findOrFail($id);
        Storage::disk('public')->delete($file->path);
        $file->delete();

        return response()->json(['success' => true, 'message' => 'File deleted successfully.']);
    }
}
