# WhatsApp Message Format

## Example Message

When a bike is assigned to a rider, the following message will be sent to your WhatsApp group:

```
Bike  🏍
Bike No : 33184
Noon I,d : 106399
Name : Asif Ur Rehman
Date : 14-10-25
Time: 02:30 pm
Note : Give to Asif Ur Rehman
Project : Keeta
Emirates : Dubai
```

## Field Mapping

| Field | Description | Source |
|-------|-------------|--------|
| **Bike No** | Bike plate number | `bikes.plate` |
| **Noon I,d** | Rider's Noon ID or Rider ID | `riders.noon_no` (fallback to `riders.rider_id`) |
| **Name** | Rider's full name | `riders.name` |
| **Date** | Assignment date | Current date in format `dd-mm-yy` |
| **Time** | Assignment time | Current time in format `hh:mm AM/PM` |
| **Note** | Assignment note | Auto-generated: "Give to [Rider Name]" |
| **Project** | Customer/Project name | `customers.name` |
| **Emirates** | Location/Hub | `riders.emirate_hub` |

## Database Fields Used

### From `riders` table:
- `name` - Rider's full name
- `noon_no` - Noon ID (primary)
- `rider_id` - Rider ID (fallback if noon_no is empty)
- `emirate_hub` - Emirates/location

### From `bikes` table:
- `plate` - Bike plate number

### From `customers` table (via relationship):
- `name` - Project/Customer name

## Customization

To customize the message format, edit the `formatBikeAssignmentMessage()` method in:
```
app/Services/WhatsAppService.php
```

### Current Implementation:

```php
protected function formatBikeAssignmentMessage(Bikes $bike, Riders $rider, $assignmentDate = null, $assignedBy = null)
{
    $assignmentDate = $assignmentDate ?? now();
    $formattedDate = $assignmentDate->format('d-m-y');
    $formattedTime = $assignmentDate->format('h:i A');
    
    // Load relationships
    $bike->load('leasingCompany');
    $rider->load('customer', 'vendor');

    // Simple format
    $message = "Bike  🏍\n";
    $message .= "Bike No : {$bike->plate}\n";
    
    // Noon ID - using noon_no or rider_id as fallback
    $noonId = $rider->noon_no ?? $rider->rider_id ?? 'N/A';
    $message .= "Noon I,d : {$noonId}\n";
    
    $message .= "Name : {$rider->name}\n";
    $message .= "Date : {$formattedDate}\n";
    $message .= "Time: {$formattedTime}\n";
    
    // Note - can be customized
    $note = "Give to {$rider->name}";
    $message .= "Note : {$note}\n";
    
    // Project - using customer name
    $project = $rider->customer ? $rider->customer->name : 'N/A';
    $message .= "Project : {$project}\n";
    
    // Emirates - using emirate_hub
    $emirates = $rider->emirate_hub ?? 'N/A';
    $message .= "Emirates : {$emirates}";

    return $message;
}
```

## Date/Time Format Options

If you need different date/time formats, you can modify the format strings:

### Date Formats:
- `d-m-y` → 14-10-25
- `d-m-Y` → 14-10-2025
- `d/m/Y` → 14/10/2025
- `Y-m-d` → 2025-10-14
- `d M Y` → 14 Oct 2025

### Time Formats:
- `h:i A` → 02:30 PM
- `H:i` → 14:30 (24-hour)
- `h:i:s A` → 02:30:45 PM

## Adding Custom Fields

To add more fields to the message, edit the method and add:

```php
// Add fleet supervisor
if ($rider->fleet_supervisor) {
    $message .= "Supervisor : {$rider->fleet_supervisor}\n";
}

// Add bike model
if ($bike->model) {
    $message .= "Model : {$bike->model}\n";
}

// Add custom note from form (if passed)
if ($assignedBy) {
    $assignedByName = is_object($assignedBy) ? $assignedBy->name : $assignedBy;
    $message .= "Assigned By : {$assignedByName}\n";
}
```

## Testing the Format

After making changes:

1. Clear cache:
   ```bash
   php artisan config:clear
   ```

2. Restart queue worker:
   ```bash
   php artisan queue:restart
   ```

3. Assign a test bike to see the new format

## Example with Real Data

```
Bike  🏍
Bike No : ABC-1234
Noon I,d : 106399
Name : Asif Ur Rehman
Date : 14-10-25
Time: 02:30 pm
Note : Give to Asif Ur Rehman
Project : Keeta
Emirates : Dubai
```

This clean, simple format is easy to read on mobile devices and provides all essential information at a glance.

