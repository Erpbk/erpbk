# "Assigned By" Tracking in BikeHistory

## ✅ How It Works

When a bike is assigned to a rider, the system automatically saves WHO performed the assignment in the `bike_history` table using the `created_by` field.

---

## 📊 Database Field

### **Table**: `bike_history`

| Field | Type | Description |
|-------|------|-------------|
| `created_by` | integer | User ID of who assigned the bike |
| `bike_id` | integer | Bike that was assigned |
| `rider_id` | integer | Rider who received the bike |
| `warehouse` | string | Status (Active, Return, etc.) |
| `note_date` | date | Date of assignment |
| `notes` | text | Additional notes |

---

## 🔧 Implementation

### **Method 1: `assignrider()` - Main Assignment Method**

**File**: `app/Http/Controllers/BikesController.php` (Lines 441-449)

```php
BikeHistory::create([
  'bike_id'     => $bike->id,
  'rider_id'    => $bike->rider_id,
  'warehouse'   => 'Active',
  'note_date'   => $request->note_date,
  'return_date' => null,
  'notes'       => $request->notes ?? null,
  'created_by'  => Auth::id()  // ✅ Saves who assigned the bike
]);
```

---

### **Method 2: `assign_rider()` - Alternative Assignment Method**

**File**: `app/Http/Controllers/BikesController.php` (Line 598)

```php
// Save bike history with created_by
$data['created_by'] = Auth::id(); // ✅ Saves who assigned the bike
BikeHistory::create($data);
```

---

## 🔍 How to View Assignment History

### **Query Who Assigned a Bike:**

```php
use App\Models\BikeHistory;
use App\Models\User;

// Get assignment history with user info
$history = BikeHistory::with('creator')
    ->where('bike_id', $bikeId)
    ->where('warehouse', 'Active')
    ->orderBy('note_date', 'desc')
    ->first();

// Get the user who assigned
$assignedBy = User::find($history->created_by);
echo "Assigned by: " . $assignedBy->name;
```

---

### **Add Relationship to BikeHistory Model** (Optional)

**File**: `app/Models/BikeHistory.php`

Add this method to easily access who created/assigned:

```php
public function creator()
{
    return $this->belongsTo(User::class, 'created_by');
}
```

Then you can use:

```php
$history = BikeHistory::with('creator')->find($id);
echo $history->creator->name; // Name of user who assigned
```

---

## 📱 WhatsApp Notification Integration

When the bike is assigned, the event is fired with the user information:

**File**: `app/Http/Controllers/BikesController.php` (Line 454)

```php
// Fire event for WhatsApp notification
$rider = Riders::find($request->rider_id);
if ($rider) {
  event(new \App\Events\BikeAssignedEvent(
    $bike, 
    $rider, 
    now(), 
    Auth::user()  // ✅ Passes the user who assigned
  ));
}
```

The `Auth::user()` contains the full user object (ID, name, email, etc.)

---

## 📋 Example: Get Assignment History

### **View All Assignments for a Bike:**

```php
use App\Models\BikeHistory;
use App\Models\User;

$bikeId = 123;

$assignments = BikeHistory::where('bike_id', $bikeId)
    ->where('warehouse', 'Active')
    ->orderBy('note_date', 'desc')
    ->get();

foreach ($assignments as $assignment) {
    $user = User::find($assignment->created_by);
    echo "Bike assigned to Rider #{$assignment->rider_id} ";
    echo "by {$user->name} ";
    echo "on {$assignment->note_date}\n";
}
```

---

## 🔍 Query Examples

### **Find who assigned a specific bike:**

```sql
SELECT 
  bh.bike_id,
  bh.rider_id,
  bh.note_date,
  u.name as assigned_by
FROM bike_history bh
LEFT JOIN users u ON u.id = bh.created_by
WHERE bh.bike_id = 123
AND bh.warehouse = 'Active'
ORDER BY bh.note_date DESC;
```

---

### **Get assignment statistics by user:**

```sql
SELECT 
  u.name,
  COUNT(*) as total_assignments
FROM bike_history bh
LEFT JOIN users u ON u.id = bh.created_by
WHERE bh.warehouse = 'Active'
GROUP BY u.name
ORDER BY total_assignments DESC;
```

---

## 📊 Reports

### **Generate Assignment Report:**

```php
use App\Models\BikeHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// Get assignments grouped by user
$report = BikeHistory::select(
    'created_by',
    DB::raw('COUNT(*) as total_assignments')
)
->where('warehouse', 'Active')
->groupBy('created_by')
->with('creator')
->get();

foreach ($report as $row) {
    echo "{$row->creator->name}: {$row->total_assignments} assignments\n";
}
```

---

## ✅ Verification

### **Check if "Assigned By" is being saved:**

```bash
# In Laravel Tinker
php artisan tinker
```

```php
// Get latest bike assignment
$latest = \App\Models\BikeHistory::latest()->first();

// Check if created_by is set
$latest->created_by; // Should return user ID (not null)

// Get the user who assigned
$user = \App\Models\User::find($latest->created_by);
$user->name; // Name of user who assigned

exit
```

---

## 🔄 Update History Records (If Needed)

If you have old records without `created_by`, you can update them:

```php
use App\Models\BikeHistory;

// Set a default user for old records
BikeHistory::whereNull('created_by')
    ->update(['created_by' => 1]); // Set to admin user ID
```

---

## 📝 Field Information

### **Current User Information Available:**

When `Auth::user()` or `Auth::id()` is called, you get:

```php
Auth::id()          // User ID (saved to created_by)
Auth::user()->name  // User name
Auth::user()->email // User email
Auth::user()->role  // User role (if available)
```

---

## 🎯 Summary

✅ **Both assignment methods** now save `created_by`
✅ Stores the **User ID** of who performed the assignment
✅ Stored in **`bike_history`** table
✅ Can be queried to see assignment history
✅ Passed to **WhatsApp event** for notifications
✅ Can generate **reports** on who assigns bikes

---

## 📞 Common Queries

### **Who assigned this bike?**
```php
$history = BikeHistory::where('bike_id', $bikeId)->latest()->first();
$user = User::find($history->created_by);
echo $user->name;
```

### **How many bikes did I assign today?**
```php
$count = BikeHistory::where('created_by', Auth::id())
    ->whereDate('note_date', today())
    ->where('warehouse', 'Active')
    ->count();
```

### **Show assignment history for a bike**
```php
$history = BikeHistory::where('bike_id', $bikeId)
    ->orderBy('note_date', 'desc')
    ->get();
    
foreach ($history as $record) {
    $user = User::find($record->created_by);
    echo "Assigned by {$user->name} on {$record->note_date}\n";
}
```

---

**The system is now tracking WHO assigns bikes to riders!** ✅

