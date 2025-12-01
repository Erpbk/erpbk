# Activity Log Implementation Guide

## Overview

Your ERP system now has a comprehensive Activity Log module that automatically tracks all user actions (create, edit, delete) across your application. The system maintains a complete audit trail, preserving all data even when records are deleted.

## What's Already Implemented ✅

### 1. Database Structure
- **ActivityLog Model** (`app/Models/ActivityLog.php`)
- **Migration** (`database/migrations/2025_09_17_135533_create_activity_logs_table.php`)
- **Database Table**: `activity_logs` with proper indexes and foreign keys

### 2. Core Services
- **ActivityLogger Service** (`app/Services/ActivityLogger.php`)
  - Static methods for logging different actions
  - Support for both synchronous and asynchronous logging
  - Queue job support for better performance

### 3. User Interface
- **ActivityLogController** (`app/Http/Controllers/ActivityLogController.php`)
- **Views**: 
  - `resources/views/activity_logs/index.blade.php` - List all activity logs with filters
  - `resources/views/activity_logs/show.blade.php` - Detailed view of specific log
- **Routes**: Already configured in `routes/web.php`

### 4. Background Processing
- **LogActivity Job** (`app/Jobs/LogActivity.php`) - For async logging

## New Enhancements 🆕

### 1. Automatic Model Tracking Trait
**File**: `app/Traits/LogsActivity.php`

This trait automatically logs all model events (created, updated, deleted, restored) without requiring manual logging in controllers.

#### How to Use:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class YourModel extends Model
{
    use LogsActivity;
    
    // Your model code here...
    
    /**
     * Optional: Override module name
     */
    protected static function getModuleName(): string
    {
        return 'Custom Module Name';
    }
}
```

### 2. Request Logging Middleware
**File**: `app/Http/Middleware/LogUserActivity.php`

This middleware automatically logs significant HTTP requests.

#### How to Register:
Add to `app/Http/Kernel.php`:
```php
protected $middlewareGroups = [
    'web' => [
        // ... other middleware
        \App\Http\Middleware\LogUserActivity::class,
    ],
];
```

## How to Enable Activity Logging for All Models

### Step 1: Add the Trait to Your Models

For each model you want to track, add the `LogsActivity` trait:

```php
// Example: app/Models/Riders.php
use App\Traits\LogsActivity;

class Riders extends Model
{
    use LogsActivity;
    // ... rest of your model
}
```

### Step 2: Remove Manual Logging from Controllers

Since the trait handles automatic logging, you can remove manual logging calls like:

```php
// Remove these lines from your controllers:
// ActivityLogger::created('Users', $user);
// ActivityLogger::updated('Users', $user, $oldData);
// ActivityLogger::deleted('Users', $user);
```

### Step 3: (Optional) Register Middleware

Add the middleware to automatically log HTTP requests:

```php
// In app/Http/Kernel.php
protected $middlewareGroups = [
    'web' => [
        // ... existing middleware
        \App\Http\Middleware\LogUserActivity::class,
    ],
];
```

## Available Logging Methods

### Automatic Logging (via Trait)
- **Created**: When a new record is created
- **Updated**: When a record is updated (shows old vs new values)
- **Deleted**: When a record is deleted (preserves all data)
- **Restored**: When a soft-deleted record is restored (only for models using SoftDeletes trait)

### Manual Logging (via ActivityLogger)
```php
use App\Services\ActivityLogger;

// Basic logging
ActivityLogger::log('custom_action', 'ModuleName', $model, $changes);

// Specific actions
ActivityLogger::created('ModuleName', $model);
ActivityLogger::updated('ModuleName', $model, $oldData);
ActivityLogger::deleted('ModuleName', $model);

// Custom actions
ActivityLogger::custom('approved', 'Orders', $order);

// Async logging (for better performance)
ActivityLogger::createdAsync('ModuleName', $model);
ActivityLogger::updatedAsync('ModuleName', $model, $oldData);
ActivityLogger::deletedAsync('ModuleName', $model);
```

## Activity Log Data Structure

Each activity log entry contains:

```json
{
    "id": 1,
    "user_id": 123,
    "action": "updated",
    "module_name": "Users",
    "model_type": "App\\Models\\User",
    "model_id": 456,
    "changes": {
        "old": {
            "name": "John Doe",
            "email": "john@example.com"
        },
        "new": {
            "name": "John Smith",
            "email": "john@example.com"
        }
    },
    "ip_address": "192.168.1.1",
    "created_at": "2025-01-27T10:30:00.000000Z",
    "updated_at": "2025-01-27T10:30:00.000000Z"
}
```

## Viewing Activity Logs

### Web Interface
- **URL**: `/activity-logs`
- **Menu Location**: User Management → Activity Logs
- **Required Permission**: `activity_logs_view`
- **Features**:
  - Filter by user, module, action, date range
  - View detailed changes (old vs new values)
  - Statistics dashboard
  - Pagination and search

### API Endpoints
```php
// Get activity logs with filters
GET /activity-logs?user_id=1&module_name=Users&action=updated

// Get statistics
GET /activity-logs/api/statistics

// View specific log
GET /activity-logs/{id}
```

## Performance Considerations

### 1. Async Logging
Use async methods for better performance:
```php
ActivityLogger::createdAsync('ModuleName', $model);
```

### 2. Queue Configuration
Make sure your queue is properly configured to process the LogActivity jobs.

### 3. Database Indexes
The migration already includes proper indexes for:
- `user_id` + `created_at`
- `module_name` + `created_at`
- `action` + `created_at`
- `model_type` + `model_id`

### 4. Data Cleanup
Consider implementing a cleanup job to archive old logs:
```php
// Example cleanup (run daily)
ActivityLog::where('created_at', '<', now()->subYear())->delete();
```

## Security Considerations

### 1. Sensitive Data
The system logs all model data. Consider excluding sensitive fields:
```php
// In your model
protected static function bootLogsActivity()
{
    parent::bootLogsActivity();
    
    // Exclude sensitive fields from logging
    static::updating(function ($model) {
        $model->setHidden(['password', 'api_token']);
    });
}
```

### 2. Access Control
The system now includes proper permission-based access control:

**Available Permissions:**
- `activity_logs_view` - View activity logs
- `activity_logs_export` - Export activity logs
- `activity_logs_delete` - Delete activity logs

**Controller Protection:**
```php
// Middleware is already configured in ActivityLogController
$this->middleware('permission:activity_logs_view')->only(['index', 'show']);
$this->middleware('permission:activity_logs_delete')->only(['destroy']);
```

**Menu Access:**
The Activity Logs menu item is only visible to users with the `activity_logs_view` permission.

## Testing the Implementation

### 1. Test Automatic Logging
1. Add the trait to a model
2. Create/update/delete records
3. Check the activity logs in the web interface

### 2. Test Manual Logging
```php
use App\Services\ActivityLogger;

// Test in tinker or a controller
ActivityLogger::custom('test_action', 'TestModule', null, ['test' => 'data']);
```

### 3. Verify Data Integrity
- Check that deleted records preserve all data in the `changes` field
- Verify that updates show both old and new values
- Ensure user information is correctly captured

## Troubleshooting

### Common Issues

1. **No logs appearing**: Check if user is authenticated
2. **Missing changes data**: Ensure model has `$fillable` or `$guarded` properly set
3. **Performance issues**: Use async logging methods
4. **Queue not processing**: Check queue worker status

### Debug Commands
```bash
# Check queue status
php artisan queue:work

# Clear failed jobs
php artisan queue:flush

# Check activity logs
php artisan tinker
>>> App\Models\ActivityLog::latest()->take(5)->get()
```

## Next Steps

1. **Add the trait to your key models** (Riders, Bikes, etc.)
2. **Test the implementation** with a few models first
3. **Remove manual logging** from controllers where the trait is used
4. **Configure queue processing** for async logging
5. **Set up proper access controls** for viewing logs
6. **Consider data retention policies** for long-term storage

Your Activity Log system is now ready to provide comprehensive audit trails for your ERP application! 🎉
