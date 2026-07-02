<?php

namespace App\Services;

use App\Support\PublicStorageDisk;
use Intervention\Image\Facades\Image as ResizeImage;
use Maestroerror\HeicToJpg;

class ImageService
{
    public function uploadLogo($request)
    {
        ['name' => $name, 'file' => $imageFile, 'tmp' => $tmp] = $this->resolveSourceFile($request->file('image_name'));

        $this->saveToPublicDisk(
            ResizeImage::make($imageFile)->resize(null, 120, function ($img) {
                $img->aspectRatio();
            }),
            'uploads/' . $name
        );

        $this->saveToPublicDisk(
            ResizeImage::make($imageFile)->resize(null, 75, function ($img) {
                $img->aspectRatio();
            }),
            'uploads/thumbnail/' . $name
        );

        $this->cleanupTmp($tmp);

        return $name;
    }

    public function uploadWithSize($request, $width, $height)
    {
        ['name' => $name, 'file' => $imageFile, 'tmp' => $tmp] = $this->resolveSourceFile($request->file('image_name'));

        $this->saveToPublicDisk(
            ResizeImage::make($imageFile)->resize($width ?? null, $height ?? null, function ($img) {
                $img->aspectRatio();
            }),
            'uploads/' . $name
        );

        $this->cleanupTmp($tmp);

        return $name;
    }

    public function uploadEvent($request)
    {
        ['name' => $name, 'file' => $imageFile, 'tmp' => $tmp] = $this->resolveSourceFile($request->file('image_name'));

        $this->saveToPublicDisk(
            ResizeImage::make($imageFile)->resize(null, 500, function ($img) {
                $img->aspectRatio();
            }),
            'uploads/' . $name
        );

        $this->saveToPublicDisk(
            ResizeImage::make($imageFile)->resize(null, 355, function ($img) {
                $img->aspectRatio();
            }),
            'uploads/thumbnail/' . $name
        );

        $this->cleanupTmp($tmp);

        return $name;
    }

    public function uploadImage($request)
    {
        ['name' => $name, 'file' => $imageFile, 'tmp' => $tmp] = $this->resolveSourceFile($request->file('image_name'));

        $this->saveToPublicDisk(
            ResizeImage::make($imageFile)->resize(null, 212, function ($img) {
                $img->aspectRatio();
            }),
            'uploads/' . $name
        );

        $this->cleanupTmp($tmp);

        return $name;
    }

    public function uploadGallery($request, $type, $type_id)
    {
        foreach ($request->images as $image) {
            ['name' => $name, 'file' => $imageFile, 'tmp' => $tmp] = $this->resolveSourceFile($image);

            $this->saveToPublicDisk(
                ResizeImage::make($imageFile)->resize(null, 500, function ($img) {
                    $img->aspectRatio();
                }),
                'uploads/' . $name
            );

            $this->cleanupTmp($tmp);

            Images::create([
                'type' => $type,
                'type_id' => $type_id,
                'image_name' => $name,
            ]);
        }

        return true;
    }

    /**
     * @return array{name: string, file: mixed, tmp: ?string}
     */
    private function resolveSourceFile($uploadedFile): array
    {
        $name = time() . '.' . $uploadedFile->extension();
        $imageFile = $uploadedFile;
        $tmp = null;

        if (HeicToJpg::isHeic($uploadedFile)) {
            $name = rand(1, 20) . time() . '.jpg';
            $tmp = tempnam(sys_get_temp_dir(), 'heic_');
            HeicToJpg::convert($uploadedFile)->saveAs($tmp);
            $imageFile = $tmp;
        }

        return compact('name', 'imageFile', 'tmp');
    }

    private function saveToPublicDisk($image, string $relativePath): void
    {
        $extension = pathinfo($relativePath, PATHINFO_EXTENSION) ?: 'jpg';
        PublicStorageDisk::put($relativePath, (string) $image->encode($extension));
    }

    private function cleanupTmp(?string $tmp): void
    {
        if ($tmp && is_file($tmp)) {
            @unlink($tmp);
        }
    }
}
