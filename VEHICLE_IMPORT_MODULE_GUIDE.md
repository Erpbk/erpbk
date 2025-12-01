# Vehicle Import Module Documentation

## Overview
The Vehicle Import Module allows users to bulk import vehicle (bike) records into the system using Excel files. This module is similar to the Import Vouchers functionality and provides a streamlined way to add multiple vehicles at once.

## Features

### 1. **Two Access Methods**
- **Modal View**: Quick access from the Vehicles index page via the "Add Vehicle" dropdown
- **Standalone Page**: Direct access via route `/bikes/import`

### 2. **Import Options**
- Import new vehicles
- Update existing vehicles (matched by plate number)
- Optional: Reset all bike data before import (Admin only)
- Payment account selection for financial tracking

### 3. **Comprehensive Validation**
- File type validation (Excel .xlsx, .xls only)
- File size limit (50MB maximum)
- Required field validation (plate number)
- Data type validation (dates, status codes, etc.)
- Duplicate bike code checking

### 4. **Error Handling**
- Row-by-row validation
- Detailed error messages with row numbers
- Success/error count tracking
- Partial import support (successful rows are imported even if some fail)

## File Locations

### Controller
- **Path**: `app/Http/Controllers/BikesController.php`
- **Methods**:
  - `import()` - Display the import form
  - `processImport()` - Handle the file upload and processing
  - `downloadSampleTemplate()` - Generate and download sample Excel template

### Import Class
- **Path**: `app/Imports/ImportBikes.php`
- **Features**:
  - Implements `ToCollection` and `WithHeadingRow` interfaces
  - Row-by-row validation
  - Automatic relationship matching (riders, companies, customers)
  - Date parsing (handles Excel date formats)
  - Error tracking and reporting

### Views
- **Standalone Page**: `resources/views/bikes/import.blade.php`
- **Modal View**: `resources/views/bikes/import_modal.blade.php`

### Routes
```php
Route::get('bikes/import', [BikesController::class, 'import'])->name('bikes.import');
Route::post('bikes/processImport', [BikesController::class, 'processImport'])->name('bikes.processImport');
Route::get('bikes/download-template', [BikesController::class, 'downloadSampleTemplate'])->name('bikes.download-template');
```

### JavaScript Handler
- **Path**: `public/js/bike-import.js`
- **Functionality**: Handles modal loading and AJAX interactions

## Excel Template Format

### Required Column
- **plate** - Vehicle plate number (unique identifier)

### Optional Columns
1. **vehicle_type** - Type of vehicle (Motorcycle, Scooter, etc.)
2. **chassis_number** - Vehicle chassis number
3. **color** - Vehicle color
4. **model** - Vehicle model (Honda, Yamaha, etc.)
5. **model_type** - Model type/variant (CBR, NMAX, etc.)
6. **engine** - Engine capacity (150CC, 125CC, etc.)
7. **bike_code** - Internal bike code (must be unique)
8. **emirates** - Emirate (Dubai, Abu Dhabi, etc.)
9. **warehouse** - Storage location
10. **status** - Status (1 = Active, 0 = Inactive)
11. **registration_date** - Date format: YYYY-MM-DD
12. **expiry_date** - Registration expiry date
13. **insurance_expiry** - Insurance expiry date
14. **insurance_co** - Insurance company name
15. **policy_no** - Insurance policy number
16. **contract_number** - Contract number
17. **traffic_file_number** - Traffic file reference
18. **rider_name** - Rider name (matched with existing riders)
19. **company_name** - Leasing company name (matched with existing companies)
20. **customer_name** - Customer name (matched with existing customers)
21. **notes** - Additional notes

## How to Use

### Step 1: Access the Import Module
1. Navigate to the Vehicles page
2. Click on "Add Vehicle" dropdown button
3. Select "Import Vehicles"

OR

- Directly visit: `/bikes/import`

### Step 2: Download Sample Template
1. Click the "Download Sample Template" button
2. Open the downloaded Excel file
3. Review the sample data format
4. Replace sample data with your actual vehicle data

### Step 3: Prepare Your Data
1. Ensure all required columns are present
2. Use exact column headers as shown in template
3. Format dates as YYYY-MM-DD
4. Set status as 1 (Active) or 0 (Inactive)
5. Remove any empty rows

### Step 4: Upload and Import
1. Select your prepared Excel file
2. Choose the payment account from dropdown
3. (Optional - Admin only) Check "Reset all bike data" if needed
4. Click "Import Bikes"
5. Wait for processing (progress indicator will show)

### Step 5: Review Results
- Success message shows count of imported records
- If errors occur, detailed list shows row numbers and issues
- Successfully imported vehicles appear in the Vehicles list immediately

## Data Processing Logic

### New Vehicle
If a vehicle with the given plate number doesn't exist:
- Creates a new vehicle record
- Sets `created_by` to current user ID
- Sets `updated_by` to current user ID

### Existing Vehicle
If a vehicle with the given plate number exists:
- Updates the existing record with new data
- Sets `updated_by` to current user ID
- Preserves `created_by` from original record

### Relationship Matching
The system automatically matches names with existing records:
- **Rider Name**: Searches for partial match in riders table
- **Company Name**: Searches for partial match in leasing_companies table
- **Customer Name**: Searches for partial match in customers table

If no match is found, the field is set to NULL (no error thrown).

## Validation Rules

### File Validation
- Required: Yes
- Max Size: 50MB
- Allowed Types: .xlsx, .xls

### Field Validation
- **plate**: Required, string, max 100 characters
- **bike_code**: Unique (if provided), max 100 characters
- **status**: Must be 1 or 0 (if provided)
- **dates**: Must be valid date format
- **string fields**: Max 100-255 characters depending on field

## Error Messages

### Common Errors
1. "Plate number is required" - Missing plate in a row
2. "Bike code already exists" - Duplicate bike_code value
3. "Status must be 1 (Active) or 0 (Inactive)" - Invalid status value
4. "Registration date must be a valid date" - Invalid date format
5. "Excel file is required" - No file selected
6. "Select the account to credit" - Payment account not selected

### Error Display
- Errors show row number for easy identification
- Multiple errors per row are combined in error message
- Import continues for valid rows even if some rows fail

## Permissions Required
- **bike_create** - Required to access import functionality
- **admin role** - Required to use "Reset all bike data" option

## Admin Features

### Reset All Bike Data
**WARNING**: This is a destructive operation!

When enabled:
1. Deletes all records from `bike_history` table
2. Deletes all records from `bikes` table
3. Then imports the new data from Excel

**Requirements**:
- Must have admin role
- Checkbox only visible to admins
- Confirmation required before execution

## Technical Details

### Import Process Flow
1. File upload validation
2. Check reset_data flag (admin only)
3. If reset requested, truncate tables
4. Store payment_from in session
5. Parse Excel file row by row
6. Validate each row individually
7. Prepare data (format dates, match relationships)
8. Create or update bike record
9. Track success/error counts
10. Return results to user

### Date Handling
The system handles multiple date formats:
- Excel numeric dates (converted using PhpOffice)
- String dates (YYYY-MM-DD)
- Automatically converts to standard format

### AJAX Processing
- Form submitted via AJAX for better UX
- Loading indicator during processing
- Real-time error display
- Auto-redirect on success

## Testing Checklist

- [ ] Upload valid Excel file - should succeed
- [ ] Upload file with missing plate - should show error
- [ ] Upload file with duplicate bike_code - should show error
- [ ] Upload file with invalid dates - should show error
- [ ] Upload file with partial errors - should import valid rows
- [ ] Match existing rider name - should link correctly
- [ ] Match existing company name - should link correctly
- [ ] Update existing bike by plate - should update not duplicate
- [ ] Download sample template - should generate proper Excel
- [ ] Test modal view - should open and function properly
- [ ] Test standalone page - should work independently
- [ ] Test with large file (40MB+) - should process successfully
- [ ] Test without payment account - should show validation error
- [ ] Test reset data (admin) - should clear and reimport
- [ ] Test permissions - non-authorized users should see 403

## Sample Template Example

| plate | vehicle_type | chassis_number | color | model | bike_code | status |
|-------|-------------|----------------|-------|-------|-----------|--------|
| ABC123 | Motorcycle | CH123456789 | Red | Honda | BIKE001 | 1 |
| XYZ456 | Scooter | CH987654321 | Blue | Yamaha | BIKE002 | 1 |

## Troubleshooting

### Issue: Import button not visible
**Solution**: Check if user has `bike_create` permission

### Issue: Template download not working
**Solution**: Ensure route `bikes.download-template` is accessible and method exists in controller

### Issue: Excel file rejected
**Solution**: 
- Check file extension (.xlsx or .xls only)
- Check file size (max 50MB)
- Ensure file is not corrupted

### Issue: All rows showing errors
**Solution**:
- Verify column headers match exactly
- Check that plate column exists and has values
- Ensure no special characters in headers

### Issue: Dates not importing correctly
**Solution**: Format dates as YYYY-MM-DD or use Excel date format

### Issue: Rider/Company/Customer not linking
**Solution**: 
- Check spelling of names
- Ensure records exist in respective tables
- Use partial name matching (system searches with LIKE)

## Future Enhancements

Potential improvements for consideration:
1. Batch processing for very large files (10k+ rows)
2. Background job processing with progress tracking
3. Email notification on import completion
4. Import history/audit log
5. Duplicate detection before import
6. CSV format support
7. Column mapping interface (map custom headers to system fields)
8. Data preview before import
9. Rollback functionality for recent imports
10. Export current data to Excel for editing and re-import

## Related Modules
- **Bikes Module**: Main vehicles management
- **Riders Module**: Rider assignment and management
- **Leasing Companies**: Company relationships
- **Accounts Module**: Payment tracking

## Support
For issues or questions about the Vehicle Import Module, contact the development team or refer to the main ERP documentation.

---
**Last Updated**: 2025-11-04
**Version**: 1.0

