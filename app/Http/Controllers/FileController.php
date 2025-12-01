<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;

class FileController extends Controller
{
  public function show($folder, $filename)
  {
    $path = "public/$folder/$filename"; // Path inside storage/app/public/

    if (!Storage::exists($path)) {
      abort(404); // Return 404 if file not found
    }

    return response()->file(storage_path("app/$path"));
  }
  public function root($folder, $filename)
  {
    $path = "$folder/$filename"; // Path inside storage/app/public/

    if (!Storage::exists($path)) {
      abort(404); // Return 404 if file not found
    }

    return response()->file(storage_path("app/$path"));
  }
}



