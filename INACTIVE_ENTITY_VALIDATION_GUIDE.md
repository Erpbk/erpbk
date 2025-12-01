# Inactive Entity Validation Implementation Guide

## Overview

This guide documents the system-wide validation that prevents entries from being made against inactive entities (riders, accounts, banks, customers, suppliers, vendors, etc.) throughout the entire project.

## Table of Contents

1. [Architecture](#architecture)
2. [Components](#components)
3. [How It Works](#how-it-works)
4. [Usage Examples](#usage-examples)
5. [Extending the System](#extending-the-system)
6. [Testing](#testing)

---

## Architecture

The inactive entity validation system is built using the following components:

```
┌─────────────────────────────────────────────────────────────┐
│                        Request Layer                         │
│  (Controllers, Form Requests, API Endpoints)                │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                   Validation Layer                           │
│  - Custom Validation Rules (ActiveRider, ActiveAccount...)  │
│  - ActiveStatusValidator Service                            │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                  Transaction Layer                           │
│  - TransactionService (validates before creating)           │
│  - VoucherService (uses TransactionService)                 │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                     Model Layer                              │
│  - Models with HasActiveStatus trait                        │
│  - Database (riders, accounts, banks, etc.)                 │
└─────────────────────────────────────────────────────────────┘
```

---

## Components

### 1. HasActiveStatus Trait (`app/Traits/HasActiveStatus.php`)

A reusable trait that provides methods to check if an entity is active.

**Features:**
- `isActive()` - Check if entity is active
- `isInactive()` - Check if entity is inactive
- `scopeActive($query)` - Query scope for active records
- `scopeInactive($query)` - Query scope for inactive records
- `getStatusLabel()` - Get human-readable status label

**Models Using This Trait:**
- `Riders`
- `Accounts`
- `Banks`
- `Customers`
- `Supplier`
- `Vendors`

**Status Logic:**

| Entity   | Active When                          | Inactive When                        |
|----------|--------------------------------------|--------------------------------------|
| Riders   | `status == 1`                        | `status != 1`                        |
| Accounts | `status == 1 AND is_locked == 0`     | `status != 1 OR is_locked == 1`      |
| Others   | `status == 1` or `status == true`    | `status != 1` or `status == false`   |

### 2. ActiveStatusValidator Service (`app/Services/ActiveStatusValidator.php`)

A centralized service for validating entity status across the application.

**Methods:**
- `validateRider($riderId)` - Validate rider is active
- `validateAccount($accountId)` - Validate account is active (also validates linked rider if account is a rider account)
- `validateBank($bankId)` - Validate bank is active (also validates linked account)
- `validateCustomer($customerId)` - Validate customer is active
- `validateSupplier($supplierId)` - Validate supplier is active
- `validateVendor($vendorId)` - Validate vendor is active
- `validateMultipleAccounts(array $accountIds)` - Validate multiple accounts at once
- `validate($entityType, $entityId)` - Generic validation by entity type
- `validateOrFail($entityType, $entityId)` - Validate and throw exception if fails

**Return Format:**
All validation methods return an array:
```php
[
    'valid' => bool,           // true if entity is active, false otherwise
    'message' => string|null   // Error message if validation fails, null if passes
]
```

### 3. Custom Validation Rules

Laravel custom validation rules for use in form requests and controllers.

**Available Rules:**
- `ActiveRider` - Validates rider_id field
- `ActiveAccount` - Validates account_id field
- `ActiveBank` - Validates bank_id field
- `ActiveCustomer` - Validates customer_id field

**Location:** `app/Rules/`

### 4. TransactionService Update (`app/Services/TransactionService.php`)

The TransactionService now validates that accounts are active before creating any transaction.

**What Changed:**
- Automatically validates account status in `recordTransaction()` method
- Throws exception if account or linked rider is inactive
- All services using TransactionService now get automatic validation

### 5. Controller Updates

The following controllers have been updated with validation:

**RidersController:**
- `storeadvanceloan()` - Validates rider account before creating advance loan
- `storecod()` - Validates rider account before creating COD entry
- `storepayment()` - Validates rider account before creating payment

**PaymentController:**
- `store()` - Validates bank and account before creating payment

**ReceiptController:**
- `store()` - Validates bank and account before creating receipt

---

## How It Works

### Example Flow: Creating a Payment for an Inactive Rider

```
1. User submits payment form for Rider (Status = 3, Inactive)
   ↓
2. PaymentController::store() called
   ↓
3. ActiveStatusValidator->validateBank($bankId) ✓ Pass
   ↓
4. ActiveStatusValidator->validateAccount($accountId)
   ↓
5. Account found, belongs to Rider
   ↓
6. ActiveStatusValidator->validateRider($riderId)
   ↓
7. Rider status = 3 (Inactive)
   ↓
8. Validation FAILS
   ↓
9. Error returned to user: "Cannot create entry for inactive rider: [Rider Name] 
   (Rider ID: [ID]). Please activate the rider first."
   ↓
10. Payment NOT created
```

### Validation Points

The system validates at multiple levels:

1. **Request Level** - Using custom validation rules in form requests
2. **Controller Level** - Explicit validation before processing
3. **Service Level** - TransactionService validates before creating transactions
4. **Automatic** - Any code using TransactionService gets validation for free

---

## Usage Examples

### Example 1: Using ActiveStatusValidator in a Controller

```php
<?php

namespace App\Http\Controllers;

use App\Services\ActiveStatusValidator;
use Illuminate\Http\Request;

class MyController extends Controller
{
    public function store(Request $request)
    {
        $validator = new ActiveStatusValidator();
        
        // Validate rider
        $validation = $validator->validateRider($request->rider_id);
        if (!$validation['valid']) {
            return redirect()->back()
                ->withErrors(['rider_id' => $validation['message']])
                ->withInput();
        }
        
        // Validate account
        $validation = $validator->validateAccount($request->account_id);
        if (!$validation['valid']) {
            return redirect()->back()
                ->withErrors(['account_id' => $validation['message']])
                ->withInput();
        }
        
        // Continue with processing...
    }
}
```

### Example 2: Using Custom Validation Rules in Form Request

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ActiveRider;
use App\Rules\ActiveAccount;

class CreateTransactionRequest extends FormRequest
{
    public function rules()
    {
        return [
            'rider_id' => ['required', 'integer', new ActiveRider()],
            'account_id' => ['required', 'integer', new ActiveAccount()],
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
```

### Example 3: Using HasActiveStatus Trait in Models

```php
<?php

use App\Models\Riders;

// Check if a rider is active
$rider = Riders::find(1);
if ($rider->isActive()) {
    echo "Rider is active";
} else {
    echo "Rider is inactive: Cannot create entries";
}

// Get only active riders
$activeRiders = Riders::active()->get();

// Get status label
echo $rider->getStatusLabel(); // "Active" or "Inactive"
```

### Example 4: Using validateOrFail for Simple Validation

```php
<?php

use App\Services\ActiveStatusValidator;

$validator = new ActiveStatusValidator();

try {
    $validator->validateOrFail('rider', $riderId);
    $validator->validateOrFail('account', $accountId);
    
    // Continue processing - all entities are active
    
} catch (\Exception $e) {
    // Handle validation error
    return response()->json(['error' => $e->getMessage()], 400);
}
```

### Example 5: Validating Multiple Accounts

```php
<?php

use App\Services\ActiveStatusValidator;

$validator = new ActiveStatusValidator();

$accountIds = [100, 200, 300, 400];
$result = $validator->validateMultipleAccounts($accountIds);

if (!$result['valid']) {
    // One or more accounts are inactive
    foreach ($result['invalidAccounts'] as $invalid) {
        echo $invalid['message'] . "\n";
    }
}
```

---

## Extending the System

### Adding Validation to a New Entity

**Step 1: Add HasActiveStatus trait to the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasActiveStatus;

class MyNewEntity extends Model
{
    use HasActiveStatus;
    
    // Model code...
}
```

**Step 2: Add validation method to ActiveStatusValidator**

```php
/**
 * Validate if a my_new_entity is active
 * 
 * @param int $entityId
 * @return array ['valid' => bool, 'message' => string|null]
 */
public function validateMyNewEntity($entityId)
{
    if (empty($entityId)) {
        return ['valid' => true, 'message' => null];
    }

    $entity = MyNewEntity::find($entityId);
    
    if (!$entity) {
        return [
            'valid' => false,
            'message' => "MyNewEntity with ID {$entityId} not found."
        ];
    }

    if (empty($entity->status)) {
        return [
            'valid' => false,
            'message' => "Cannot create entry for inactive entity: {$entity->name}. Please activate it first."
        ];
    }

    return ['valid' => true, 'message' => null];
}
```

**Step 3: Add case to the validate() method**

```php
public function validate($entityType, $entityId)
{
    switch (strtolower($entityType)) {
        // ... existing cases ...
        case 'my_new_entity':
            return $this->validateMyNewEntity($entityId);
        default:
            Log::warning("Unknown entity type for validation: {$entityType}");
            return ['valid' => true, 'message' => null];
    }
}
```

**Step 4: Create a custom validation rule (optional)**

```php
<?php

namespace App\Rules;

use App\Services\ActiveStatusValidator;
use Illuminate\Contracts\Validation\Rule;

class ActiveMyNewEntity implements Rule
{
    protected $validator;
    protected $message;

    public function __construct()
    {
        $this->validator = new ActiveStatusValidator();
    }

    public function passes($attribute, $value)
    {
        if (empty($value)) {
            return true;
        }

        $result = $this->validator->validateMyNewEntity($value);
        
        if (!$result['valid']) {
            $this->message = $result['message'];
            return false;
        }

        return true;
    }

    public function message()
    {
        return $this->message ?? 'The selected entity is not active.';
    }
}
```

### Adding Validation to a New Controller

Add validation before creating any entries:

```php
public function store(Request $request)
{
    // Add validation
    $validator = new \App\Services\ActiveStatusValidator();
    $validation = $validator->validateAccount($request->account_id);
    
    if (!$validation['valid']) {
        return redirect()->back()
            ->withErrors(['account_id' => $validation['message']])
            ->withInput();
    }
    
    // Continue with your logic...
}
```

---

## Testing

### Manual Testing Checklist

- [ ] **Test Inactive Rider Validation**
  - Create a rider with status = 3 (inactive)
  - Try to create advance loan → Should be blocked
  - Try to create COD entry → Should be blocked
  - Try to create payment → Should be blocked
  - Activate rider (status = 1)
  - Try creating entries again → Should work

- [ ] **Test Inactive Account Validation**
  - Set an account status to 0 or is_locked to 1
  - Try to create transaction → Should be blocked
  - Activate account and unlock it
  - Try creating transaction → Should work

- [ ] **Test Inactive Bank Validation**
  - Set a bank status to 0
  - Try to create payment/receipt → Should be blocked
  - Activate bank
  - Try again → Should work

- [ ] **Test Locked Account**
  - Lock an account (is_locked = 1)
  - Try to create transaction → Should be blocked
  - Unlock account
  - Try again → Should work

- [ ] **Test Rider Account Linking**
  - Create inactive rider
  - Try to create transaction on rider's account → Should be blocked
  - Activate rider
  - Try again → Should work

### Writing Automated Tests

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Riders;
use App\Models\Accounts;
use App\Services\ActiveStatusValidator;

class InactiveEntityValidationTest extends TestCase
{
    /** @test */
    public function it_prevents_entries_for_inactive_riders()
    {
        $rider = Riders::factory()->create(['status' => 3]); // Inactive
        
        $validator = new ActiveStatusValidator();
        $result = $validator->validateRider($rider->id);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('inactive rider', $result['message']);
    }
    
    /** @test */
    public function it_allows_entries_for_active_riders()
    {
        $rider = Riders::factory()->create(['status' => 1]); // Active
        
        $validator = new ActiveStatusValidator();
        $result = $validator->validateRider($rider->id);
        
        $this->assertTrue($result['valid']);
        $this->assertNull($result['message']);
    }
    
    /** @test */
    public function it_prevents_transactions_for_locked_accounts()
    {
        $account = Accounts::factory()->create([
            'status' => 1,
            'is_locked' => 1
        ]);
        
        $validator = new ActiveStatusValidator();
        $result = $validator->validateAccount($account->id);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('locked account', $result['message']);
    }
}
```

---

## Error Messages

The system provides clear, actionable error messages:

| Scenario | Error Message |
|----------|---------------|
| Inactive Rider | "Cannot create entry for inactive rider: [Name] (Rider ID: [ID]). Please activate the rider first." |
| Inactive Account | "Cannot create entry for inactive account: [Name] ([Code]). Please activate the account first." |
| Locked Account | "Cannot create entry for locked account: [Name] ([Code]). Please unlock the account first." |
| Inactive Bank | "Cannot create entry for inactive bank: [Name] ([Account No]). Please activate the bank first." |
| Inactive Customer | "Cannot create entry for inactive customer: [Name]. Please activate the customer first." |
| Inactive Supplier | "Cannot create entry for inactive supplier: [Name]. Please activate the supplier first." |
| Inactive Vendor | "Cannot create entry for inactive vendor: [Name]. Please activate the vendor first." |

---

## Benefits

1. **Data Integrity** - Prevents inconsistent data from inactive entities
2. **Centralized Logic** - Single source of truth for validation
3. **Reusable Components** - Trait, service, and rules can be used anywhere
4. **Clear Error Messages** - Users know exactly what went wrong and how to fix it
5. **Automatic Validation** - TransactionService validates automatically
6. **Easy to Extend** - Simple pattern to add new entities
7. **Multiple Validation Levels** - Request, Controller, Service, and Model levels

---

## Troubleshooting

### "Still able to create entries for inactive riders"

**Possible Causes:**
1. Controller not using validation
2. Using direct model creation instead of TransactionService
3. Status field not properly set in database

**Solution:**
- Ensure controller uses `ActiveStatusValidator`
- Use `TransactionService` for all transactions
- Check database status values

### "Validation passing but entity appears inactive"

**Possible Causes:**
1. Status field type mismatch (string vs int)
2. Multiple status fields (status, active, is_active, etc.)
3. Cached data

**Solution:**
- Check HasActiveStatus trait logic for your entity
- Verify status field name and type in database
- Clear application cache

### "Getting validation errors for optional fields"

**Possible Causes:**
- Validator checking empty values

**Solution:**
- All validators already handle empty values by returning `['valid' => true]`
- Make sure you're passing the actual field value, not checking it yourself

---

## Summary

The inactive entity validation system provides comprehensive, multi-layered protection against creating entries for inactive entities. It's built on reusable components that are easy to extend and maintain, with clear error messages that help users understand and resolve issues.

For any questions or issues, refer to the code in:
- `app/Traits/HasActiveStatus.php`
- `app/Services/ActiveStatusValidator.php`
- `app/Rules/Active*.php`
- `app/Services/TransactionService.php`

