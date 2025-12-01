# WhatsApp Integration - Complete Verification Checklist

## ✅ Step-by-Step Verification Guide

Use this checklist to verify your WhatsApp integration is working correctly.

---

## 📋 **Part 1: File Existence Check**

### **Laravel Files** (Check these exist)

```bash
# Navigate to your Laravel root directory
cd d:\xammp1\htdocs\erpbk
```

**Run these commands to verify files exist:**

```bash
# Check Event file
dir app\Events\BikeAssignedEvent.php

# Check Listener file
dir app\Listeners\SendBikeAssignmentNotification.php

# Check Job file
dir app\Jobs\SendWhatsAppNotificationJob.php

# Check Service file
dir app\Services\WhatsAppService.php
```

**Expected Result:** All 4 files should be found.

---

### **Node.js Service Files** (Check these exist)

```bash
# Navigate to whatsapp-service directory
cd whatsapp-service

# Check all files
dir server.js
dir whatsapp-bot.js
dir logger.js
dir package.json
dir ecosystem.config.js
```

**Expected Result:** All 5 files should be found.

---

## 🔍 **Part 2: Code Verification**

### **Verify Laravel Event Registration**

**Check**: `app/Providers/EventServiceProvider.php`

```bash
type app\Providers\EventServiceProvider.php | findstr "BikeAssignedEvent"
```

**Expected Output:** Should contain:
```
\App\Events\BikeAssignedEvent::class => [
    \App\Listeners\SendBikeAssignmentNotification::class,
```

---

### **Verify BikesController Integration**

**Check**: `app/Http/Controllers/BikesController.php`

```bash
type app\Http\Controllers\BikesController.php | findstr "BikeAssignedEvent"
```

**Expected Output:** Should contain:
```
event(new \App\Events\BikeAssignedEvent($bike, $rider, now(), Auth::user()));
```

**Or manually verify:**
Open `app/Http/Controllers/BikesController.php` and search for `BikeAssignedEvent`. You should find it in the bike assignment logic (around line 450-575).

---

### **Verify WhatsApp Service**

**Check**: `app/Services/WhatsAppService.php`

```bash
type app\Services\WhatsAppService.php | findstr "formatBikeAssignmentMessage"
```

**Expected Output:** Method should exist.

**Verify message format:**
```bash
type app\Services\WhatsAppService.php | findstr "Bike  🏍"
```

**Expected Output:** Should show the new simple format.

---

## ⚙️ **Part 3: Configuration Check**

### **Laravel .env Configuration**

**Check your `.env` file has these lines:**

```bash
type .env | findstr "WHATSAPP"
```

**Expected Output:**
```
WHATSAPP_NOTIFICATIONS_ENABLED=true
WHATSAPP_NODE_SERVICE_URL=http://localhost:3000
```

**Also check queue configuration:**
```bash
type .env | findstr "QUEUE_CONNECTION"
```

**Expected Output:**
```
QUEUE_CONNECTION=database
```

---

### **Node.js Service Configuration**

**Check**: `whatsapp-service/.env`

```bash
cd whatsapp-service
type .env
```

**Should contain:**
```env
PORT=3000
NODE_ENV=production
WHATSAPP_SESSION_NAME=bike-notifications
WHATSAPP_GROUP_ID=your-group-id-here
LOG_LEVEL=info
```

---

## 🧪 **Part 4: Test Laravel Configuration**

### **Test 1: Check Laravel Config**

```bash
php artisan tinker
```

**Inside Tinker, run:**
```php
env('WHATSAPP_NOTIFICATIONS_ENABLED')
// Expected: true

env('WHATSAPP_NODE_SERVICE_URL')
// Expected: "http://localhost:3000"

env('QUEUE_CONNECTION')
// Expected: "database"

exit
```

---

### **Test 2: Verify Queue Tables Exist**

```bash
php artisan db:show
```

**Then check tables:**
```bash
php artisan tinker
```

```php
\DB::table('jobs')->count()
// Should return a number (even 0 is OK)

\DB::table('failed_jobs')->count()
// Should return a number (even 0 is OK)

exit
```

---

### **Test 3: Check Event Listener Registration**

```bash
php artisan event:list
```

**Look for:**
```
App\Events\BikeAssignedEvent
    App\Listeners\SendBikeAssignmentNotification
```

---

## 🚀 **Part 5: Node.js Service Verification**

### **Test 1: Check Dependencies**

```bash
cd whatsapp-service

# Check if node_modules exists
dir node_modules
```

**If not found, install:**
```bash
npm install
```

---

### **Test 2: Start Node.js Service**

```bash
npm start
```

**Expected Output:**
```
WhatsApp Service running on port 3000
Environment: production
Initializing WhatsApp bot...
```

**What to check:**
- ✅ No error messages
- ✅ Service starts successfully
- ✅ Port 3000 is accessible

---

### **Test 3: Test API Health (New Terminal)**

**Open a NEW terminal/command prompt:**

```bash
curl http://localhost:3000/api/health
```

**Expected Response:**
```json
{
  "status": "ok",
  "bot": {
    "status": "connected",
    "connected": true,
    "groupConfigured": true
  }
}
```

---

## 📱 **Part 6: WhatsApp Connection Test**

### **Test 1: Check WhatsApp Status**

```bash
curl http://localhost:3000/api/status
```

**Expected Response:**
```json
{
  "success": true,
  "status": {
    "status": "connected",
    "connected": true,
    "groupConfigured": true,
    "groupId": "1234567890@g.us"
  }
}
```

---

### **Test 2: Send Test Message**

```bash
curl -X POST http://localhost:3000/api/send-message -H "Content-Type: application/json" -d "{\"type\":\"test\",\"message\":\"Test from verification script\"}"
```

**Expected:**
- ✅ Response: `"success": true`
- ✅ Message appears in WhatsApp group

---

## 🔄 **Part 7: Queue Worker Verification**

### **Test 1: Check Queue Worker Running**

**In a NEW terminal:**

```bash
php artisan queue:work --once
```

**Expected Output:**
```
[2024-10-18 14:30:00] Processing: App\Jobs\...
[2024-10-18 14:30:01] Processed:  App\Jobs\...
```

---

### **Test 2: Start Queue Worker (Production)**

```bash
php artisan queue:work
```

**Keep this terminal open. Expected output:**
```
INFO  Processing jobs from the [default] queue.
```

---

## 🎯 **Part 8: End-to-End Integration Test**

### **Complete Flow Test:**

**Prerequisites:**
- ✅ Node.js service running (`npm start` in whatsapp-service)
- ✅ Queue worker running (`php artisan queue:work`)
- ✅ WhatsApp connected

**Steps:**

1. **Go to your Laravel application in browser**

2. **Navigate to Bikes section**

3. **Assign a bike to a rider:**
   - Select a bike
   - Select a rider
   - Set status to "Active"
   - Click Save/Assign

4. **Check your WhatsApp group within 5 seconds**

**Expected Result:**
```
Bike  🏍
Bike No : ABC-1234
Noon I,d : 106399
Name : Asif Ur Rehman
Date : 18-10-24
Time: 02:30 pm
Note : Give to Asif Ur Rehman
Project : Keeta
Emirates : Dubai
```

---

## 📊 **Part 9: Log Verification**

### **Check Laravel Logs**

```bash
tail -f storage/logs/laravel.log
```

**Look for:**
```
WhatsApp notification job dispatched
bike_id: 123
rider_id: 456
```

---

### **Check Node.js Logs**

```bash
tail -f whatsapp-service/logs/combined.log
```

**Look for:**
```
Received message request
Message sent successfully
```

---

## 🐛 **Part 10: Troubleshooting Tests**

### **Test 1: Clear All Caches**

```bash
php artisan config:clear
php artisan cache:clear
php artisan event:clear
php artisan route:clear
php artisan view:clear
```

---

### **Test 2: Check Failed Jobs**

```bash
php artisan queue:failed
```

**Expected:** Should be empty or show failed jobs you can investigate.

**If there are failed jobs, retry them:**
```bash
php artisan queue:retry all
```

---

### **Test 3: Restart Queue Worker**

```bash
php artisan queue:restart
```

---

### **Test 4: Restart Node.js Service**

```bash
# In whatsapp-service terminal, press Ctrl+C
# Then restart:
npm start
```

---

## ✅ **Verification Checklist Summary**

Mark each as complete:

### **Files & Code**
- [ ] All Laravel files exist (4 files)
- [ ] All Node.js files exist (5 files)
- [ ] Event registered in EventServiceProvider
- [ ] BikesController fires event
- [ ] WhatsAppService has correct message format

### **Configuration**
- [ ] Laravel .env configured correctly
- [ ] Node.js .env configured correctly
- [ ] WhatsApp group ID set

### **Services Running**
- [ ] Node.js service starts without errors
- [ ] Node.js API responds to health check
- [ ] Queue worker processes jobs
- [ ] WhatsApp session connected

### **Integration Test**
- [ ] Test message sends via API
- [ ] Bike assignment triggers notification
- [ ] Message appears in WhatsApp group
- [ ] Message format is correct

### **Logs & Monitoring**
- [ ] Laravel logs show job dispatch
- [ ] Node.js logs show message sent
- [ ] No errors in either log
- [ ] Failed jobs table is empty

---

## 📝 **Quick Verification Script**

Create a file: `verify-whatsapp.bat` (Windows)

```batch
@echo off
echo ========================================
echo WhatsApp Integration Verification
echo ========================================
echo.

echo [1/5] Checking Laravel files...
if exist app\Events\BikeAssignedEvent.php (
    echo ✓ BikeAssignedEvent.php exists
) else (
    echo ✗ BikeAssignedEvent.php NOT FOUND
)

if exist app\Listeners\SendBikeAssignmentNotification.php (
    echo ✓ SendBikeAssignmentNotification.php exists
) else (
    echo ✗ SendBikeAssignmentNotification.php NOT FOUND
)

if exist app\Jobs\SendWhatsAppNotificationJob.php (
    echo ✓ SendWhatsAppNotificationJob.php exists
) else (
    echo ✗ SendWhatsAppNotificationJob.php NOT FOUND
)

if exist app\Services\WhatsAppService.php (
    echo ✓ WhatsAppService.php exists
) else (
    echo ✗ WhatsAppService.php NOT FOUND
)

echo.
echo [2/5] Checking Node.js service files...
if exist whatsapp-service\server.js (
    echo ✓ server.js exists
) else (
    echo ✗ server.js NOT FOUND
)

if exist whatsapp-service\whatsapp-bot.js (
    echo ✓ whatsapp-bot.js exists
) else (
    echo ✗ whatsapp-bot.js NOT FOUND
)

if exist whatsapp-service\package.json (
    echo ✓ package.json exists
) else (
    echo ✗ package.json NOT FOUND
)

echo.
echo [3/5] Checking configuration...
findstr /C:"WHATSAPP_NOTIFICATIONS_ENABLED" .env >nul
if %errorlevel% equ 0 (
    echo ✓ WHATSAPP_NOTIFICATIONS_ENABLED found in .env
) else (
    echo ✗ WHATSAPP_NOTIFICATIONS_ENABLED NOT FOUND in .env
)

findstr /C:"WHATSAPP_NODE_SERVICE_URL" .env >nul
if %errorlevel% equ 0 (
    echo ✓ WHATSAPP_NODE_SERVICE_URL found in .env
) else (
    echo ✗ WHATSAPP_NODE_SERVICE_URL NOT FOUND in .env
)

echo.
echo [4/5] Testing Node.js API...
curl -s http://localhost:3000/api/health >nul 2>&1
if %errorlevel% equ 0 (
    echo ✓ Node.js service is responding
    curl -s http://localhost:3000/api/health
) else (
    echo ✗ Node.js service is NOT responding
    echo   Make sure to run: npm start in whatsapp-service directory
)

echo.
echo [5/5] Checking queue configuration...
findstr /C:"QUEUE_CONNECTION" .env >nul
if %errorlevel% equ 0 (
    echo ✓ QUEUE_CONNECTION found in .env
) else (
    echo ✗ QUEUE_CONNECTION NOT FOUND in .env
)

echo.
echo ========================================
echo Verification Complete!
echo ========================================
echo.
echo Next Steps:
echo 1. Start queue worker: php artisan queue:work
echo 2. Start Node.js service: cd whatsapp-service ^&^& npm start
echo 3. Assign a test bike to verify end-to-end
echo.
pause
```

**Run it:**
```bash
verify-whatsapp.bat
```

---

## 🎉 **Success Criteria**

Your implementation is correct if:

✅ All files exist  
✅ Configuration is set  
✅ Node.js service responds to API calls  
✅ Queue worker processes jobs  
✅ WhatsApp session is connected  
✅ Test message sends successfully  
✅ Bike assignment triggers WhatsApp notification  
✅ Message format matches your requirement  

---

## 📞 **Need Help?**

If any check fails:
1. Review the specific section that failed
2. Check logs: `storage/logs/laravel.log` and `whatsapp-service/logs/`
3. Refer to [WHATSAPP_INTEGRATION_GUIDE.md](WHATSAPP_INTEGRATION_GUIDE.md) troubleshooting section

---

**Your system is verified and ready!** 🚀

