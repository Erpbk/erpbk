<?php

namespace App\Services\Agreements;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Stores an uploaded letterhead as a raster image Dompdf and the editor can paint.
 */
class AgreementLetterheadRasterizer
{
    public function store(UploadedFile $file, int $companyId, string $kind = 'letterhead'): string
    {
        $dir = ($kind === 'watermark' ? 'agreement-watermarks/' : 'agreement-letterheads/') . $companyId;
        $mime = (string) ($file->getMimeType() ?: '');
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $sourcePath = $file->getRealPath();

        if ($sourcePath === false || ! is_readable($sourcePath)) {
            throw new RuntimeException('The letterhead file could not be read.');
        }

        if ($this->isPdf($mime, $ext)) {
            $png = $this->pdfFirstPageToPng($sourcePath);
            if ($png === null || $png === '') {
                throw new RuntimeException(
                    'This PDF could not be converted to an image. Upload a JPG or PNG of the letterhead page, or install Ghostscript / Imagick on the server.'
                );
            }

            return $this->putPng($dir, $png);
        }

        $normalized = $this->normalizeImageFile($sourcePath, $mime, $ext);
        $name = Str::uuid()->toString() . '.' . $normalized['extension'];
        $path = $dir . '/' . $name;
        Storage::disk('public')->put($path, $normalized['bytes']);

        return $path;
    }

    /**
     * @return array{bytes: string, extension: string}
     */
    private function normalizeImageFile(string $sourcePath, string $mime, string $ext): array
    {
        $image = $this->loadGd($sourcePath, $mime, $ext);
        if ($image === false) {
            throw new RuntimeException('Upload a JPG, PNG, WebP, or PDF letterhead.');
        }

        $this->downscale($image, 1654);
        imagesavealpha($image, true);

        ob_start();
        $useJpeg = ! $this->hasTransparency($image);
        if ($useJpeg) {
            imagejpeg($image, null, 84);
            $bytes = (string) ob_get_clean();
            imagedestroy($image);

            return ['bytes' => $bytes, 'extension' => 'jpg'];
        }

        imagepng($image, null, 6);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return ['bytes' => $bytes, 'extension' => 'png'];
    }

    private function putPng(string $dir, string $pngBytes): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'lhpng');
        if ($tmp === false) {
            throw new RuntimeException('Could not write the converted letterhead.');
        }

        file_put_contents($tmp, $pngBytes);
        $image = @imagecreatefrompng($tmp);
        @unlink($tmp);

        if ($image === false) {
            $path = $dir . '/' . Str::uuid()->toString() . '.png';
            Storage::disk('public')->put($path, $pngBytes);

            return $path;
        }

        $this->downscale($image, 1654);
        imagesavealpha($image, true);
        ob_start();
        imagepng($image, null, 6);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        $path = $dir . '/' . Str::uuid()->toString() . '.png';
        Storage::disk('public')->put($path, $bytes);

        return $path;
    }

    private function pdfFirstPageToPng(string $pdfPath): ?string
    {
        if (extension_loaded('imagick') && class_exists(\Imagick::class)) {
            try {
                $imagick = new \Imagick();
                $imagick->setResolution(140, 140);
                $imagick->readImage($pdfPath . '[0]');
                $imagick->setImageFormat('png');
                if (defined('Imagick::ALPHACHANNEL_REMOVE')) {
                    $imagick->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
                }
                $blob = $imagick->getImageBlob();
                $imagick->clear();
                $imagick->destroy();

                if (is_string($blob) && $blob !== '') {
                    return $blob;
                }
            } catch (\Throwable) {
                // Try Ghostscript next.
            }
        }

        $gs = $this->ghostscriptBinary();
        if ($gs === null) {
            return null;
        }

        $out = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lh-' . Str::uuid()->toString() . '.png';
        $process = new Process([
            $gs,
            '-dSAFER',
            '-dBATCH',
            '-dNOPAUSE',
            '-dQUIET',
            '-sDEVICE=png16m',
            '-r140',
            '-dFirstPage=1',
            '-dLastPage=1',
            '-sOutputFile=' . $out,
            $pdfPath,
        ]);
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful() || ! is_readable($out)) {
            @unlink($out);

            return null;
        }

        $bytes = (string) file_get_contents($out);
        @unlink($out);

        return $bytes !== '' ? $bytes : null;
    }

    private function ghostscriptBinary(): ?string
    {
        foreach (['gswin64c', 'gswin32c', 'gs'] as $binary) {
            $process = Process::fromShellCommandline(
                PHP_OS_FAMILY === 'Windows' ? 'where ' . $binary : 'command -v ' . $binary
            );
            $process->run();
            if (! $process->isSuccessful()) {
                continue;
            }

            $line = trim((string) strtok($process->getOutput(), "\n"));
            if ($line !== '' && is_file($line)) {
                return $line;
            }
            if ($line !== '') {
                return $binary;
            }
        }

        return null;
    }

    private function isPdf(string $mime, string $ext): bool
    {
        return $ext === 'pdf' || str_contains($mime, 'pdf');
    }

    private function loadGd(string $path, string $mime, string $ext)
    {
        if (str_contains($mime, 'png') || $ext === 'png') {
            return @imagecreatefrompng($path);
        }
        if (str_contains($mime, 'jpeg') || str_contains($mime, 'jpg') || in_array($ext, ['jpg', 'jpeg'], true)) {
            return @imagecreatefromjpeg($path);
        }
        if ((str_contains($mime, 'webp') || $ext === 'webp') && function_exists('imagecreatefromwebp')) {
            return @imagecreatefromwebp($path);
        }
        if (str_contains($mime, 'gif') || $ext === 'gif') {
            return @imagecreatefromgif($path);
        }

        $info = @getimagesize($path);
        if ($info === false) {
            return false;
        }

        return match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
    }

    private function downscale(&$image, int $maxEdge): void
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $edge = max($width, $height);
        if ($edge <= $maxEdge) {
            return;
        }

        $scale = $maxEdge / $edge;
        $newW = max(1, (int) round($width * $scale));
        $newH = max(1, (int) round($height * $scale));
        $resized = imagecreatetruecolor($newW, $newH);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefilledrectangle($resized, 0, 0, $newW, $newH, $transparent);
        imagealphablending($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $width, $height);
        imagedestroy($image);
        $image = $resized;
    }

    private function hasTransparency($image): bool
    {
        if (! function_exists('imageistruecolor') || ! imageistruecolor($image)) {
            return false;
        }

        $width = min(imagesx($image), 80);
        $height = min(imagesy($image), 80);
        for ($x = 0; $x < $width; $x += 4) {
            for ($y = 0; $y < $height; $y += 4) {
                $rgba = imagecolorat($image, $x, $y);
                if ((($rgba & 0x7F000000) >> 24) > 0) {
                    return true;
                }
            }
        }

        return false;
    }
}
