# Testing File Synchronization

## How to Test Profile Photo Sync

1. **Go to a Rider Profile** → Click on **Files** tab
2. **Upload an image** with one of these words in the filename:
   - "profile" (e.g., `profile.jpg`, `rider_profile.png`)
   - "photo" (e.g., `photo.jpg`, `john_photo.jpeg`)
   - "picture" (e.g., `picture.png`)
   - "image" (e.g., `profile_image.jpg`)

3. **After upload**, the image should:
   - Appear in the Files list
   - **Automatically sync** to the rider's profile photo on the View page
   - Replace any existing profile photo

4. **Refresh the View page** to see the updated profile photo

## How to Test Document Expiry Sync

1. **Go to a Rider Profile** → Click on **Files** tab
2. **Upload a document** (PDF, image, etc.) with a name that matches a document type:
   - **Passport**: filename contains "passport" or "pass"
   - **Emirates ID**: filename contains "emirate", "emirates", or "eid"
   - **License**: filename contains "license", "licence", or "driving"
   - **Labor Card**: filename contains "labor", "labour", or "card"
   - **Road Permit**: filename contains "road" or "permit"
   - **Health Insurance**: filename contains "health", "insurance", or "medical"

3. **Set the Expiry Date** when uploading
4. **After upload**, check **Rider Information** tab:
   - The corresponding expiry field should be updated with the same date
   - For example: if you uploaded "passport_scan.pdf" with expiry "2025-12-31"
   - Then "Passport Expiry" in Rider Information should show "2025-12-31"

## How to Test Expired Document Badge

1. **Create test data**:
   - Add some expired dates in Rider Information (e.g., Passport Expiry = 2023-01-01)
   - Upload files with expired dates in Files tab

2. **Check the badges**:
   - Look at the tabs in the rider profile
   - Both **Information** and **Files** tabs should show a red badge with a number
   - The number should be the **total** of:
     - Expired fields in Rider Information (passport_expiry, license_expiry, etc.)
     - Expired files in Files tab (files with expiry_date in the past)

3. **Test dynamic update**:
   - Upload a new file with an expired date
   - Refresh the page
   - Badge count should increase

## Troubleshooting

### Profile Photo Not Syncing

**Check Application Logs** (`storage/logs/laravel.log`):
- Look for "Profile photo synced" (success)
- Look for "Profile photo file not found" or "Failed to copy profile photo" (errors)

**Verify**:
1. File was uploaded successfully to Files tab
2. Filename contains "profile", "photo", "picture", or "image"
3. File type is jpg, jpeg, png, gif, or webp
4. Observer is registered (check `app/Providers/AppServiceProvider.php`)

**Manual Check**:
```php
// Run in tinker: php artisan tinker
$file = \App\Models\Files::latest()->first();
echo "File Name: " . $file->name . "\n";
echo "File Type: " . $file->file_type . "\n";
echo "Stored As: " . $file->file_name . "\n";
echo "Path: storage/app/public/rider/" . $file->type_id . "/" . $file->file_name . "\n";
```

### Document Expiry Not Syncing

**Check Application Logs**:
- Look for "Document expiry synced" (success)

**Verify**:
1. File has an expiry_date set
2. Filename matches a document type keyword
3. Rider Information field exists for that document type

**Test Manually**:
```php
// Run in tinker
$rider = \App\Models\Riders::find(1); // Change ID
echo "Passport Expiry: " . $rider->passport_expiry . "\n";
echo "License Expiry: " . $rider->license_expiry . "\n";
// etc.
```

### Badge Count Not Updating

**Verify Counts**:
```php
// Run in tinker
$rider = \App\Models\Riders::find(1); // Change ID
$infoCount = \App\Support\RiderDocumentReplacement::expiredCountForRider($rider);
$filesCount = \App\Support\RiderDocumentReplacement::expiredFilesCountForRider($rider);
$total = \App\Support\RiderDocumentReplacement::totalExpiredCountForRider($rider);

echo "Rider Info Expired: " . $infoCount . "\n";
echo "Files Tab Expired: " . $filesCount . "\n";
echo "Total: " . $total . "\n";
```

**Check Database**:
```sql
-- Expired fields in rider table
SELECT id, name, passport_expiry, license_expiry, labor_card_expiry, emirate_exp, road_permit_expiry
FROM riders
WHERE id = 1;

-- Expired files
SELECT id, name, expiry_date, created_at
FROM files
WHERE type = 'rider'
  AND type_id = 1
  AND expiry_date IS NOT NULL
  AND expiry_date < CURDATE()
ORDER BY expiry_date DESC;
```

## Common Issues

1. **Badge shows 0 but documents are expired**
   - Hard refresh the page (Ctrl+F5)
   - Check if `$inventoryTabRider` is set correctly in view.blade.php
   - Verify dates are in correct format (YYYY-MM-DD)

2. **Profile photo uploads but doesn't appear**
   - Check storage permissions: `storage/app/public/profile/` should be writable
   - Verify symbolic link: `php artisan storage:link`
   - Check if old photo path is cached in browser

3. **Document expiry syncs but shows wrong date**
   - Check date format in database
   - Verify timezone settings in config/app.php
   - Check if Carbon date casting is working correctly

## Success Indicators

✅ Profile photo appears on View page immediately after upload
✅ Document expiry dates sync to Rider Information automatically
✅ Badge counts update when refreshing the page
✅ Application logs show "Profile photo synced" and "Document expiry synced"
✅ No errors in `storage/logs/laravel.log`
