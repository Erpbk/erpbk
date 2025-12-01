# WhatsApp Messages Not Sending - Troubleshooting Guide

## 🔍 Step-by-Step Diagnosis

Follow these steps in order to find and fix the issue:

---

## ✅ **Step 1: Check if Node.js Service is Running**

Open Command Prompt and test:

```bash
curl http://localhost:3000/api/health
```

### **✅ Expected Result (Working):**
```json
{
  "status": "ok",
  "bot": {
    "status": "connected",
    "connected": true
  }
}
```

### **❌ If You Get an Error:**

**Problem**: Node.js service is NOT running

**Solution**: Start the service:
```bash
cd whatsapp-service
npm start
```

Keep this terminal open and running!

---

## ✅ **Step 2: Check Queue Worker is Running**

Open a NEW Command Prompt and check:

```bash
# Check if queue worker is running
php artisan queue:monitor
```

**Or check manually:**
```bash
# Windows
tasklist | findstr "php"

# Should show something like:
# php.exe    12345    Console    1    50,000 K
```

### **❌ If Queue Worker is NOT Running:**

**Solution**: Start the queue worker:
```bash
php artisan queue:work
```

Keep this terminal open and running!

---

## ✅ **Step 3: Verify Configuration**

### **Check Laravel .env:**

```bash
type .env | findstr "WHATSAPP"
```

**Expected Output:**
```
WHATSAPP_NOTIFICATIONS_ENABLED=true
WHATSAPP_NODE_SERVICE_URL=http://localhost:3000
```

### **❌ If Missing or Wrong:**

Edit your `.env` file and add/fix:
```env
WHATSAPP_NOTIFICATIONS_ENABLED=true
WHATSAPP_NODE_SERVICE_URL=http://localhost:3000
QUEUE_CONNECTION=database
```

Then clear cache:
```bash
php artisan config:clear
php artisan cache:clear
```

---

### **Check Node.js .env:**

```bash
type whatsapp-service\.env
```

**Expected Output:**
```
PORT=3000
WHATSAPP_SESSION_NAME=bike-notifications
WHATSAPP_GROUP_ID=1234567890@g.us
```

### **❌ If Group ID is Missing:**

You need to configure the WhatsApp group ID:
1. Start the service: `cd whatsapp-service && npm start`
2. Scan QR code
3. Copy the group ID from console
4. Add to `whatsapp-service/.env`: `WHATSAPP_GROUP_ID=your-group-id`
5. Restart the service

---

## ✅ **Step 4: Test WhatsApp Connection**

```bash
curl http://localhost:3000/api/status
```

**Expected Result:**
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

### **❌ Common Issues:**

**Issue 1: "connected": false**
- WhatsApp session disconnected
- Solution: Restart Node.js service and re-scan QR code

**Issue 2: "groupConfigured": false**
- Group ID not set
- Solution: Add WHATSAPP_GROUP_ID to .env

---

## ✅ **Step 5: Test API Manually**

Send a test message:

```bash
curl -X POST http://localhost:3000/api/send-message -H "Content-Type: application/json" -d "{\"type\":\"test\",\"message\":\"Test message from troubleshooting\"}"
```

### **✅ Expected Result:**
```json
{
  "success": true,
  "message": "Message sent successfully"
}
```

**AND** the message should appear in your WhatsApp group!

### **❌ If Message Doesn't Appear:**

**Problem**: WhatsApp connection issue

**Solutions:**
1. Check WhatsApp group ID is correct
2. Restart Node.js service
3. Re-authenticate with QR code
4. Check Node.js logs: `type whatsapp-service\logs\error.log`

---

## ✅ **Step 6: Check Laravel Logs**

```bash
type storage\logs\laravel.log | findstr "WhatsApp"
```

**Look for:**
- ✅ "WhatsApp notification job dispatched"
- ✅ "Message sent successfully"
- ❌ "WhatsApp notification failed"
- ❌ "Failed to connect to WhatsApp Node service"

### **View Last 50 Lines:**
```bash
# PowerShell
Get-Content storage\logs\laravel.log -Tail 50
```

---

## ✅ **Step 7: Check Failed Jobs**

```bash
php artisan queue:failed
```

### **✅ If No Failed Jobs:**
Good! Queue is working.

### **❌ If There Are Failed Jobs:**

**View the error:**
```bash
php artisan queue:failed
```

**Retry failed jobs:**
```bash
php artisan queue:retry all
```

---

## ✅ **Step 8: Check Event is Firing**

### **Test if event is registered:**

```bash
php artisan event:list | findstr "BikeAssigned"
```

**Expected Output:**
```
App\Events\BikeAssignedEvent
  App\Listeners\SendBikeAssignmentNotification
```

### **❌ If Not Listed:**

**Problem**: Event not registered

**Solution**: Check `app/Providers/EventServiceProvider.php`

Should contain:
```php
protected $listen = [
    \App\Events\BikeAssignedEvent::class => [
        \App\Listeners\SendBikeAssignmentNotification::class,
    ],
];
```

Then run:
```bash
php artisan event:clear
php artisan config:clear
```

---

## ✅ **Step 9: Test Database Queue**

```bash
php artisan tinker
```

```php
// Check if jobs table exists
\DB::table('jobs')->count();
// Should return a number

// Check for pending jobs
\DB::table('jobs')->get();
// Shows pending jobs

exit
```

### **❌ If Jobs Table Doesn't Exist:**

```bash
php artisan queue:table
php artisan migrate
```

---

## ✅ **Step 10: Real-Time Debugging**

### **Terminal 1: Node.js Service with Logs**
```bash
cd whatsapp-service
npm start
```

### **Terminal 2: Queue Worker with Verbose**
```bash
php artisan queue:work --verbose
```

### **Terminal 3: Monitor Logs**
```bash
# PowerShell
Get-Content storage\logs\laravel.log -Wait -Tail 20
```

### **Now Assign a Bike**

Watch all 3 terminals to see what happens!

---

## 🐛 **Common Issues & Solutions**

### **Issue 1: Configuration Not Applied**

**Symptoms:**
- Changed .env but nothing happens

**Solution:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan queue:restart
```

---

### **Issue 2: Queue Worker Not Processing**

**Symptoms:**
- Jobs get queued but never process

**Solution:**
```bash
# Stop all queue workers
taskkill /F /IM php.exe

# Start fresh
php artisan queue:work
```

---

### **Issue 3: WhatsApp Session Disconnected**

**Symptoms:**
- Was working, now stopped
- API returns "not connected"

**Solution:**
```bash
# Restart Node.js service
cd whatsapp-service

# Stop current (Ctrl+C)
# Restart
npm start

# If QR code appears, scan it again
```

---

### **Issue 4: Port Already in Use**

**Symptoms:**
- Can't start Node.js service
- Error: "Port 3000 already in use"

**Solution:**
```bash
# Find what's using port 3000
netstat -ano | findstr :3000

# Kill that process
taskkill /F /PID [PID_NUMBER]

# Start service again
npm start
```

---

### **Issue 5: Event Not Firing**

**Symptoms:**
- No job appears in queue
- No logs

**Solution:**

Check `BikesController.php` has this code (around line 451-455):

```php
// Fire event for WhatsApp notification
$rider = Riders::find($request->rider_id);
if ($rider) {
    event(new \App\Events\BikeAssignedEvent($bike, $rider, now(), Auth::user()));
}
```

If missing, the event is not being fired!

---

## 📊 **Quick Diagnostic Commands**

Run these all at once to check status:

```bash
@echo off
echo === WhatsApp Integration Status ===
echo.

echo [1] Node.js Service:
curl -s http://localhost:3000/api/health
echo.

echo [2] Laravel Config:
type .env | findstr "WHATSAPP"
echo.

echo [3] Queue Workers:
tasklist | findstr "php.exe"
echo.

echo [4] Failed Jobs:
php artisan queue:failed --json
echo.

echo [5] Last 5 Log Entries:
powershell -command "Get-Content storage\logs\laravel.log -Tail 5"
```

---

## 🔄 **Complete Reset (If Nothing Works)**

### **1. Stop Everything:**
```bash
# Kill all PHP processes
taskkill /F /IM php.exe

# Stop Node.js (Ctrl+C in its terminal)
```

### **2. Clear Everything:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan event:clear
php artisan queue:clear
php artisan queue:flush
```

### **3. Restart Services:**

**Terminal 1:**
```bash
cd whatsapp-service
npm start
```

**Terminal 2:**
```bash
php artisan queue:work --verbose
```

### **4. Test Again:**
Assign a bike and watch the terminals!

---

## ✅ **Working Checklist**

Your system should have:

- [ ] Node.js service running (port 3000)
- [ ] API health check returns OK
- [ ] WhatsApp status shows "connected": true
- [ ] WhatsApp group ID is configured
- [ ] Queue worker is running
- [ ] Laravel .env has WHATSAPP_NOTIFICATIONS_ENABLED=true
- [ ] Test message sends successfully via API
- [ ] No failed jobs in queue
- [ ] Event is registered (event:list shows it)
- [ ] Logs show no errors

---

## 🎯 **Step-by-Step Test**

Follow this exact sequence:

### **1. Start Services:**
```bash
# Terminal 1
cd whatsapp-service
npm start

# Terminal 2  
php artisan queue:work --verbose
```

### **2. Verify:**
```bash
# Terminal 3
curl http://localhost:3000/api/health
curl http://localhost:3000/api/status
```

### **3. Test API:**
```bash
curl -X POST http://localhost:3000/api/send-message -H "Content-Type: application/json" -d "{\"type\":\"test\",\"message\":\"API Test\"}"
```

Did message appear in WhatsApp? ✅ or ❌

### **4. Test Laravel:**
```bash
php artisan tinker
```

```php
// Manually trigger notification
$bike = \App\Models\Bikes::first();
$rider = \App\Models\Riders::first();
event(new \App\Events\BikeAssignedEvent($bike, $rider, now(), \Auth::user()));
exit
```

Watch Terminal 2 (queue worker) - should show job processing!

### **5. Test Real Assignment:**
- Go to your Laravel app
- Assign a bike to a rider
- Watch Terminal 2 for job processing
- Check WhatsApp group

---

## 📞 **Still Not Working?**

### **Collect This Information:**

1. **Node.js API Response:**
   ```bash
   curl http://localhost:3000/api/health
   ```

2. **Laravel Config:**
   ```bash
   php artisan tinker
   >>> env('WHATSAPP_NOTIFICATIONS_ENABLED')
   >>> env('WHATSAPP_NODE_SERVICE_URL')
   ```

3. **Queue Status:**
   ```bash
   php artisan queue:failed
   ```

4. **Last Logs:**
   ```bash
   type storage\logs\laravel.log | findstr "WhatsApp"
   type whatsapp-service\logs\error.log
   ```

5. **Event Registration:**
   ```bash
   php artisan event:list | findstr "BikeAssigned"
   ```

Share these outputs to diagnose the issue!

---

## 🎉 **Success Indicators**

When working correctly, you should see:

**Terminal 1 (Node.js):**
```
WhatsApp Service running on port 3000
✓ WhatsApp authenticated successfully!
Received message request
Message sent successfully
```

**Terminal 2 (Queue Worker):**
```
[2024-10-18 14:30:00] Processing: App\Jobs\SendWhatsAppNotificationJob
[2024-10-18 14:30:01] Processed:  App\Jobs\SendWhatsAppNotificationJob
```

**WhatsApp Group:**
```
Bike  🏍
Bike No : ABC-1234
...
```

---

**Follow these steps and you'll find the issue!** 🚀

