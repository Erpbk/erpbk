# Files Tab & Document Expiry Synchronization

## Overview

This feature automatically synchronizes data between the Files tab and Rider Information section, ensuring consistency across the system.

## Features Implemented

### 1. Profile Photo Synchronization

**Automatic sync from Files tab to View page:**

- When a user uploads a profile photo (or any image with "profile", "photo", "picture", or "image" in the filename) to the Files tab for a rider
- The system automatically copies it to the profile directory and updates the rider's profile photo
- The photo appears immediately on the View page without requiring a separate upload
- Old profile photos are automatically deleted to save storage space

**Implementation:**

- `FileObserver` watches for file uploads
- Checks if the file is an image type (jpg, jpeg, png, gif, webp)
- Checks if the filename suggests it's a profile photo
- Copies to `storage/app/public/profile/` directory
- Updates `riders.image_name` field

### 2. Document Expiry Date Synchronization

**Automatic sync from Files tab to Rider Information:**

- When a document is uploaded or updated in the Files tab with an expiry date
- The system checks if the document type matches any field in Rider Information:
  - **Passport** → `passport_expiry`
  - **Emirates/EID** → `emirate_exp`
  - **License/Driving License** → `license_expiry`
  - **Labor/Labour Card** → `labor_card_expiry`
  - **Road Permit** → `road_permit_expiry`
  - **Health/Insurance** → `insurance_expiry`
  - **NIC/CNIC** → `cnic_expiry`
- If a match is found, the expiry date is automatically synchronized to the Rider Information field

**Matching Logic:**

- Case-insensitive filename matching
- Supports common variations (e.g., "licence" vs "license", "labour" vs "labor")
- First match wins (to avoid conflicts if a file name matches multiple document types)

### 3. Expired Document Badges

**Accurate combined counts:**

- Badge now shows total count of expired documents from BOTH:
  - Rider Information section (passport_expiry, license_expiry, etc.)
  - Files tab (files with expiry_date in the past)
- Updates automatically whenever:
  - A document or expiry date is added/modified
  - Synchronization occurs between Files and Rider Information

**Implementation:**

- `RiderDocumentReplacement::expiredCountForRider()` - counts expired fields in Rider Information
- `RiderDocumentReplacement::expiredFilesCountForRider()` - counts expired files in Files tab
- `RiderDocumentReplacement::totalExpiredCountForRider()` - combines both counts
- Badge displays on both Information and Files tabs

## Technical Implementation

### Files Created/Modified:

1. **app/Observers/FileObserver.php** (NEW)
   - Watches for file save events (created/updated)
   - Handles profile photo synchronization
   - Handles document expiry synchronization
   - Uses `saveQuietly()` to prevent infinite loops

2. **app/Providers/AppServiceProvider.php** (MODIFIED)
   - Registered FileObserver in boot() method
   - Observer is automatically triggered on all file operations

3. **app/Support/RiderDocumentReplacement.php** (MODIFIED)
   - Added `expiredFilesCountForRider()` - counts expired files
   - Added `totalExpiredCountForRider()` - combined count method
   - Existing `expiredCountForRider()` unchanged (counts Rider Info fields)

4. **resources/views/riders/view.blade.php** (MODIFIED)
   - Updated to use `totalExpiredCountForRider()` instead of `expiredCountForRider()`
   - Badge now shows combined count from both sources

## Usage

### For End Users:

**Profile Photo:**

1. Go to rider profile → Files tab
2. Upload an image file with "profile" in the name (e.g., "profile_photo.jpg")
3. The photo automatically appears on the View page hero section
4. No need to upload separately via the camera button

**Document Expiry:**

1. Go to rider profile → Files tab
2. Upload a document (e.g., "passport_scan.pdf")
3. Set the expiry date in the Files form
4. The expiry date automatically syncs to Rider Information → Passport Expiry field
5. Badge counts update automatically

**Expired Document Badges:**

- The red badge on Information and Files tabs shows total expired documents
- Includes both:
  - Expired fields in Rider Information (passport_expiry, license_expiry, etc.)
  - Expired files in Files tab (files with past expiry_date)
- Hover to see exact count

## Benefits

1. **Reduced Data Entry:** Upload once, information syncs automatically
2. **Consistency:** Same data across all sections
3. **Accuracy:** Badge counts always reflect current state
4. **User-Friendly:** No manual synchronization required
5. **Storage Efficient:** Old profile photos are automatically deleted

## Future Enhancements (Optional)

- Bi-directional sync (Rider Information → Files tab)
- Conflict resolution UI when dates differ
- Bulk document upload with auto-detection
- Document type auto-detection using AI/OCR
- Notification when documents are about to expire

## Testing Recommendations

1. **Profile Photo Test:**
   - Upload various image formats with "profile" in filename
   - Verify photo appears on View page
   - Upload another profile photo and verify old one is replaced

2. **Document Expiry Test:**
   - Upload passport with expiry date in Files tab
   - Check Rider Information → Passport Expiry is updated
   - Repeat for license, labor card, etc.

3. **Badge Count Test:**
   - Create rider with some expired fields in Rider Information
   - Upload expired files in Files tab
   - Verify badge shows combined count
   - Update an expiry date and verify badge updates

4. **Edge Cases:**
   - Upload file without expiry date (should not cause errors)
   - Upload file with ambiguous name (should handle gracefully)
   - Upload non-image file as "profile" (should be ignored)
   - Update existing file's expiry date (should re-sync)

## Support

For issues or questions, refer to:

- `app/Observers/FileObserver.php` - Main synchronization logic
- `app/Support/RiderDocumentReplacement.php` - Badge calculation and field mapping
- Application logs for sync errors (search for "File-Rider sync error")
