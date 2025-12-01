# Custom Activity Log Module Documentation

## Overview
This is a fully custom Activity Log module for the ERP system built in Laravel. It provides comprehensive logging capabilities without using any third-party packages like Spatie.

## Features
- ✅ Custom migration for `activity_logs` table
- ✅ ActivityLog model with relationships and scopes
- ✅ ActivityLogger service with static methods
- ✅ Queued job for asynchronous logging (performance optimization)
- ✅ ActivityLogController with filtering and statistics
- ✅ Clean Blade views with filters and responsive design
- ✅ Example implementation in UserController
- ✅ Fully isolated module (doesn't modify existing ERP modules)

## Database Structure

### activity_logs Table
```sql
- id (primary key)
- user_id (nullable, FK to users table)
- action (string: created, updated, deleted, logged_in, logged_out)
- module_name (string: invoices, users, payments, etc.)
- model_type (string: class name of the model, nullable)
- model_id (bigInteger, nullable)
- changes (json: stores old and new values)
- ip_address (string, nullable)
- created_at, updated_at (timestamps)
```

## Usage Examples

### Basic Logging
```php
use App\Services\ActivityLogger;

// Log a created action
ActivityLogger::created('Users', $user);

// Log an updated action
ActivityLogger::updated('Users', $user, $oldData);

// Log a deleted action
ActivityLogger::deleted('Users', $user);

// Log custom actions
ActivityLogger::custom('approved', 'Invoices', $invoice, ['reason' => 'Payment received']);
```

### Asynchronous Logging (Recommended for Performance)
```php
// Use async methods for better performance
ActivityLogger::createdAsync('Users', $user);
ActivityLogger::updatedAsync('Users', $user, $oldData);
ActivityLogger::deletedAsync('Users', $user);
```

### Authentication Logging
```php
// Log login/logout
ActivityLogger::login($user);
ActivityLogger::logout($user);
```

## Implementation in Controllers

### Example: UserController
```php
use App\Services\ActivityLogger;

public function store(CreateUserRequest $request)
{
    // ... existing code ...
    $user = $this->userRepository->create($input);
    
    // Log the creation
    ActivityLogger::created('Users', $user);
    
    // ... rest of the code ...
}

public function update($id, UpdateUserRequest $request)
{
    $user = $this->userRepository->find($id);
    $oldData = $user->toArray(); // Store old data before update
    
    // ... update logic ...
    $user = $this->userRepository->update($input, $id);
    
    // Log the update
    ActivityLogger::updated('Users', $user, $oldData);
    
    // ... rest of the code ...
}

public function destroy($id)
{
    $user = $this->userRepository->find($id);
    
    // Log the deletion BEFORE deleting
    ActivityLogger::deleted('Users', $user);
    
    $this->userRepository->delete($id);
    
    // ... rest of the code ...
}
```

## Routes

The module provides the following routes:
- `GET /activity-logs` - Index page with filters
- `GET /activity-logs/{id}` - Show individual log details
- `GET /activity-logs/api/statistics` - Get statistics (AJAX)

## Views

### Index Page Features
- ✅ Responsive table with all activity logs
- ✅ Filter modal with options for:
  - User selection
  - Module filtering
  - Action type filtering
  - Date range filtering
- ✅ Statistics modal with charts and summaries
- ✅ Pagination support
- ✅ Clean, modern UI design

### Show Page Features
- ✅ Detailed view of individual activity log
- ✅ User information display
- ✅ Changes comparison (old vs new values)
- ✅ Model information
- ✅ Responsive design

## Performance Optimization

### Queued Jobs
The module includes a `LogActivity` job for asynchronous logging:
```php
// This will be queued and processed in the background
ActivityLogger::logAsync('created', 'Users', $user, ['new' => $user->toArray()]);
```

### Database Indexes
The migration includes optimized indexes for:
- `user_id` + `created_at`
- `module_name` + `created_at`
- `action` + `created_at`
- `model_type` + `model_id`

## Adding to Other Controllers

To add activity logging to any controller:

1. Import the ActivityLogger service:
```php
use App\Services\ActivityLogger;
```

2. Add logging calls in your CRUD methods:
```php
// For creation
ActivityLogger::created('ModuleName', $model);

// For updates (store old data first)
$oldData = $model->toArray();
// ... update logic ...
ActivityLogger::updated('ModuleName', $model, $oldData);

// For deletion (log before deleting)
ActivityLogger::deleted('ModuleName', $model);
```

## Module Isolation

This module is completely isolated:
- ✅ Doesn't modify existing ERP modules
- ✅ Only adds new logging calls to controllers
- ✅ Independent table and functionality
- ✅ Can be easily removed if needed

## Queue Configuration

To use asynchronous logging, ensure your queue is configured and running:
```bash
# Start the queue worker
php artisan queue:work

# Or use supervisor/systemd for production
```

## Security Considerations

- IP addresses are logged for audit trails
- User authentication is required for all activity log routes
- Sensitive data in changes should be handled carefully
- Consider data retention policies for activity logs

## Future Enhancements

Potential improvements:
- Export functionality for activity logs
- Real-time notifications for specific actions
- Advanced filtering and search capabilities
- Integration with external audit systems
- Automated cleanup of old logs

## Troubleshooting

### Common Issues

1. **Migration fails**: Check if table already exists
2. **Queue not working**: Ensure queue worker is running
3. **Missing relationships**: Verify User model exists and is properly configured

### Debug Commands
```bash
# Check migration status
php artisan migrate:status

# Check queue status
php artisan queue:work --once

# Clear cache if needed
php artisan cache:clear
```

## Conclusion

This custom Activity Log module provides a robust, performant, and isolated solution for tracking user activities in your ERP system. It's designed to be easily integrated into existing controllers while maintaining high performance through asynchronous processing.
