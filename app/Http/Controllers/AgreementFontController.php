<?php

namespace App\Http\Controllers;

use App\Services\Agreements\AgreementFontSettings;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AgreementFontController extends Controller
{
    /**
     * Public TTF files for the agreement editor, HTML preview, and print.
     * Must stay unauthenticated: @font-face from a blob preview iframe cannot send cookies.
     */
    public function show(string $file, AgreementFontSettings $fonts): BinaryFileResponse
    {
        $path = $fonts->cachedFontPath($file);
        if ($path === null) {
            $fonts->cachedFaces();
            $path = $fonts->cachedFontPath($file);
        }

        abort_if($path === null, 404);

        return response()->file($path, [
            'Content-Type' => 'font/ttf',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET',
            'Cross-Origin-Resource-Policy' => 'cross-origin',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
