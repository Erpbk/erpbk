<?php

namespace App\Observers;

use App\Models\Files;
use App\Models\Riders;
use App\Support\RiderDocumentReplacement;
use Illuminate\Support\Facades\Log;

class FileObserver
{
    /**
     * Handle the Files "saved" event (covers both created and updated).
     */
    public function saved(Files $file): void
    {
        // Only process rider files
        if ($file->type !== 'rider' || empty($file->type_id)) {
            return;
        }

        $rider = Riders::find($file->type_id);
        if (!$rider) {
            return;
        }

        try {
            $this->syncProfilePhoto($file, $rider);
            $this->syncDocumentExpiry($file, $rider);
        } catch (\Exception $e) {
            Log::error('File-Rider sync error', [
                'file_id' => $file->id,
                'rider_id' => $rider->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sync profile photo from Files tab to Rider.
     */
    protected function syncProfilePhoto(Files $file, Riders $rider): void
    {
        $fileName = strtolower($file->name ?? '');
        
        // Check if this file is a profile photo
        $isProfilePhoto = str_contains($fileName, 'profile') 
            || str_contains($fileName, 'photo') 
            || str_contains($fileName, 'picture')
            || str_contains($fileName, 'image');

        // Also check if it's an image file type
        $isImageType = in_array(strtolower($file->file_type ?? ''), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);

        if ($isProfilePhoto && $isImageType && !empty($file->file_name)) {
            // Files are stored in rider/{rider_id}/ subdirectory
            $storagePath = storage_path('app/public/rider/' . $rider->id . '/' . $file->file_name);
            
            // Fallback: check if file is in root public directory
            if (!file_exists($storagePath)) {
                $storagePath = storage_path('app/public/' . $file->file_name);
            }
            
            if (file_exists($storagePath)) {
                // Generate new filename for profile
                $extension = pathinfo($file->file_name, PATHINFO_EXTENSION);
                $newFileName = time() . '_' . $rider->id . '.' . $extension;
                $profilePath = storage_path('app/public/profile/' . $newFileName);
                
                // Ensure profile directory exists
                $profileDir = storage_path('app/public/profile');
                if (!is_dir($profileDir)) {
                    mkdir($profileDir, 0755, true);
                }
                
                // Copy file to profile directory
                if (copy($storagePath, $profilePath)) {
                    // Delete old profile photo if exists
                    if (!empty($rider->image_name)) {
                        $oldPath = storage_path('app/public/profile/' . $rider->image_name);
                        if (file_exists($oldPath)) {
                            @unlink($oldPath);
                        }
                    }
                    
                    // Update rider with new profile photo
                    $rider->image_name = $newFileName;
                    $rider->saveQuietly();
                    
                    Log::info('Profile photo synced', [
                        'file_id' => $file->id,
                        'rider_id' => $rider->id,
                        'new_image_name' => $newFileName,
                    ]);
                } else {
                    Log::error('Failed to copy profile photo', [
                        'file_id' => $file->id,
                        'rider_id' => $rider->id,
                        'source_path' => $storagePath,
                        'dest_path' => $profilePath,
                    ]);
                }
            } else {
                Log::warning('Profile photo file not found', [
                    'file_id' => $file->id,
                    'rider_id' => $rider->id,
                    'file_name' => $file->file_name,
                    'expected_path' => $storagePath,
                ]);
            }
        }
    }

    /**
     * Sync document expiry from Files tab to Rider Information fields.
     */
    protected function syncDocumentExpiry(Files $file, Riders $rider): void
    {
        // Only sync if the file has an expiry date
        if (empty($file->expiry_date)) {
            return;
        }

        $fileName = strtolower($file->name ?? '');
        $fieldMap = RiderDocumentReplacement::fieldMap();

        // Check if file name matches any document type
        foreach ($fieldMap as $key => $fields) {
            $expiryField = $fields['expiry'] ?? null;
            if (!$expiryField) {
                continue;
            }

            // Check if the file name contains the document key
            $matchesKey = str_contains($fileName, $key) 
                || str_contains($fileName, str_replace('_', '', $key));

            // Special cases for common variations
            $specialMatches = [
                'emirates' => str_contains($fileName, 'emirate') || str_contains($fileName, 'eid'),
                'license' => str_contains($fileName, 'licence') || str_contains($fileName, 'driving'),
                'labor' => str_contains($fileName, 'labour') || str_contains($fileName, 'card'),
                'passport' => str_contains($fileName, 'pass'),
                'road' => str_contains($fileName, 'permit'),
                'health' => str_contains($fileName, 'insurance') || str_contains($fileName, 'medical'),
            ];

            $matches = $matchesKey || ($specialMatches[$key] ?? false);

            if ($matches) {
                // Only update if the rider's expiry field is empty or different
                $currentExpiry = $rider->{$expiryField} ?? null;
                $newExpiry = $file->expiry_date instanceof \Carbon\Carbon 
                    ? $file->expiry_date->format('Y-m-d') 
                    : $file->expiry_date;

                if ($currentExpiry != $newExpiry) {
                    $rider->{$expiryField} = $newExpiry;
                    $rider->saveQuietly(); // Use saveQuietly to avoid triggering observers again
                    
                    Log::info('Document expiry synced', [
                        'file_id' => $file->id,
                        'rider_id' => $rider->id,
                        'document_type' => $key,
                        'expiry_field' => $expiryField,
                        'expiry_date' => $newExpiry,
                    ]);
                }
                
                break; // Stop after first match
            }
        }
    }

    /**
     * Handle the Files "created" event.
     */
    public function created(Files $files): void
    {
        //
    }

    /**
     * Handle the Files "updated" event.
     */
    public function updated(Files $files): void
    {
        //
    }

    /**
     * Handle the Files "deleted" event.
     */
    public function deleted(Files $files): void
    {
        //
    }

    /**
     * Handle the Files "restored" event.
     */
    public function restored(Files $files): void
    {
        //
    }

    /**
     * Handle the Files "force deleted" event.
     */
    public function forceDeleted(Files $files): void
    {
        //
    }
}
