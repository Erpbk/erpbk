<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileController extends Controller
{
  public function show(string $path): BinaryFileResponse
  {
    return $this->serve($path, preferPublic: true);
  }

  public function root(string $path): BinaryFileResponse
  {
    return $this->serve($path, preferPublic: false);
  }

  private function serve(string $relativePath, bool $preferPublic = false): BinaryFileResponse
  {
    $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');

    if ($relativePath === '' || str_contains($relativePath, '..')) {
      abort(404);
    }

    if ($preferPublic && Storage::disk('public')->exists($relativePath)) {
      return response()->file(Storage::disk('public')->path($relativePath));
    }

    $candidates = $preferPublic
      ? ["public/{$relativePath}", $relativePath]
      : [$relativePath, "public/{$relativePath}"];

    foreach ($candidates as $path) {
      if (Storage::disk('local')->exists($path)) {
        return response()->file(storage_path('app/' . $path));
      }
    }

    abort(404);
  }
}
