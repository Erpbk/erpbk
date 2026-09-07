<?php

namespace App\Services;

use App\Models\BikeAssignFieldAssignment;
use App\Models\BikeCustomField;
use App\Support\PublicStorageDisk;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Facades\Image as ResizeImage;
use Maestroerror\HeicToJpg;
use RuntimeException;

class BikeConditionAttachmentService
{
    public const INPUT = 'condition_images';

    /**
     * @return list<string>
     */
    public static function allowedExtensions(): array
    {
        return ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'heic', 'heif', 'pdf'];
    }

    public static function assignmentForContext(string $context): ?BikeAssignFieldAssignment
    {
        return BikeCustomField::assignModalFields($context)
            ->first(fn($field) => ($field->field_key ?? '') === self::INPUT);
    }

    /**
     * @return array<string, mixed>
     */
    public static function validationRules(bool $required = false): array
    {
        return [
            self::INPUT => $required ? 'required|array|min:1|max:20' : 'nullable|array|max:20',
            self::INPUT . '.*' => [
                'file',
                'max:10240',
                function (string $attribute, $value, $fail) {
                    if (! $value instanceof UploadedFile) {
                        return;
                    }
                    $ext = self::extensionOf($value);
                    if (! in_array($ext, self::allowedExtensions(), true)) {
                        $fail('Each vehicle condition file must be an image or a PDF.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function validationRulesForContext(string $context): array
    {
        $field = self::assignmentForContext($context);
        if (! $field) {
            return [];
        }

        return self::validationRules((bool) ($field->resolvedInputSpec()['required'] ?? false));
    }

    /**
     * @return array<string, string>
     */
    public static function validationMessagesForContext(string $context): array
    {
        $field = self::assignmentForContext($context);
        $label = $field ? $field->resolvedLabel() : 'Vehicle condition photos';

        return [
            self::INPUT . '.required' => $label . ' is required.',
            self::INPUT . '.min' => $label . ' is required.',
        ];
    }

    public function storeFromRequest(Request $request, int $bikeId, string $kind): ?string
    {
        $files = $request->file(self::INPUT);
        if ($files === null) {
            return null;
        }

        $files = is_array($files) ? $files : [$files];
        $files = array_values(array_filter($files, function ($file) {
            return $file instanceof UploadedFile && $file->isValid();
        }));

        if ($files === []) {
            return null;
        }

        if (count($files) === 1) {
            return $this->storeSingle($files[0], $bikeId, $kind);
        }

        foreach ($files as $file) {
            if ($this->isPdf($file)) {
                throw ValidationException::withMessages([
                    self::INPUT => 'Upload either one file, or multiple images (not PDFs) to combine into a PDF.',
                ]);
            }
        }

        return $this->storeAsPdf($files, $bikeId, $kind);
    }

    public function mergeOnto(array $existing, string $kind, string $path): array
    {
        $existing[$kind] = $path;

        return $existing;
    }

    private function storeSingle(UploadedFile $file, int $bikeId, string $kind): string
    {
        $ext = self::extensionOf($file) ?: 'bin';
        $name = $kind . '-' . time() . '-' . bin2hex(random_bytes(3)) . '.' . $ext;

        return PublicStorageDisk::storeUploadedFile($file, $this->directory($bikeId), $name);
    }

    /**
     * @param list<UploadedFile> $files
     */
    private function storeAsPdf(array $files, int $bikeId, string $kind): string
    {
        $pages = [];
        $temps = [];

        try {
            foreach ($files as $file) {
                $source = $file->getRealPath();
                if ($source === false || ! is_readable($source)) {
                    throw new RuntimeException('A vehicle condition image could not be read.');
                }

                if (HeicToJpg::isHeic($file) || in_array(self::extensionOf($file), ['heic', 'heif'], true)) {
                    $tmp = tempnam(sys_get_temp_dir(), 'bike_heic_');
                    if ($tmp === false) {
                        throw new RuntimeException('Could not convert a HEIC image.');
                    }
                    HeicToJpg::convert($file)->saveAs($tmp);
                    $temps[] = $tmp;
                    $source = $tmp;
                }

                $encoded = (string) ResizeImage::make($source)->resize(1600, 1600, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })->encode('jpg', 82);

                $pages[] = 'data:image/jpeg;base64,' . base64_encode($encoded);
            }
        } finally {
            foreach ($temps as $tmp) {
                if (is_file($tmp)) {
                    @unlink($tmp);
                }
            }
        }

        if ($pages === []) {
            throw new RuntimeException('No vehicle condition images could be converted.');
        }

        $html = view('bikes.condition_images_pdf', ['pages' => $pages])->render();
        $binary = Pdf::loadHTML($html)->setPaper('a4', 'portrait')->output();
        if ($binary === '' || $binary === false) {
            throw new RuntimeException('Could not build the vehicle condition PDF.');
        }

        $path = $this->directory($bikeId) . '/' . $kind . '-' . time() . '-' . bin2hex(random_bytes(3)) . '.pdf';
        if (! PublicStorageDisk::put($path, $binary)) {
            throw new RuntimeException('Could not store the vehicle condition PDF.');
        }

        return $path;
    }

    private function directory(int $bikeId): string
    {
        return 'bike/' . $bikeId . '/condition';
    }

    private function isPdf(UploadedFile $file): bool
    {
        $ext = self::extensionOf($file);
        $mime = strtolower((string) $file->getMimeType());

        return $ext === 'pdf' || str_contains($mime, 'pdf');
    }

    private static function extensionOf(UploadedFile $file): string
    {
        $ext = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: ''));

        return preg_replace('/[^a-z0-9]/', '', $ext) ?? '';
    }
}
